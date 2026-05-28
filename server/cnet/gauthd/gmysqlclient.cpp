#include <sstream>
#include <iostream>
#include <string>
#include <vector>
#include <mysql/mysql.h>

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"
#include "protocol.h"
#include "thread.h"
#include "timersender.h"
#include "octets.h"
#include "base64.h"
#include "getaddcashsn.hrp"
#include "addcash.hpp"

#include "authmanager.h"
#include "gmysqlclient.hpp"

using namespace GNET;

MysqlManager *MysqlManager::instance = NULL;

MysqlManager::MysqlManager()
{
	pthread_mutex_init(&mysql_mutex, NULL);
	pthread_mutex_lock(&mysql_mutex);

	pthread_mutex_init(&query_mutex, NULL);
	active = false;
	DBMysql = NULL;
	address.clear();
	port = 0;
	user.clear();
	passwd.clear();
	dbname.clear();
	hash = 0;

	pthread_mutex_unlock(&mysql_mutex);
}

MysqlManager::~MysqlManager()
{
	pthread_mutex_lock(&mysql_mutex);

	active = false;
	delete DBMysql;
	DBMysql = NULL;
	address.clear();
	user.clear();
	passwd.clear();
	dbname.clear();
	pthread_mutex_destroy(&query_mutex);

	pthread_mutex_unlock(&mysql_mutex);
	pthread_mutex_destroy(&mysql_mutex);
}

void MysqlManager::Init(const char *_ip, const short _port, const char *_user, const char *_passwd, const char *_db, int _hash)
{
	pthread_mutex_lock(&mysql_mutex);

	address = _ip;
	port = _port;
	user = _user;
	passwd = _passwd;
	dbname = _db;
	hash = _hash;
	active = false;

	pthread_mutex_unlock(&mysql_mutex);
}

void MysqlManager::HeartBeat(size_t tick)
{
	if (!(tick % 60))
	{
		if (GiveUseCash())
		{
			ClearUseCash();
		}
	}
}

void MysqlManager::Lock()
{
	pthread_mutex_lock(&query_mutex);
}

void MysqlManager::Unlock()
{
	pthread_mutex_unlock(&query_mutex);
}

bool MysqlManager::Connect()
{
	bool res = false;
	pthread_mutex_lock(&mysql_mutex);

	DBMysql = mysql_init(NULL);
	if (DBMysql)
	{
		if (mysql_real_connect(DBMysql, address.c_str(), user.c_str(), passwd.c_str(), dbname.c_str(), port, 0, 0) && !CheckFailedExcept())
		{
			std::cout << "MysqlManager: AddSession " << std::endl;
			active = true;
			res = true;
		}
	}

	pthread_mutex_unlock(&mysql_mutex);
	return res;
}

bool MysqlManager::Disconnect()
{
	bool res = false;
	pthread_mutex_lock(&mysql_mutex);
	if (DBMysql)
	{
		mysql_close(DBMysql);
		DBMysql = NULL;
		active = false;
		res = true;
		std::cout << "MysqlManager: DelSession " << std::endl;
	}
	pthread_mutex_unlock(&mysql_mutex);
	return res;
}

bool MysqlManager::Reconnect()
{
	bool res = false;
	pthread_mutex_lock(&mysql_mutex);

	if (DBMysql)
	{
		mysql_close(DBMysql);
		DBMysql = NULL;
		active = false;
	}

	DBMysql = mysql_init(NULL);
	if (DBMysql)
	{
		if (mysql_real_connect(DBMysql, address.c_str(), user.c_str(), passwd.c_str(), dbname.c_str(), port, 0, 0) && !CheckFailedExcept())
		{
			active = true;
			res = true;
			std::cout << "MysqlManager: Reconnect --OK-- " << std::endl;
		}
		else
		{
			sleep(5);
			std::cout << "MysqlManager: Reconnecting ... " << std::endl;
			pthread_mutex_unlock(&mysql_mutex);
			Reconnect();
		}
	}

	pthread_mutex_unlock(&mysql_mutex);
	return res;
}

bool MysqlManager::CheckFailedExcept()
{
	bool res = false;
	if (DBMysql)
	{
		if (mysql_errno(DBMysql))
		{
			active == false;
			const char *err = mysql_error(DBMysql);
			if (err)
			{
				std::cout << "MysqlManager: error: " << err << std::endl;
			}
			res = true;
		}
	}
	else
	{
		active == false;
		std::cout << "MysqlManager: error: MYSQL == NULL " << std::endl;
		res = true;
	}
	return res;
}

