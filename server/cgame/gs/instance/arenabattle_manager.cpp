#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <strings.h>
#include <ASSERT.h>
#include <threadpool.h>
#include <conf.h>
#include <io/pollio.h>
#include <io/passiveio.h>
#include <gsp_if.h>
#include <db_if.h>
#include <amemory.h>
#include <meminfo.h>
#include <strtok.h>
#include <glog.h>

#include "../template/itemdataman.h"
#include "../template/npcgendata.h"
#include "../world.h"
#include "../player_imp.h"
#include "../npc.h"
#include "../matter.h"
#include "../playertemplate.h"
#include "instance_config.h"
#include "arenabattle_manager.h"
#include "../pathfinding/pathfinding.h"
#include "../template/globaldataman.h"
#include "arenabattle_ctrl.h"
#include "../obj_interface.h"
#include <factionlib.h>
#include "../aei_filter.h"

world *
arenabattle_world_manager::CreateWorldTemplate()
{
	world *pPlane = new world;
	pPlane->Init(_world_index);
	pPlane->InitManager(this);

	pPlane->SetWorldCtrl(new arenabattle_ctrl());
	return pPlane;
}

world_message_handler *
arenabattle_world_manager::CreateMessageHandler()
{
	return new arenabattle_world_message_handler(this);
}

void arenabattle_world_manager::Heartbeat()
{
	_msg_queue.OnTimer(0, 100);
	world_manager::Heartbeat();
	size_t ins_count = _max_active_index;
	for (size_t i = 0; i < ins_count; i++)
	{
		if (_planes_state[i] == 0)
		{
			continue;
		}
		world *pPlane = _cur_planes[i];
		if (!pPlane)
			continue;
		pPlane->RunTick();
	}

	mutex_spinlock(&_heartbeat_lock);

	if ((++_heartbeat_counter) > TICK_PER_SEC * HEARTBEAT_CHECK_INTERVAL)
	{
		//每10秒检验一次
		//这里进行超时时间的处理
		for (size_t i = 0; i < ins_count; i++)
		{
			if (_planes_state[i] == 0)
				continue; //空世界
			world *pPlane = _cur_planes[i];
			if (!pPlane)
				continue;
			if (pPlane->w_obsolete)
			{
				//处于等待废除状态
				if (pPlane->w_player_count)
				{
					pPlane->w_obsolete = 0;
				}
				else
				{
					if (pPlane->w_destroy_timestamp <= g_timer.get_systime())
					{
						//没有玩家保持了20分钟则应该将这个world回归到空闲中
						FreeWorld(pPlane, i);
					}
				}
			}
			else
			{
				if (!pPlane->w_player_count)
				{
					pPlane->w_obsolete = 1;
				}
			}
		}
		_heartbeat_counter = 0;

		//进行冷却列表的处理 永远都不回收世界
		RegroupCoolDownWorld();
	}

	if ((++_heartbeat_counter2) > TICK_PER_SEC * HEARTBEAT_CHECK_INTERVAL)
	{
		//如果世界池的容量不足，则进行重新创建处理
		FillWorldPool();

		_heartbeat_counter2 = 0;
	}

	mutex_spinunlock(&_heartbeat_lock);
}

bool arenabattle_world_manager::InitNetClient(const char *gmconf)
{
	return instance_world_manager::InitNetClient(gmconf);
}

void arenabattle_world_manager::OnDeliveryConnected()
{
	GMSV::SendArenaBattleServerRegister(1, GetWorldIndex(), GetWorldTag(), BATTLE_TYPE_DUEL);
	return;
}

void arenabattle_world_manager::PreInit(const char *servername)
{
	std::string section = "Instance_";
	section += servername;
	Conf *conf = Conf::GetInstance();
}

