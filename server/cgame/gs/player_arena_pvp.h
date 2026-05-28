#ifndef __ONLINEGAME_GS_PLAYER_IMP_ARENA_PVP_H__
#define __ONLINEGAME_GS_PLAYER_IMP_ARENA_PVP_H__

#include "player_imp.h"

class gplayer_arenapvp_imp : public gplayer_imp
{
private:
	static bool __GetPetAttackHook(gactive_imp *__this, const MSG &msg, attack_msg &amsg);
	static bool __GetPetEnchantHook(gactive_imp *__this, const MSG &msg, enchant_msg &emsg);
	static void __GetPetAttackFill(gactive_imp *__this, attack_msg &attack);
	static void __GetPetEnchantFill(gactive_imp *__this, enchant_msg &enchant);

public:
	DECLARE_SUBSTANCE(gplayer_arenapvp_imp);
	virtual int MessageHandler(world *pPlane, const MSG &msg);
	virtual int ZombieMessageHandler(world *pPlane, const MSG &msg);
	virtual void FillAttackMsg(const XID &target, attack_msg &attack, int dec_arrow);
	virtual void FillEnchantMsg(const XID &target, enchant_msg &enchant);
	virtual void PlayerEnterWorld();
	virtual void PlayerEnterServer(int source_tag);
	virtual void PlayerLeaveServer();
	virtual attack_judge GetPetAttackHook();
	virtual enchant_judge GetPetEnchantHook();
	virtual attack_fill GetPetAttackFill();
	virtual enchant_fill GetPetEnchantFill();
	virtual void OnDeath(const XID &lastattack, bool is_pariah, char attacker_mode, int taskdead);
	virtual int Resurrect(const A3DVECTOR &pos, bool nomove, float exp_reduce, int target_tag, float hp_factor, float mp_factor, int param, float ap_factor, int extra_invincible_time);
	virtual bool ObjCanReturnToTown();
	void OnHeartbeat(size_t tick);
};

#endif
