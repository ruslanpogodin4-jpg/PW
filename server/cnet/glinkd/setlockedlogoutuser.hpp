#ifndef __GNET_SETLOCKEDLOGOUTUSER_HPP
#define __GNET_SETLOCKEDLOGOUTUSER_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"

#include "glinkserver.hpp"
#include "gdeliveryclient.hpp"
namespace GNET
{

	class SetLockedLogoutUser : public GNET::Protocol
	{
#include "setlockedlogoutuser"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			if (!GLinkServer::ValidRole(sid, userid))
			{
				GLinkServer::GetInstance()->SessionError(sid, ERR_INVALID_ACCOUNT, "Error userid or roleid.");
				return;
			}
			this->localsid = sid;
			GDeliveryClient::GetInstance()->SendProtocol(this);
		}
	};

};

#endif