void arenabattle_world_manager::FinalInit(const char *servername)
{
	// Load XYZ Position Spawn

	std::string section = "Instance_";
	section += servername;
	Conf *conf = Conf::GetInstance();

	std::string str = conf->find(section, "teamApos");
	{
		A3DVECTOR pos;
		sscanf(str.c_str(), "%f,%f,%f", &pos.x, &pos.y, &pos.z);
		town_entry entry = {FACTION_OFFENSE_FRIEND | FACTION_BATTLEOFFENSE, pos};
		_town_list.push_back(entry);
	}

	str = conf->find(section, "teamBpos");
	{
		A3DVECTOR pos;
		sscanf(str.c_str(), "%f,%f,%f", &pos.x, &pos.y, &pos.z);
		town_entry entry = {FACTION_BATTLEDEFENCE | FACTION_DEFENCE_FRIEND, pos};
		_town_list.push_back(entry);
	}

	str = conf->find(section, "triggerid");
	{
		sscanf(str.c_str(), "%d", &triggerid);
	}
}

bool arenabattle_world_manager::CreateArenaBattle(const arenabattle_param &param)
{
	//首先取得或者创建一个世界
	spin_autolock keeper(_key_lock);
	instance_hash_key hkey;
	hkey.key1 = param.battle_id;
	hkey.key2 = 0;
	int world_index;
	world *pPlane = AllocWorldWithoutLock(hkey, world_index);

	if (pPlane == NULL)
	{
		return false;
	}

	arenabattle_ctrl *pCtrl = dynamic_cast<arenabattle_ctrl *>(pPlane->w_ctrl);
	if (pCtrl == NULL)
	{
		ASSERT(false);
		return false;
	}

	if (pCtrl->_data.battle_id != 0)
	{
		return false;
	}

	pCtrl->_data.battle_id = param.battle_id;
	pCtrl->_data.attacker_count = 0;
	pCtrl->_data.defender_count = 0;
	pCtrl->_data.player_count_limit = param.player_count;
	pCtrl->_data.faction_attacker = param.attacker;
	pCtrl->_data.faction_defender = param.defender;
	pCtrl->_data.start_timestamp = param.start_timestamp;
	pCtrl->_data.end_timestamp = param.end_timestamp;
	pCtrl->_data.battle_mode = param.battle_mode;
	pCtrl->_data.battle_type = param.battle_type;
	pCtrl->_data.triggerid = triggerid;

	return true;
}

/**/
void arenabattle_world_manager::UserLogin(int cs_index, int cs_sid, int uid, const void *auth_data, size_t auth_size, bool isshielduser, char flag, int vip)
{
	GMSV::SendLoginRe(cs_index, uid, cs_sid, 3, flag); // login failed
}

void arenabattle_world_manager::SetFilterWhenLogin(gplayer_imp *pImp, instance_key *ikey)
{
	pImp->_filters.AddFilter(new aei_arena_filter(pImp, FILTER_CHECK_INSTANCE_KEY, ikey->target.key_level4));
}

void arenabattle_world_manager::GetLogoutPos(gplayer_imp *pImp, int &world_tag, A3DVECTOR &pos)
{
	//这里应该用动态的savepoint 创建世界时需要指定这些数据
	pImp->GetLastInstanceSourcePos(world_tag, pos);
	if (world_tag != 1)
	{
		//这样真的好？ 不过也没办法， 不然出错了怎么办？
		world_tag = 1;
		pos = A3DVECTOR(320, 0, 3200);
	}
}

world *
arenabattle_world_manager::GetWorldInSwitch(const instance_hash_key &ikey, int &world_index, int)
{
	spin_autolock keeper(_key_lock);
	world *pPlane = NULL;
	int *pTmp = _key_map.nGet(ikey);
	world_index = -1;
	if (pTmp)
	{
		//存在这样的世界
		world_index = *pTmp;
		;
		pPlane = _cur_planes[world_index];
		ASSERT(pPlane);

		//这里要检查世界是否允许登入  若不允许登录,则直接返回NULL
		//$$$$$$

		pPlane->w_obsolete = 0;
	}
	if (world_index < 0)
		return NULL;
	return pPlane;
}

