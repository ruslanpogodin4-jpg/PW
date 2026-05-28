/*
 * FILE: globaldataman.h
 *
 * DESCRIPTION: global data loader and manager
 *
 * CREATED BY: Hedi, 2005/7/18
 *
 * HISTORY:
 *
 * Copyright (c) 2005 Archosaur Studio, All Rights Reserved.
 */

#ifndef _GLOBALDATAMAN_H_
#define _GLOBALDATAMAN_H_

#include <vector.h>
#include <a3dvector.h>

enum
{
	TREASURE_ITEM_OWNER_NPC_COUNT = 8,
};
typedef struct _TRANS_TARGET_SERV
{
	int id;
	int world_tag;
	A3DVECTOR3 vecPos;
	int domain_id;
} TRANS_TARGET_SERV;

bool globaldata_loadserver(const char *trans_data, const char *mall_data, const char *mall2_data, const char *mall3_data);
bool globaldata_releaseserver();

abase::vector<TRANS_TARGET_SERV> &globaldata_gettranstargetsserver();
#pragma pack(push, GSHOP_ITEM_PACK, 1)
typedef struct _GSHOP_ITEM_CLIENT
{
	int local_id;  // id of this shop item, used only for localization purpose
	int main_type; // index into the main type array
	int sub_type;  // index into the sub type arrray

	char icon[128]; // icon file path

	unsigned int id;  // object id of this item
	unsigned int num; // number of objects in this item

	struct
	{
		unsigned int price;		 // price of this item
		unsigned int end_time;	 //(年/月/日/时/分/秒)-如果为0，则持续生效
		unsigned int time;		 // 租借时间（秒，0表示无期）
		unsigned int start_time; // 开启时间：(年/月/日/时/分/秒)-如果为0，则在结束生效时间点前都生效
		int type;				 // Time type, 0 = no time, 1 = weekly, 2 = monthly, -1 = not valid
		unsigned int day;		 // 由位表示是否选择了某一天，可表示周也可表示月，由低到高
		unsigned int status;	 // Product status: 0 = regular, 1 = new, 2 = promotion, 3 recommended, 4-12 = discount %, 13 = flash sale, 14-19 = VIP levels
		unsigned int flag;		 // 分类控制
#ifdef VIP
		unsigned int min_vip_level; // Min VIP level required to purchase this item
#endif
	} buy[4];

	unsigned short desc[512];  // simple description
	unsigned short szName[32]; // name of this item to show
	unsigned int idGift;
	unsigned int iGiftNum;
	unsigned int iGiftTime;
	unsigned int iLogPrice;
	unsigned int owner_npcs[TREASURE_ITEM_OWNER_NPC_COUNT];
#ifdef VIP
	int buy_times_limit;	  // Indicates the amount of times it can be purchased during "buy_times_limit_mode" timeframe
	int buy_times_limit_mode; // 0 = none, 1 = daily, 2 = weekly, etc.
#endif

} GSHOP_ITEM_CLIENT;
#pragma pack(pop, GSHOP_ITEM_PACK)

typedef struct _MALL_ITEM_SERV
{
	int goods_id;
	int goods_count;

	struct __entry
	{
		int group_id;		   // 组id
		int st_type;		   // sale_time::type
		int st_param1;		   // sale_time::param1
		int st_param2;		   // sale_time::param2
		int status;			   // 商品状态：新品，促销，推荐
		int expire_date_valid; // expire_time 是否date
		int expire_time;
		int cash_need;
		int min_vip_level;
	} list[4];

	int gift_id;
	int gift_count;
	int gift_expire_time;
	int gift_log_price;
	int spec_owner[TREASURE_ITEM_OWNER_NPC_COUNT];
	int buy_times_limit;	  // 限购次数
	int buy_times_limit_mode; // Limited purchase method: 0 = Unlimited purchase,  1 = Daily purchase limit, 2 = Weekly purchase limit
} MALL_ITEM_SERV;
abase::vector<MALL_ITEM_SERV> &globaldata_getmallitemservice();
int globaldata_getmalltimestamp();

abase::vector<MALL_ITEM_SERV> &globaldata_getmall2itemservice();
int globaldata_getmall2timestamp();

abase::vector<MALL_ITEM_SERV> &globaldata_getmall3itemservice();
int globaldata_getmall3timestamp();

#endif //_GLOBALDATAMAN_H_
