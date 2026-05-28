
#ifndef __GNET_ARENABATTLECANCEL_HPP
#define __GNET_ARENABATTLECANCEL_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"


namespace GNET
{

class ArenaBattleCancel : public GNET::Protocol
{
	#include "arenabattlecancel"

	void Process(Manager *manager, Manager::Session::ID sid)
	{
		// TODO
	}
};

};

#endif
