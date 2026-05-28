
#ifndef __GNET_ARENABATTLESTART_HPP
#define __GNET_ARENABATTLESTART_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"

void arena_battleground_start(int battle_id, int map_tag, int battle_type, int defender, int attacker, int max_player_cnt, int start_time, int end_time);
namespace GNET
{

	class ArenaBattleStart : public GNET::Protocol
	{
#include "arenabattlestart"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			arena_battleground_start(battle_id, map_tag, battle_type, defender, attacker, max_player_cnt, start_time, end_time);
		}
	};

};

#endif