bool MysqlManager::MysqlQuery(GSQL &iSQL, size_t iPos)
{
	bool res = false;

	if (DBMysql && active)
	{
		const char *str = (const char *)iSQL.istr[iPos].begin();
		size_t len = str && str[0] ? strlen(str) : 0;

		if (len && !mysql_real_query(DBMysql, str, len) && mysql_field_count(DBMysql) > 0)
		{
			MYSQL_RES *oSQL = mysql_store_result(DBMysql);
			if (oSQL)
			{
				int oCount = mysql_num_rows(oSQL);

				if (oCount > LEN__OUTPUT)
				{
					oCount = LEN__OUTPUT;
				}

				iSQL.ostr.clear();
				iSQL.ostr.resize(oCount);

				for (size_t i = 0; i < iSQL.ostr.size() && i < LEN__OUTPUT; i++)
				{
					MYSQL_ROW row = mysql_fetch_row(oSQL);
					int rCount = mysql_num_fields(oSQL);

					iSQL.ostr[i].iindex = iPos;
					iSQL.ostr[i].str.clear();
					iSQL.ostr[i].str.resize(rCount);

					for (size_t j = 0; j < iSQL.ostr[i].str.size() && j < LEN__ROW; j++)
					{
						if (row && row[j] && row[j][0])
						{
							iSQL.ostr[i].str[j].replace(row[j], (strlen(row[j]) + 1));
						}
						else
						{
							iSQL.ostr[i].str[j].replace("NULL", 5);
						}
					}
				}

				mysql_free_result(oSQL);
				res = true;
			}
		}
	}
	return res;
}

bool MysqlManager::MysqlSender(GSQL &iSQL)
{
	bool res = false;
	pthread_mutex_lock(&mysql_mutex);
	if (!CheckFailedExcept())
	{
		while (mysql_next_result(DBMysql) == 0)
			;
		for (size_t i = 0; i < iSQL.istr.size() && i < LEN__INPUT; i++)
		{
			MysqlQuery(iSQL, i);
		}

		// LOGS BRGIN
		printf("MYSQL::LOG:Input: INPUT_COUNT = %d \n", iSQL.istr.size());
		for (size_t i = 0; i < iSQL.istr.size() && i < LEN__INPUT; i++)
		{
			std::string iLog((const char *)iSQL.istr[i].begin(), iSQL.istr[i].size());
			printf("MYSQL::LOG:Input: iIndex = %d , str = %s \n", i, iLog.c_str());
		}

		printf("MYSQL::LOG:Input: ONPUT_COUNT = %d \n", iSQL.ostr.size());
		for (size_t i = 0; i < iSQL.ostr.size() && i < LEN__OUTPUT; i++)
		{
			printf("MYSQL::LOG:Input: ONPUT_POS = %d , ROW_COUNT = %d \n", i, iSQL.ostr[i].str.size());
			for (size_t j = 0; j < iSQL.ostr[i].str.size() && j < LEN__ROW; j++)
			{
				std::string iLog((const char *)iSQL.ostr[i].str[j].begin(), iSQL.ostr[i].str[j].size());
				printf("MYSQL::LOG:Output: iIndex = %d , oPos=%d , rPos=%d , str = %s \n", iSQL.ostr[i].iindex, i, j, iLog.c_str());
			}
		}
		// LOGS END

		res = true;
	}
	pthread_mutex_unlock(&mysql_mutex);
	if (!res)
	{
		Reconnect();
		res = MysqlSender(iSQL);
	}
	return res;
}

int MysqlManager::GetIndexPos(GSQL &iSQL, size_t iPos)
{
	for (size_t i = 0; i < iSQL.ostr.size() && i < LEN__OUTPUT; i++)
	{
		if (iPos == iSQL.ostr[i].iindex)
		{
			return i;
		}
	}
	return 0;
}

