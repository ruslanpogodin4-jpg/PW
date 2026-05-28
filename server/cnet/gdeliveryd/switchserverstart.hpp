
#ifndef __GNET_SWITCHSERVERSTART_HPP
#define __GNET_SWITCHSERVERSTART_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"

#include "gdeliveryserver.hpp"
#include "mapuser.h"
namespace GNET
{

	class SwitchServerStart : public GNET::Protocol
	{
#include "switchserverstart"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			//为修复复制bug，该协议发送流程修改为gs->gdeliveryd->glinkd
			int game_id = GProviderServer::GetInstance()->FindGameServer(sid);
			if (game_id == _GAMESERVER_ID_INVALID || game_id != src_gsid)
			{
				Log::log(LOG_ERR, "gdelivery::SwitchServerStart::invalid gameserver.");
				return;
			}
			Thread::RWLock::WRScoped l(UserContainer::GetInstance().GetLocker());
			PlayerInfo *pinfo = UserContainer::GetInstance().FindRoleOnline((roleid));
			if (NULL == pinfo)
			{
				Log::log(LOG_ERR, "gdelivery::SwitchServerStart:: invalid user info(userid or localsid).");
				return;
			}
			if (pinfo->user->linkid != link_id || pinfo->gameid != src_gsid)
			{
				Log::log(LOG_ERR, "gdelivery::SwitchServerStart:: invalid roleinfo(roleid or link_id or gs_id) .");
				return;
			}

			if (GDeliveryServer::GetInstance()->IsLimitedInstance(dst_gsid))
			{
				if (UserContainer::GetInstance().FindHardwareID(pinfo->user->hwid, dst_gsid))
				{
					DEBUG_PRINT("gdeliveryserver::DUPLICATED HWID ON INSTANCE");
					return;
				}
			}

			if (GDeliveryServer::GetInstance()->IsNWProtected())
			{
				if (dst_gsid == 83 || dst_gsid == 84 || dst_gsid == 85 || dst_gsid == 86) {
					if(src_gsid != 83){
						if (UserContainer::GetInstance().FindHardwareID(pinfo->user->hwid, 83))
						{
							DEBUG_PRINT("gdeliveryserver::DUPLICATED HWID ON NATION WAR 83");
							return;
						}
					}
					if (src_gsid != 84)
					{
						if (UserContainer::GetInstance().FindHardwareID(pinfo->user->hwid, 84))
						{
							DEBUG_PRINT("gdeliveryserver::DUPLICATED HWID ON NATION WAR 84");
							return;
						}
					}
					if (src_gsid != 85)
					{
						if (UserContainer::GetInstance().FindHardwareID(pinfo->user->hwid, 85))
						{
							DEBUG_PRINT("gdeliveryserver::DUPLICATED HWID ON NATION WAR 85");
							return;
						}
					}
					if (src_gsid != 86)
					{
						if (UserContainer::GetInstance().FindHardwareID(pinfo->user->hwid, 86))
						{
							DEBUG_PRINT("gdeliveryserver::DUPLICATED HWID ON NATION WAR 86");
							return;
						}
					}
				}
			}

			if (!GDeliveryServer::GetInstance()->Send(pinfo->linksid, this))
			{
				Log::log(LOG_ERR, "gdelivery::SwitchServerStart:: send to link failed.");
				return;
			}
			// record dst_game server id
			pinfo->user->switch_gsid = dst_gsid;
			DEBUG_PRINT("gdeliveryserver:: receive SwitchServerStart. roleid(%d),linkid(%d),src_gsid(%d),dst_gsid(%d)", roleid, link_id, src_gsid, dst_gsid);
		}
	};

};

#endif
