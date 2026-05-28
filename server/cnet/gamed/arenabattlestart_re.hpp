
#ifndef __GNET_ARENABATTLESTART_RE_HPP
#define __GNET_ARENABATTLESTART_RE_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"

namespace GNET
{

	class ArenaBattleStart_Re : public GNET::Protocol
	{
#include "arenabattlestart_re"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			// TODO
		}
	};

};

#endif
