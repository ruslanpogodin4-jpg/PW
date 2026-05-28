
#ifndef __GNET_ARENABATTLEINVITE_HPP
#define __GNET_ARENABATTLEINVITE_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"


namespace GNET
{

class ArenaBattleInvite : public GNET::Protocol
{
	#include "arenabattleinvite"

	void Process(Manager *manager, Manager::Session::ID sid)
	{
		// TODO
	}
};

};

#endif