Octets &MysqlManager::GetRowOct(GSQL &iSQL, size_t iPos, size_t rPos)
{
	static Octets res;
	if (iPos < iSQL.ostr.size() && rPos < iSQL.ostr[iPos].str.size() && iSQL.ostr[iPos].str[rPos].size() > 1)
	{
		// iSQL.ostr[iPos].str[rPos].resize(iSQL.ostr[iPos].str[rPos].size()-1);
		return iSQL.ostr[iPos].str[rPos];
	}
	printf("MysqlManager::GetRowOct: iPos=%d , rPos=%d \n", iPos, rPos);
	res.clear();
	return res;
}

const char *MysqlManager::GetRowStr(GSQL &iSQL, size_t iPos, size_t rPos)
{
	if (iPos < iSQL.ostr.size() && rPos < iSQL.ostr[iPos].str.size())
	{
		return (const char *)iSQL.ostr[iPos].str[rPos].begin();
	}
	printf("MysqlManager::GetRowStr: iPos=%d , rPos=%d , oSize=%d , rSize=%d \n", iPos, rPos, iSQL.ostr.size(), iSQL.ostr[iPos].str.size());
	return NULL;
}

size_t MysqlManager::GetRowNum(GSQL &iSQL, size_t iPos, size_t rPos)
{
	const char *str = GetRowStr(iSQL, iPos, rPos);
	if (str && strlen(str) < 40)
	{
		return atoll(str);
	}
	printf("MysqlManager::GetRowNum: iPos=%d , rPos=%d , oSize=%d , rSize=%d \n", iPos, rPos, iSQL.ostr.size(), iSQL.ostr[iPos].str.size());
	return 0;
}

double MysqlManager::GetRowFlt(GSQL &iSQL, size_t iPos, size_t rPos)
{
	const char *str = GetRowStr(iSQL, iPos, rPos);
	if (str && strlen(str) < 40)
	{
		return atof(str);
	}
	printf("MysqlManager::GetRowFlt: iPos=%d , rPos=%d , oSize=%d , rSize=%d \n", iPos, rPos, iSQL.ostr.size(), iSQL.ostr[rPos].str.size());
	return 0.0f;
}

//-------------------[packets]--------------------

bool MysqlManager::MatrixPasswd(int &uid, Octets &identify, Octets &responce)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	GSQL iSQL(0, 0, 0);
	size_t SqlCount = 2;
	iSQL.istr.resize(SqlCount);

	size_t i = 0;
	std::string login((const char *)identify.begin(), identify.size());
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "CALL acquireuserpasswd('%s', @userid, @passwd)", login.c_str());
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "SELECT @userid, @passwd");

	if (MysqlSender(iSQL) && iSQL.ostr.size() >= 1)
	{
		size_t iIndex = GetIndexPos(iSQL, 1);

		if (iSQL.ostr[iIndex].str.size() >= 2)
		{
			uid = GetRowNum(iSQL, iIndex, 0);
			responce = GetRowOct(iSQL, iIndex, 1);
			responce.resize(responce.size() - 1);

			if (uid > 0 && responce.size() > 0)
			{
				res = true;
			}
		}
	}

	MYSQL_MUTEX_END
	return res;
}

bool MysqlManager::ClearOnlineRecord(unsigned int sid, int zid, int aid)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	GSQL iSQL(0, 0, 0);
	size_t SqlCount = 1;
	iSQL.istr.resize(SqlCount);

	size_t i = 0;
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "CALL clearonlinerecords(%d, %d)", zid, aid);

	if (MysqlSender(iSQL))
	{
		this->sid = sid;
		this->zoneid = zid;
		this->aid = aid;
		res = true;
	}

	MYSQL_MUTEX_END
	Log::log(LOG_INFO, "MysqlManager::ClearOnlineRecord: sid=%u , zid=%d , aid=%d , res=%d \n", sid, zid, aid, res);
	return res;
}

bool MysqlManager::OnlineRecord(int uid, int &zid, int &zonelocalid, int &overwrite)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	GSQL iSQL(0, uid, 0);
	size_t SqlCount = 3;
	iSQL.istr.resize(SqlCount);

	size_t i = 0;
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "SET @zoneid = %d, @aid = %d, @zonelocalid = %d", zid, aid, zonelocalid);
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "CALL recordonline(%d, %d, @zoneid, @zonelocalid, @overwrite)", uid, aid);
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "SELECT @zoneid, @zonelocalid, @overwrite");

	if (MysqlSender(iSQL) && iSQL.ostr.size() >= 1)
	{
		size_t iIndex = GetIndexPos(iSQL, 2);
		if (iSQL.ostr[iIndex].str.size() >= 3)
		{
			zid = GetRowNum(iSQL, iIndex, 0);
			zonelocalid = GetRowNum(iSQL, iIndex, 1);
			overwrite = GetRowNum(iSQL, iIndex, 2);
			res = true;
		}
	}

	MYSQL_MUTEX_END
	return res;
}

