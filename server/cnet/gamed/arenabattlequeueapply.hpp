
#ifndef __GNET_ARENABATTLEQUEUEAPPLY_HPP
#define __GNET_ARENABATTLEQUEUEAPPLY_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"

#include "arenaapplyentry"

namespace GNET
{

	class ArenaBattleQueueApply : public GNET::Protocol
	{
#include "arenabattlequeueapply"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			// TODO
		}
	};

};

#endif
