
#ifndef __GNET_SETLOCKEDLOGOUTUSER_HPP
#define __GNET_SETLOCKEDLOGOUTUSER_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"

#include "gproviderserver.hpp"
#include "mapuser.h"

namespace GNET
{

	class SetLockedLogoutUser : public GNET::Protocol
	{
#include "setlockedlogoutuser"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			PlayerInfo *pinfo = UserContainer::GetInstance().FindRole(userid);
			if (!pinfo)
			{
				return;
			}

			pinfo->user->locked_logout = 1 ;
		}
	};

};

#endif
