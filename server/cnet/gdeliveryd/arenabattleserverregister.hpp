#ifndef __GNET_ARENABATTLESERVERREGISTER_HPP
#define __GNET_ARENABATTLESERVERREGISTER_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"
#include "arenabattleman.h"

namespace GNET
{

	class ArenaBattleServerRegister : public GNET::Protocol
	{
#include "arenabattleserverregister"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			Log::formatlog("arenabattle", "register=server:server_type=%d:war_type=%d:serverid=%d:worldtag=%d:sid=%d", server_type, battle_type, server_id, world_tag, sid);
			ArenaBattleMan::GetInstance()->RegisterServer(server_type, battle_type, server_id, world_tag);
		}
	};

};

#endif