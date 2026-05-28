
#ifndef __GNET_ARENABATTLEQUEUEAPPLY_HPP
#define __GNET_ARENABATTLEQUEUEAPPLY_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"

#include "arenaapplyentry"
#include "arenabattleman.h"
namespace GNET
{

	class ArenaBattleQueueApply : public GNET::Protocol
	{
#include "arenabattlequeueapply"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			ArenaBattleMan::GetInstance()->OnPlayerApplyQueue(battle_mode, list, sid, team_id);
		}
	};

};

#endif
