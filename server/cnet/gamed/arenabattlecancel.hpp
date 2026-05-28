
#ifndef __GNET_ARENABATTLECANCEL_HPP
#define __GNET_ARENABATTLECANCEL_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"
#include "log.h"
void player_cancel_matchmaking_arena(int role_id, int reason);

namespace GNET
{

	class ArenaBattleCancel : public GNET::Protocol
	{
#include "arenabattlecancel"

		class PlayerCancelArenaTask : public Thread::Runnable
		{
			int _roleid;

		public:
			PlayerCancelArenaTask(int roleid) : _roleid(roleid) {}
			void Run()
			{
				player_cancel_matchmaking_arena(_roleid, 1);
				delete this;
			}
		};

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			Log::log(LOG_INFO, "gamed :: Receive Task to Cancel Arena Entry for roleid: %d", role_id);

			Thread::Runnable *task = new PlayerCancelArenaTask(role_id);
			Thread::Pool::AddTask(task);
		}
	};
};

#endif