bool MysqlManager::OnfflineRecord(int uid, int &zonelocalid, int &overwrite)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	GSQL iSQL(0, uid, 0);
	size_t SqlCount = 3;
	iSQL.istr.resize(SqlCount);

	size_t i = 0;
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "SET @zoneid = %d, @aid = %d, @zonelocalid = %d", zoneid, aid, zonelocalid);
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "CALL recordoffline(%d, %d, @zoneid, @zonelocalid, @overwrite)", uid, aid);
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "SELECT @zoneid, @zonelocalid, @overwrite");

	if (MysqlSender(iSQL) && iSQL.ostr.size() >= 1)
	{
		size_t iIndex = GetIndexPos(iSQL, 2);
		if (iSQL.ostr[iIndex].str.size() >= 3)
		{
			// zid = GetRowNum(iSQL, iIndex, 0);
			zonelocalid = GetRowNum(iSQL, iIndex, 1);
			overwrite = GetRowNum(iSQL, iIndex, 2);
			res = true;
		}
	}

	MYSQL_MUTEX_END
	return res;
}

bool MysqlManager::UserCreatime(int uid, int &timestamp)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	GSQL iSQL(0, uid, 0);
	size_t SqlCount = 1;
	iSQL.istr.resize(SqlCount);

	size_t i = 0;
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "SELECT UNIX_TIMESTAMP(creatime) FROM users WHERE ID=%d", uid);

	if (MysqlSender(iSQL) && iSQL.ostr.size() >= 1 && iSQL.ostr[0].str.size() >= 1)
	{
		timestamp = GetRowNum(iSQL, 0, 0);
		res = true;
	}

	MYSQL_MUTEX_END
	return res;
}

bool MysqlManager::UserGMPrivilege(int uid, int zid, bool &IsGM)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	GSQL iSQL(0, uid, 0);
	size_t SqlCount = 1;
	iSQL.istr.resize(SqlCount);

	size_t i = 0;
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "SELECT rid FROM auth WHERE userid=%d AND zoneid=%d", uid, zid);

	if (MysqlSender(iSQL) && iSQL.ostr.size() >= 1 && iSQL.ostr[0].str.size() >= 1)
	{
		IsGM = true;
		res = true;
	}

	MYSQL_MUTEX_END
	return res;
}

bool MysqlManager::QueryGMPrivilege(int uid, int zid, ByteVector &auth)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	GSQL iSQL(0, uid, 0);
	size_t SqlCount = 1;
	iSQL.istr.resize(SqlCount);

	size_t i = 0;
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "SELECT rid FROM auth WHERE userid=%d AND zoneid=%d", uid, zid);

	if (MysqlSender(iSQL))
	{
		for (size_t j = 0; j < iSQL.ostr.size() && j < LEN__OUTPUT; j++)
		{
			if (iSQL.ostr[j].str.size() >= 1)
			{
				auth.add(GetRowNum(iSQL, j, 0));
			}
		}
	}

	MYSQL_MUTEX_END
	return res;
}

//---------------------------------------------------------------------------------

bool MysqlManager::ClearUseCash()
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	GSQL iSQL(0, 0, 0);
	size_t SqlCount = 1;
	iSQL.istr.resize(SqlCount);

	size_t i = 0;
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "DELETE FROM usecashnow WHERE status=%d", 0);

	if (MysqlSender(iSQL))
	{
		res = true;
	}

	MYSQL_MUTEX_END
	return res;
}

