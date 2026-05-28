#include "world.h"
#include "player_single_arena.h"
#include "clstab.h"

DEFINE_SUBSTANCE(gplayer_single_arena_imp,gplayer_imp,CLS_SINGLE_ARENA_IMP)

int 
gplayer_single_arena_imp::MessageHandler(world * pPlane ,const MSG & msg)
{
	switch(msg.message)
	{
	case GM_MSG_ATTACK:
		{
			ASSERT(msg.content_length >= sizeof(attack_msg));
			attack_msg & amsg = *(attack_msg*)msg.content;
			_filters.EF_TransRecvAttack(msg.source, amsg);

			XID attacker = amsg.ainfo.attacker;

			if( !(attacker == _parent->ID) ){

				if(attacker.IsPlayerClass())
					amsg.vigour = -1;

				((gplayer_controller*)_commander)->TrySelect(msg.source);
				HandleAttackMsg(pPlane,msg,&amsg);
				return gplayer_imp::MessageHandler(pPlane,msg);

			}
		}
		break;
	case GM_MSG_ENCHANT:
		{
			ASSERT(msg.content_length >= sizeof(enchant_msg));
			enchant_msg ech_msg = *(enchant_msg*)msg.content;
			_filters.EF_TransRecvEnchant(msg.source, ech_msg);

			if ( ech_msg.helpful )
			{
				XID attacker = ech_msg.ainfo.attacker;
				if(attacker != _parent->ID)
				{
					if(ech_msg.helpful == 1)
					{
						if((_refuse_bless & C2S::REFUSE_NON_TEAMMATE_BLESS) 
							&& (!IsInTeam() || !IsMember(attacker))) return 0;
					}
					else if(ech_msg.helpful == 2)
					{
						if(_refuse_bless & C2S::REFUSE_NEUTRAL_BLESS) return 0;
					}
				}
			}
			else
			{
				XID attacker = ech_msg.ainfo.attacker;
				if(attacker == _parent->ID)
					return 0;
				if(attacker.IsPlayerClass())
					ech_msg.target_faction = -1;
				((gplayer_controller*)_commander)->TrySelect(msg.source); 
			}
			ech_msg.is_invader = false;
			HandleEnchantMsg(pPlane,msg,&ech_msg);
			return 0;
		}
		break;
	}
	return gplayer_imp::MessageHandler(pPlane,msg);
}

gactive_imp::attack_judge 
gplayer_single_arena_imp::GetPetAttackHook()
{
	return gplayer_single_arena_imp::__GetPetAttackHook;
}

gactive_imp::enchant_judge 
gplayer_single_arena_imp::GetPetEnchantHook()
{
	return gplayer_single_arena_imp::__GetPetEnchantHook;
}

bool 
gplayer_single_arena_imp::__GetPetAttackHook(gactive_imp * __this, const MSG & msg, attack_msg & amsg)
{
	amsg.is_invader = 0;
	amsg.target_faction = -1;
	return true;
}

bool 
gplayer_single_arena_imp::__GetPetEnchantHook(gactive_imp * __this, const MSG & msg,enchant_msg & emsg)
{
	if(!emsg.helpful) {
		emsg.target_faction = -1;
	}
	emsg.is_invader = 0;
	return true;
}