#ifndef __GNET_ARENA_BATTLE_MAN_H
#define __GNET_ARENA_BATTLE_MAN_H

#include <vector>
#include <map>
#include "thread.h"
#include "itimer.h"
#include "arenaapplyentry"
#include "localmacro.h"

namespace GNET
{
	class ArenaBattleMan : public IntervalTimer::Observer
	{
	public:
		enum
		{
			ST_CLOSE,
			ST_OPEN,
		};

		enum
		{
			ARENA_BATTLE_3V3,
			ARENA_BATTLE_6V6,
		};

		enum
		{
			ARENA_BATTLE_3V3_NUM = 3,
			ARENA_BATTLE_6V6_NUM = 6,
		};

		enum SERVER_STAT
		{
			SERVER_STAT_NORMAL,
			SERVER_STAT_CREATE,
			SERVER_STAT_DISCONNECT,
			SERVER_STAT_ERROR,
			SERVER_STAT_FULL,
			ARENABATTLE_START_TIME = (12 * 3600 + 00 * 60),
			ARENABATTLE_END_TIME = (15 * 3600 + 00 * 60),
			ARENABATTLE_START_TIME2 = (20 * 3600 + 00 * 60),
			ARENABATTLE_END_TIME2 = (23 * 3600 + 00 * 60),
		};

		enum BATTLE_STAT
		{
			BATTLE_STAT_CREATE,		//正在创建的过程中
			BATTLE_STAT_OPEN,		//处于开启状态
			BATTLE_STAT_WAIT_CLOSE, //战场副本刚结束
			BATTLE_STAT_CLOSE,		//处于关闭状态
		};

		enum PLAYER_STAT
		{
			PLAYER_STAT_CREATE,
			PLAYER_STAT_MATCHMAKING,
			PLAYER_STAT_BATTLECREATE,
			PLAYER_STAT_INVITE,
			PLAYER_STAT_INBATTLE,
			PLAYER_STAT_END
		};

		struct ServerInfo
		{
			int war_type;
			int world_tag;
			int server_id;
			SERVER_STAT status;

			ServerInfo()
			{
				war_type = 0;
				world_tag = 0;
				server_id = 0;
				status = SERVER_STAT_CREATE;
			}

			void
			Init(int war_type_, int world_tag_, int server_id_, SERVER_STAT stat_);
		};

		struct TeamArenaEntry
		{
			int battle_mode;
			int team_measure_balance;
			int teamid;
			int battle_id;
			int status;
			int start_matchmaking_timestamp;
			int matchmaking_limit;
			unsigned int sid;
			std::vector<ArenaApplyEntry *> team_members;

			TeamArenaEntry()
			{
				battle_mode = -1;
				team_measure_balance = 0;
				teamid = 0;
				battle_id = -1;
				status = PLAYER_STAT_CREATE;
				start_matchmaking_timestamp = 0;
				matchmaking_limit = 0;
				sid = -1;
			}
		};

		struct BattleInfo
		{
			int battle_id;
			int battle_type;
			int battle_mode;
			int server_idx;
			int world_tag;
			int end_time;
			int start_time;
			int TeamA_ID;
			int TeamB_ID;
			BATTLE_STAT status;

			BattleInfo()
			{
				battle_id = -1;
				battle_type = -1;
				battle_mode = -1;
				server_idx = 0;
				world_tag = 0;
				end_time = 0;
				start_time = 0;
				TeamA_ID = 0;
				TeamB_ID = 0;
				status = BATTLE_STAT_CREATE;
			}
		};

		typedef std::vector<ServerInfo> SERVER_LIST;
		typedef std::vector<TeamArenaEntry *> TEAM_ARENA_LIST;
		typedef std::map<int /*team_id */, TeamArenaEntry *> TEAM_ARENA_MAP;
		typedef std::map<int /*battle_id*/, BattleInfo *> BATTLE_MAP;

	protected:
		Thread::RWLock locker;
		int _adjust_time;
		int _status;
		int _battle_id_index;
		SERVER_LIST _servers;
		TEAM_ARENA_MAP _team_players_map;
		BATTLE_MAP _battle_map;

		Thread::Mutex lock_op;

	public:
		ArenaBattleMan() : locker("ArenaBattleMan::locker"), lock_op("ArenaBattleMan::arena_operation"), _adjust_time(0), _status(ST_CLOSE), _battle_id_index(0) {}
		~ArenaBattleMan() {}
		static ArenaBattleMan *GetInstance()
		{
			static ArenaBattleMan instance;
			return &instance;
		}
		bool Initialize();
		bool RegisterServer(int server_type, int war_type, int server_id, int worldtag);
		bool Update();
		void FindAvailableBattles();
		void FilterBattleType(int type);
		void OnPlayerApplyQueue(int battle_mode, std::vector<ArenaApplyEntry> apply_list, unsigned int sid, int teamid);
		void CreateBattleServer(TeamArenaEntry *TeamA, TeamArenaEntry *TeamB, int battle_mode);
		void OnBattleStart(int battle_id, int retcode, unsigned int sid);
		void CancelTeamEntryForMatchMaking(int team_id, bool cancel = false);
		void CancelBattleEntry(int battle_id, bool cancel = false);
		void FinishBattleEntry(int battle_id, int result, int winner, bool cancel = false);

		void SendTeamBattleReward(int battle_mode, int battle_id, int team_id);
		void BroadcastBattleStatus(int status);
		bool ValidateEntryRole(int roleid);
		BattleInfo *GetBattleByBattleID(int battle_id);
		TeamArenaEntry *GetTeamByTeamID(int team_id);

		time_t GetTime();
	};
};
#endif