bool MysqlManager::GiveUseCash()
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	GSQL iSQL(0, 0, 0);
	size_t SqlCount = 1;
	iSQL.istr.resize(SqlCount);
	GAuthServer *aum = GAuthServer::GetInstance();

	size_t i = 0;
	sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(), "SELECT userid, zoneid, sn, aid, point, cash, status, UNIX_TIMESTAMP(creatime) FROM usecashnow WHERE status=%d", 0);

	if (MysqlSender(iSQL) && iSQL.ostr.size())
	{
		size_t oSize = iSQL.ostr.size();

		if (oSize > LEN__OUTPUT)
		{
			oSize = LEN__OUTPUT;
		}

		usecashnow.clear();
		usecashnow.resize(oSize);

		for (size_t j = 0; j < oSize && j < LEN__OUTPUT; j++)
		{
			if (iSQL.ostr[j].str.size() >= 8)
			{
				usecashnow[j].userid = GetRowNum(iSQL, j, 0);
				usecashnow[j].zoneid = GetRowNum(iSQL, j, 1);
				usecashnow[j].sn = GetRowNum(iSQL, j, 2);
				usecashnow[j].aid = GetRowNum(iSQL, j, 3);
				usecashnow[j].point = GetRowNum(iSQL, j, 4);
				usecashnow[j].cash = GetRowNum(iSQL, j, 5);
				usecashnow[j].status = GetRowNum(iSQL, j, 6);
				usecashnow[j].creatime = GetRowNum(iSQL, j, 7);

				int _uid = usecashnow[j].userid;
				int _zid = usecashnow[j].zoneid;
				int _cash = usecashnow[j].cash;

				if (_uid > 0 && _cash && _zid == zoneid)
				{
					GetAddCashSN CashSN = GetAddCashSN(RPC_GETADDCASHSN, NULL, NULL);
					GetAddCashSNArg arg;
					arg.userid = _uid;
					arg.zoneid = _zid;
					CashSN.SetArgument(arg);
					CashSN.SendToSponsor();
					CashSN.Process(aum, sid);
				}
			}
		}

		res = true;
	}

	MYSQL_MUTEX_END
	return res;
}

bool MysqlManager::UpdateUseCashSN(int uid, int zid, int sn, int &cash)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	for (size_t j = 0; j < usecashnow.size() && j < LEN__OUTPUT; j++)
	{
		if (usecashnow[j].userid == uid && usecashnow[j].zoneid == zid && usecashnow[j].cash && usecashnow[j].status == 0)
		{
			usecashnow[j].status = 1;
			usecashnow[j].sn = sn;
			cash = usecashnow[j].cash;
			res = true;
			break;
		}
	}

	MYSQL_MUTEX_END
	return res;
}

bool MysqlManager::AddCashLog(int uid, int zid, int sn)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	for (size_t j = 0; j < usecashnow.size() && j < LEN__OUTPUT; j++)
	{
		if (usecashnow[j].userid == uid && usecashnow[j].zoneid == zid && usecashnow[j].status == 1 && usecashnow[j].sn == sn)
		{
			size_t i = 0;
			GSQL iSQL(0, uid, 0);
			size_t SqlCount = 1;
			iSQL.istr.resize(SqlCount);

			sprintf((char *)iSQL.istr[i++].resize(LEN__QUERY).begin(),
					"REPLACE INTO usecashlog VALUES (%d, %d, %d, %d, %d, %d, %d, creatime=FROM_UNIXTIME(%d), NOW())",
					usecashnow[j].userid,
					usecashnow[j].zoneid,
					usecashnow[j].sn,
					usecashnow[j].aid,
					usecashnow[j].point,
					usecashnow[j].cash,
					usecashnow[j].status,
					usecashnow[j].creatime);

			if (MysqlSender(iSQL))
			{
				res = true;
			}

			// printf("MysqlManager::AddCashLog: pos=%d \n", j);

			break;
		}
	}

	MYSQL_MUTEX_END
	return res;
}

bool MysqlManager::PanelQuery(GSQL &iSQL)
{
	bool res = false;
	MYSQL_MUTEX_BEGIN

	if (MysqlSender(iSQL))
	{
		res = true;
	}

	MYSQL_MUTEX_END
	return res;
}

//-----------------------------------------------------------------------------//
//-------------------------------[MYSQL_TIMER]---------------------------------//
//-----------------------------------------------------------------------------//

void MysqlTimer::UpdateTimer()
{
	static size_t tick = 0;
	tick++;
	MysqlManager::GetInstance()->HeartBeat(tick);
}

void MysqlTimer::Run()
{
	UpdateTimer();
	Thread::HouseKeeper::AddTimerTask(this, update_time);
}
