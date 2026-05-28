
#ifndef __GNET_ARENABATTLESTART_RE_HPP
#define __GNET_ARENABATTLESTART_RE_HPP

#include "rpcdefs.h"
#include "callid.hxx"
#include "state.hxx"
#include "arenabattleman.h"

namespace GNET
{

	class ArenaBattleStart_Re : public GNET::Protocol
	{
#include "arenabattlestart_re"

		void Process(Manager *manager, Manager::Session::ID sid)
		{
			Log::formatlog("arenabattle", "Received Signal to invite players to arenabattlestart_re for battle_id: %d\n", battle_id);
			ArenaBattleMan::GetInstance()->OnBattleStart(battle_id, retcode, sid);
		}
	};

};

#endif
