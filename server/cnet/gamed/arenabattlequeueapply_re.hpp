
#ifndef __GNET_ARENABATTLEQUEUEAPPLY_RE_HPP
#define __GNET_ARENABATTLEQUEUEAPPLY_RE_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"

namespace GNET
{
	class ArenaBattleQueueApply_Re : public GNET::Protocol
	{
#include "arenabattlequeueapply_re"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
		}
	};
};

#endif