int arenabattle_world_manager::CheckPlayerSwitchRequest(const XID &who, const instance_key *ikey, const A3DVECTOR &pos, int ins_timer)
{
	if (ikey->target.key_level4 == 0)
	{
		return S2C::ERR_CANNOT_ENTER_INSTANCE;
	}

	instance_hash_key key;
	TransformInstanceKey(ikey->target, key);
	world *pPlane = NULL;
	int rst = 0;
	mutex_spinlock(&_key_lock);
	int *pTmp = _key_map.nGet(key);
	if (!pTmp)
	{
		mutex_spinunlock(&_key_lock);
		return S2C::ERR_BATTLEFIELD_IS_CLOSED;
	}
	pPlane = _cur_planes[*pTmp];
	if (pPlane)
	{
		if (pPlane->w_player_count >= _player_limit_per_instance)
		{
			//检查基础人数上限
			rst = S2C::ERR_TOO_MANY_PLAYER_IN_INSTANCE;
		}
		else
		{
			arenabattle_ctrl *pCtrl = (arenabattle_ctrl *)pPlane->w_ctrl;

			//检查人数是否已经满了
			if (pCtrl->_data.attacker_count >= (pCtrl->_data.player_count_limit / 2) && pCtrl->_data.defender_count >= (pCtrl->_data.player_count_limit / 2))
			{
				rst = S2C::ERR_TOO_MANY_PLAYER_IN_INSTANCE;
			}

			if (!rst)
			{
				//检查世界是否已经即将关闭
				if (pCtrl->_data.end_timestamp <= g_timer.get_systime())
				{
					rst = S2C::ERR_BATTLEFIELD_IS_CLOSED;
				}
				else if (pPlane->w_battle_result)
				{
					rst = S2C::ERR_BATTLEFIELD_IS_FINISHED;
				}
			}
		}
	}
	else
	{
		rst = S2C::ERR_CANNOT_ENTER_INSTANCE;
	}

	//检查玩家的人数， 状态和其他数据是否匹配
	mutex_spinunlock(&_key_lock);
	return rst;
}

bool arenabattle_world_manager::GetTown(int faction, A3DVECTOR &pos, int &tag)
{
	int list[64];
	int counter = 0;
	for (size_t i = 0; i < _town_list.size() && counter < 64; i++)
	{
		if (_town_list[i].faction & faction)
		{
			list[counter] = i;
			counter++;
		}
	}
	if (counter > 0)
	{
		int index = abase::Rand(0, counter - 1);
		pos = _town_list[list[index]].target_pos;
		tag = GetWorldTag();
		return true;
	}
	return false;
}

bool arenabattle_world_manager::GetTownPosition(gplayer_imp *pImp, const A3DVECTOR &opos, A3DVECTOR &pos, int &tag)
{
	int faction = 0;
	if (((gplayer *)(pImp->_parent))->IsBattleOffense())
	{
		faction = FACTION_OFFENSE_FRIEND | FACTION_BATTLEOFFENSE;
	}
	else
	{
		faction = FACTION_BATTLEDEFENCE | FACTION_DEFENCE_FRIEND;
	}
	return GetTown(faction, pos, tag);
}

void arenabattle_world_manager::SetIncomingPlayerPos(gplayer *pPlayer, const A3DVECTOR &origin_pos, int special_mask)
{
	world *pPlane = pPlayer->imp->_plane;

	arenabattle_ctrl *pCtrl = (arenabattle_ctrl *)(pPlane->w_ctrl);

	gplayer_imp *gpimp = pPlayer->imp;

	int faction = 0;
	int id = gpimp->_arena_team_id;
	if (id)
	{
		if (id == pCtrl->_data.faction_attacker)
		{
			faction = FACTION_OFFENSE_FRIEND | FACTION_BATTLEOFFENSE;
			pPlayer->SetBattleOffense();
		}
		else if (id == pCtrl->_data.faction_defender)
		{
			faction = FACTION_BATTLEDEFENCE | FACTION_DEFENCE_FRIEND;
			pPlayer->SetBattleDefence();
		}
	}

	if (faction)
	{
		int tag;
		if (GetTown(faction, pPlayer->pos, tag))
			return;
	}

	instance_world_manager::SetIncomingPlayerPos(pPlayer, origin_pos, special_mask);
}