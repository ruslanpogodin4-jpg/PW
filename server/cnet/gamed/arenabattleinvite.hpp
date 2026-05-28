
#ifndef __GNET_ARENABATTLEINVITE_HPP
#define __GNET_ARENABATTLEINVITE_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"

void player_invite_arenabattle(int role_id, int team_id, int battle_id, int world_tag);
namespace GNET
{

	class ArenaBattleInvite : public GNET::Protocol
	{
#include "arenabattleinvite"

		class PlayerInviteArenaTask : public Thread::Runnable
		{
			int _roleid;
			int _battle_id;
			int _team_id;
			int _world_tag;

		public:
			PlayerInviteArenaTask(int roleid, int battle_id, int team_id, int world_tag) : _roleid(roleid), _battle_id(battle_id), _team_id(team_id), _world_tag(world_tag) {}
			void Run()
			{
				player_invite_arenabattle(_roleid, _team_id, _battle_id, _world_tag);
				delete this;
			}
		};

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			Thread::Runnable *task = new PlayerInviteArenaTask(role_id, battle_id, team_id, world_tag);
			Thread::Pool::AddTask(task);
		}
	};

};

#endif
