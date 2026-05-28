#include "arenabattle_ctrl.h"
#include "../faction.h"
#include "../obj_interface.h"
#include <gsp_if.h>
#include <factionlib.h>
#include "../player_imp.h"

void arenabattle_ctrl::PlayerEnter(gplayer *pPlayer, int type)
{
	spin_autolock keeper(_user_list_lock);
	AddMapNode(_all_list, pPlayer);
	if (type & 0x01)
	{
		// attacker
		_attacker_player_list.insert({pPlayer->ID.id, pPlayer});
		AddMapNode(_attacker_list, pPlayer);
	}
	else if (type & 0x02)
	{
		// defender
		_defender_player_list.insert({pPlayer->ID.id, pPlayer});
		AddMapNode(_defender_list, pPlayer);
	}
	SubscribePlayers();
}

void arenabattle_ctrl::PlayerLeave(gplayer *pPlayer, int type)
{
	spin_autolock keeper(_user_list_lock);
	DelMapNode(_all_list, pPlayer);
	if (type & 0x01)
	{
		// attacker
		_attacker_player_list.erase(pPlayer->ID.id);
		DelMapNode(_attacker_list, pPlayer);
	}
	else if (type & 0x02)
	{
		// defender
		_defender_player_list.erase(pPlayer->ID.id);
		DelMapNode(_defender_list, pPlayer);
	}
}

void arenabattle_ctrl::Tick(world *pPlane)
{
	spin_autolock l(_lock);
	if ((++_tick_counter) % 20 != 0)
		return;

	_data._state_timeout--;
	if (_data._state_timeout <= 0 && _data._arena_state != ARENA_FINISH)
	{
		_data._arena_state++;
		_data._state_timeout = GetStageTime(_data._arena_state);
		NextStageAction(pPlane);
	}
	CheckBattleResult(pPlane);
}

void arenabattle_ctrl::NextStageAction(world *pPlane)
{
	switch (_data._arena_state)
	{
	case ARENA_PREPARE_1:
		SubscribePlayers();
		break;
	case ARENA_ROUND_FIGHT:
		pPlane->ClearSpawn(_data.triggerid); // trigger ID
		break;
	case ARENA_ROUND_REST:
		// Stop attack
		break;
	case ARENA_FINISH:
		// Close instance
		break;
	}
}

void arenabattle_ctrl::SubscribePlayers()
{

	PLAYER_LIST::iterator it = _attacker_player_list.begin();
	while (it != _attacker_player_list.end())
	{
		gplayer *attacker = it->second;

		PLAYER_LIST::iterator it2 = _defender_player_list.begin();
		while (it2 != _defender_player_list.end())
		{
			gplayer *defender = it2->second;

			link_sid ld;
			ld.cs_id = attacker->cs_index;
			ld.cs_sid = attacker->cs_sid;
			ld.user_id = attacker->ID.id;
			((gactive_imp *)attacker->imp)->SendTo<0>(GM_MSG_SUBSCIBE_ENEMY, defender->ID, 0, &ld, sizeof(ld));
			++it2;
		}
		++it;
	}

	it = _defender_player_list.begin();
	while (it != _defender_player_list.end())
	{
		gplayer *defender = it->second;

		PLAYER_LIST::iterator it2 = _attacker_player_list.begin();
		while (it2 != _attacker_player_list.end())
		{
			gplayer *attacker = it2->second;

			link_sid ld;
			ld.cs_id = defender->cs_index;
			ld.cs_sid = defender->cs_sid;
			ld.user_id = defender->ID.id;
			((gactive_imp *)defender->imp)->SendTo<0>(GM_MSG_SUBSCIBE_ENEMY, attacker->ID, 0, &ld, sizeof(ld));
			++it2;
		}
		++it;
	}
}

void arenabattle_ctrl::CheckBattleResult(world *pPlane)
{
	bool peace = false;

	if (_battle_result)
		return;

	if (_battle_result == PEACE && _data._arena_state == ARENA_ROUND_REST)
		peace = true;

	if (peace)
	{
		_battle_result = DRAW_GAME;
		_winner_faction = BR_TIMEOUT;
	}

	if (_data._arena_state == ARENA_ROUND_FIGHT)
	{

		if (_offense_data.goal_count == 0 || _defence_data.goal_count == 0)
		{
			_battle_result = DRAW_GAME;
			_winner_faction = BR_TIMEOUT;
		}

		if (!_battle_result && _offense_data.death_count >= _offense_data.goal_count)
		{
			_battle_result = WIN_LOSE;
			_winner_faction = BR_WINNER_OFFENSE;
		}

		if (!_battle_result && _defence_data.death_count >= _defence_data.goal_count)
		{
			_battle_result = WIN_LOSE;
			_winner_faction = BR_WINNER_DEFENCE;
		}
	}

	if (_battle_result)
		BattleEnd(pPlane);
}

void arenabattle_ctrl::OnPlayerDeath(gplayer *pPlayer, const XID &killer, int player_soulpower, const A3DVECTOR &pos)
{
	if (pPlayer->IsBattleOffense())
	{
		interlocked_increment(&_defence_data.death_count);
	}
	else
	{
		interlocked_increment(&_offense_data.death_count);
	}
}

void arenabattle_ctrl::BattleEnd(world *pPlane)
{
	std::map<int, gplayer *>::iterator it = _attacker_player_list.begin();
	while (it != _attacker_player_list.end())
	{
		gplayer_imp *playerimp = (gplayer_imp *)it->second->imp;
		if (_winner_faction == BR_WINNER_OFFENSE)
		{
			playerimp->_arenastats_info._total_victory++;
			playerimp->_arenastats_info._arena_rank += 5;
		}
		else if (_winner_faction == BR_WINNER_DEFENCE)
		{
			playerimp->_arenastats_info._total_defeat++;
			playerimp->_arenastats_info._arena_rank -= 5;
			if (playerimp->_arenastats_info._arena_rank < 0)
			{
				playerimp->_arenastats_info._arena_rank = 0;
			}
		}
		it++;
	}

	it = _defender_player_list.begin();
	while (it != _defender_player_list.end())
	{
		gplayer_imp *playerimp = (gplayer_imp *)it->second->imp;
		if (_winner_faction == BR_WINNER_DEFENCE)
		{
			playerimp->_arenastats_info._total_victory++;
			playerimp->_arenastats_info._arena_rank += 5;
		}
		else if (_winner_faction == BR_WINNER_OFFENSE)
		{
			playerimp->_arenastats_info._total_defeat++;
			playerimp->_arenastats_info._arena_rank -= 5;
			if (playerimp->_arenastats_info._arena_rank < 0)
			{
				playerimp->_arenastats_info._arena_rank = 0;
			}
		}
		it++;
	}

	GMSV::SendArenaBattleEnd(_data.battle_id, _battle_result, _winner_faction);

	//设置结束时间
	pPlane->w_end_timestamp = g_timer.get_systime() + 130;
	pPlane->w_destroy_timestamp = pPlane->w_end_timestamp + 150;

	//同步战斗结果至world 中 这步必须最后完成
	pPlane->w_battle_result = _battle_result;
}