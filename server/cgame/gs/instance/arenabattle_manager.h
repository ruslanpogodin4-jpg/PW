#ifndef __ONLINEGAME_GS_ARENABATTLE_MANAGER_H__
#define __ONLINEGAME_GS_ARENABATTLE_MANAGER_H__

#include "instance_manager.h"
#include "arenabattle_ctrl.h"

struct arenabattle_param
{
	int battle_id;
	int attacker;		 //攻击方国家
	int defender;		 //防守方国家
	int player_count;	 //每方玩家限制的人数
	int start_timestamp; //结束时间
	int end_timestamp;	 //结束时间
	int battle_type;	 //攻击方国家总人数
	int battle_mode;	 //攻击方国家总人数
};

/*------------------------战场副本管理-------------------------------*/
class arenabattle_world_manager : public instance_world_manager
{
	virtual void UserLogin(int cs_index, int cs_sid, int uid, const void *auth_data, size_t auth_size, bool isshielduser, char flag, int vip);
	virtual void SetFilterWhenLogin(gplayer_imp *pImp, instance_key *ikey);
	virtual void GetLogoutPos(gplayer_imp *pImp, int &world_tag, A3DVECTOR &pos);
	virtual bool InitNetClient(const char *gmconf);
	virtual void FinalInit(const char *servername);
	virtual void PreInit(const char *servername);
	virtual void OnDeliveryConnected();
	struct town_entry
	{
		int faction;
		A3DVECTOR target_pos;
	};

	abase::vector<town_entry> _town_list;
	int _win_condition;
	int _player_count_limit;
	int triggerid;
	bool GetTown(int faction, A3DVECTOR &pos, int &tag);

public:
	enum
	{
		BATTLE_TYPE_DUEL = 0,
	};

	enum
	{
		TEAM_A,
		TEAM_B,
	};

	typedef arenabattle_ctrl::map_data map_data;

protected:
	map_data _defence_data;
	map_data _offense_data;

public:
	arenabattle_world_manager() : instance_world_manager()
	{
		//战场副本应该是固定时间清除
		_idle_time = 300;
		_life_time = -1;
		triggerid = 0;
		memset(&_defence_data, 0, sizeof(map_data));
		memset(&_offense_data, 0, sizeof(map_data));
	}
	virtual int GetWorldType() { return WORLD_TYPE_ARENABATTLE; }
	virtual void TransformInstanceKey(const instance_key::key_essence &key, instance_hash_key &hkey)
	{
		hkey.key1 = key.key_level4;
		hkey.key2 = 0;
	}

	virtual int CheckPlayerSwitchRequest(const XID &who, const instance_key *key, const A3DVECTOR &pos, int ins_timer);
	virtual bool IsBattleWorld() { return true; }
	virtual bool CreateArenaBattle(const arenabattle_param &);
	virtual world *CreateWorldTemplate();
	virtual world_message_handler *CreateMessageHandler();
	virtual void Heartbeat();
	world *GetWorldInSwitch(const instance_hash_key &ikey, int &world_index, int);
	virtual bool GetTownPosition(gplayer_imp *pImp, const A3DVECTOR &opos, A3DVECTOR &pos, int &tag);
	virtual void SetIncomingPlayerPos(gplayer *pPlayer, const A3DVECTOR &origin_pos, int special_mask);
};

class arenabattle_world_message_handler : public instance_world_message_handler
{
protected:
	virtual ~arenabattle_world_message_handler() {}

	virtual void PlayerPreEnterServer(gplayer *pPlayer, gplayer_imp *pimp, instance_key &ikey); //在调用EnterWorld之前的处理
public:
	arenabattle_world_message_handler(instance_world_manager *man) : instance_world_message_handler(man) {}
	virtual int HandleMessage(world *pPlane, const MSG &msg);
	virtual int RecvExternMessage(int msg_tag, const MSG &msg);
};

#endif
