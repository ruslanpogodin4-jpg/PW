#ifndef __ONLINEGAME_GS_PLAYER_IMP_SINGLE_ARENA_H__
#define __ONLINEGAME_GS_PLAYER_IMP_SINGLE_ARENA_H__

#include "player_imp.h"

class gplayer_single_arena_imp: public gplayer_imp
{
private:
	static bool __GetPetAttackHook(gactive_imp * __this, const MSG & msg, attack_msg & amsg);
	static bool __GetPetEnchantHook(gactive_imp * __this, const MSG & msg,enchant_msg & emsg);
public:
	DECLARE_SUBSTANCE(gplayer_single_arena_imp);
	virtual int MessageHandler(world * pPlane ,const MSG & msg);
	virtual attack_judge GetPetAttackHook();
	virtual enchant_judge GetPetEnchantHook();
};


#endif