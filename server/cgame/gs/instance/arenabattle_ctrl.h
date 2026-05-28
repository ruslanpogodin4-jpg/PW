#ifndef __ONLINEGAME_GS_ARENABATTLE_CONTROL_H__
#define __ONLINEGAME_GS_ARENABATTLE_CONTROL_H__

#include "../world.h"
#include <interlocked.h>
#include <vector.h>
#include "../usermsg.h"
enum
{
	ARENA_PREPARE,	   //总战斗开始准备
	ARENA_PREPARE_1,   //总战斗开始准备1
	ARENA_ROUND_FIGHT, //单局战斗中
	ARENA_ROUND_REST,  //局间休息恢复
	ARENA_FINISH,	   //总战斗结束
};
enum
{
	PEACE,
	WIN_LOSE,
	DRAW_GAME
};

class arenabattle_ctrl : public world_data_ctrl
{
public:
	struct
	{
		int battle_id;
		int battle_type;
		int battle_mode;
		int faction_attacker;
		int faction_defender;
		int attacker_count;
		int defender_count;

		int player_count_limit;

		int start_timestamp; //结束时间
		int end_timestamp;	 //结束时间
		char _arena_state;
		int _state_timeout;
		int triggerid;
	} _data;

	struct map_data
	{
		int death_count;
		int goal_count;
		int dmg_count;
	};

	map_data _defence_data;
	map_data _offense_data;
	int _battle_result;
	int _winner_faction; // 攻击方攻防等级奖励

	inline int GetStageTime(int state)
	{
		switch (state)
		{
		case ARENA_PREPARE:
			return 75;
		case ARENA_PREPARE_1:
			return 10;
		case ARENA_ROUND_FIGHT:
			return 600;
		case ARENA_ROUND_REST:
			return 40;
		case ARENA_FINISH:
			return 45;
		}
		return 0;
	}

	enum
	{
		BR_NULL = 0,
		BR_WINNER_OFFENSE,
		BR_WINNER_DEFENCE,
		BR_TIMEOUT,
	};

public:
	typedef std::map<int, gplayer *> PLAYER_LIST;

	PLAYER_LIST _attacker_player_list;
	PLAYER_LIST _defender_player_list;
	cs_user_map _attacker_list;
	cs_user_map _defender_list;
	cs_user_map _all_list;
	int _user_list_lock;
	int _lock;
	int _tick_counter;

	inline void AddMapNode(cs_user_map &map, gplayer *pPlayer)
	{
		int cs_index = pPlayer->cs_index;
		std::pair<int, int> val(pPlayer->ID.id, pPlayer->cs_sid);
		if (cs_index >= 0 && val.first >= 0)
		{
			map[cs_index].push_back(val);
		}
	}

	inline void DelMapNode(cs_user_map &map, gplayer *pPlayer)
	{
		int cs_index = pPlayer->cs_index;
		std::pair<int, int> val(pPlayer->ID.id, pPlayer->cs_sid);
		if (cs_index >= 0 && val.first >= 0)
		{
			cs_user_list &list = map[cs_index];
			int id = pPlayer->ID.id;
			for (size_t i = 0; i < list.size(); i++)
			{
				if (list[i].first == id)
				{
					list.erase(list.begin() + i);
					i--;
				}
			}
		}
	}

public:
	bool AddAttacker()
	{
		if (_data.attacker_count >= _data.player_count_limit)
			return false;

		interlocked_increment(&_defence_data.goal_count);
		int p = interlocked_increment(&_data.attacker_count);
		if (p > _data.player_count_limit)
		{
			interlocked_decrement(&_data.attacker_count);
			return false;
		}
		else
		{
			return true;
		}
	}

	bool AddDefender()
	{
		if (_data.defender_count >= _data.player_count_limit)
			return false;
		interlocked_increment(&_offense_data.goal_count);
		int p = interlocked_increment(&_data.defender_count);
		if (p > _data.player_count_limit)
		{
			interlocked_decrement(&_data.defender_count);
			return false;
		}
		else
		{
			return true;
		}
	}

	void DelAttacker(bool dead)
	{
		if (!dead)
		{
			interlocked_decrement(&_defence_data.goal_count);
		}
		interlocked_decrement(&_data.attacker_count);
	}

	void DelDefender(bool dead)
	{
		if (!dead)
		{
			interlocked_decrement(&_offense_data.goal_count);
		}
		interlocked_decrement(&_data.defender_count);
	}

	void PlayerEnter(gplayer *pPlayer, int mask); // MASK: 1 attacker, 2 defneder
	void PlayerLeave(gplayer *pPlayer, int mask); // MASK: 1 attacker, 2 defneder
	void NextStageAction(world *pPlane);
	void SubscribePlayers();
	void CheckBattleResult(world *pPlane);
	void BattleEnd(world *pPlane);
	virtual void OnPlayerDeath(gplayer *pPlayer, const XID &killer, int player_soulpower, const A3DVECTOR &pos);
	virtual void Tick(world *pPlane);

public:
	arenabattle_ctrl() : _battle_result(0), _lock(0), _tick_counter(0), _attacker_list(), _defender_list(), _all_list()
	{
		_user_list_lock = 0;
		memset(&_data, 0, sizeof(_data));
		memset(&_defence_data, 0, sizeof(_defence_data));
		memset(&_offense_data, 0, sizeof(_offense_data));
		_data._arena_state = ARENA_PREPARE;
		_data._state_timeout = GetStageTime(ARENA_PREPARE);
	}

	virtual world_data_ctrl *Clone()
	{
		return new arenabattle_ctrl(*this);
	}

	virtual void Reset()
	{
		memset(&_data, 0, sizeof(_data));
		memset(&_defence_data, 0, sizeof(_defence_data));
		memset(&_offense_data, 0, sizeof(_offense_data));
		_battle_result = PEACE;
		_winner_faction = BR_NULL;
	}
};

#endif
