#include "arenabattleman.h"
#include "hashstring.h"
#include "mapuser.h"
#include "gdeliveryserver.hpp"
#include "maplinkserver.h"
#include "chatbroadcast.hpp"
#include "localmacro.h"
#include "arenabattlestart.hpp"
#include "arenabattleinvite.hpp"
#include "arenabattlecancel.hpp"
#include "gproviderserver.hpp"
#include <list>
#include <algorithm>
#include "game2au.hpp"
#include "gauthclient.hpp"
#include "chatbroadcast.hpp"

namespace GNET
{

	bool ArenaBattleMan::Initialize()
	{
		/*seconds per tick*/
		IntervalTimer::Attach(this, (5 * 1000000) / IntervalTimer::Resolution());
		//LOG_TRACE("ArenaBattleManager::Arena Battle Manager Init Successfully!!\n");
		return true;
	}

	bool ArenaBattleMan::Update()
	{
		Thread::Mutex::Scoped l(lock_op);
		time_t now = GetTime();

		struct tm dt;
		localtime_r(&now, &dt);
		int second_of_day = dt.tm_hour * 3600 + dt.tm_min * 60 + dt.tm_sec;
		bool is_open_time = ((second_of_day >= ARENABATTLE_START_TIME) && (second_of_day < ARENABATTLE_END_TIME)) || ((second_of_day >= ARENABATTLE_START_TIME2) && (second_of_day < ARENABATTLE_END_TIME2));

		if(_status == ST_CLOSE){
			if(is_open_time){
				_status = ST_OPEN;
				BroadcastBattleStatus(CMSG_ARENA_BATTLE_OPEN);
			}
		} else {
			if(!is_open_time){
				_status = ST_CLOSE;
				BroadcastBattleStatus(CMSG_ARENA_BATTLE_CLOSE);
			}
		}

		FindAvailableBattles();
		return true;
	}

	void ArenaBattleMan::ServerInfo::Init(int war_type_, int world_tag_, int server_id_, SERVER_STAT stat_)
	{
		war_type = war_type_;
		world_tag = world_tag_;
		server_id = server_id_;
		status = stat_;
	}

	bool ArenaBattleMan::RegisterServer(int server_type, int war_type, int server_id, int worldtag)
	{
		Thread::Mutex::Scoped l(lock_op);
		LOG_TRACE("ArenaBattleManager::ArenaBattle Register Server: war_type=%d server=%d map=%d.\n", war_type, server_id, worldtag);

		bool is_find = false;
		for (unsigned int i = 0; i < _servers.size(); ++i)
		{
			if (server_id == _servers[i].server_id)
			{
				_servers[i].Init(war_type, worldtag, server_id, SERVER_STAT_NORMAL);
				is_find = true;
				break;
			}
		}

		if (!is_find)
		{
			ServerInfo info;
			info.Init(war_type, worldtag, server_id, SERVER_STAT_NORMAL);
			_servers.push_back(info);
		}

		return true;
	}

	void ArenaBattleMan::OnPlayerApplyQueue(int battle_mode, std::vector<ArenaApplyEntry> apply_list, unsigned int sid, int teamid)
	{
		Thread::Mutex::Scoped l(lock_op);
		if (_status != ST_OPEN)
			return;


		bool validregister = true;

		TeamArenaEntry *entry = new TeamArenaEntry;
		int strengthteam = 0;
		for (int i = 0; i < apply_list.size(); i++)
		{
			validregister = ValidateEntryRole(apply_list[i].roleid);
			if (!validregister)
				break;

			ArenaApplyEntry *newapply = new ArenaApplyEntry;
			newapply->roleid = apply_list[i].roleid;
			newapply->kill_number = apply_list[i].kill_number;
			newapply->total_defeat = apply_list[i].total_defeat;
			newapply->total_victory = apply_list[i].total_victory;
			strengthteam += apply_list[i].total_victory;
			newapply->death_number = apply_list[i].death_number;
			entry->team_members.push_back(newapply);
		}

		if(validregister){
			entry->battle_mode = battle_mode;
			entry->team_measure_balance = strengthteam;		
			entry->battle_id = 0;							
			entry->status = PLAYER_STAT_MATCHMAKING;		
			entry->start_matchmaking_timestamp = GetTime();
			entry->teamid = teamid;
			entry->sid = sid;
			_team_players_map.insert({entry->teamid, entry});

			for (int i = 0; i < entry->team_members.size(); i++)
			{
				LOG_TRACE("ArenaBattleManager::Team Request battlemode: %d, teamid: %d roleid: %d\n", entry->battle_mode, entry->teamid, entry->team_members[i]->roleid);
			}


			if(entry->battle_mode == ARENA_BATTLE_3V3){
				BroadcastBattleStatus(CMSG_ARENA_BATTLE_MATCHMAKEA);
			} else {
				BroadcastBattleStatus(CMSG_ARENA_BATTLE_MATCHMAKEB);
			}
			
		} else {
			LOG_TRACE("ArenaBattleManager::Invalid Team Request: %d, teamid: %d\n", battle_mode, teamid);
			for (int i = 0; i < apply_list.size(); i++)
			{
				GProviderServer::GetInstance()->Send(sid, ArenaBattleCancel(apply_list[i].roleid,teamid));
			}

			for (int i = 0; i < entry->team_members.size(); i++)
			{
				delete entry->team_members[i];
			}

			entry->team_members.clear();
			delete entry;
			}
		return;
	}

	bool ArenaBattleMan::ValidateEntryRole(int roleid)
	{

		bool validrole = true;

		std::map<int, TeamArenaEntry *>::iterator it = _team_players_map.begin();
		while (it != _team_players_map.end())
		{

			for (int i = 0; i < it->second->team_members.size(); i++)
			{
				if (it->second->team_members[i]->roleid == roleid)
					validrole = false;
			}
			it++;
		}

		
		return validrole;
	}

	void ArenaBattleMan::FindAvailableBattles()
	{
		if(ST_OPEN){
			FilterBattleType(ARENA_BATTLE_3V3);
			FilterBattleType(ARENA_BATTLE_6V6);
		}
	}
	void ArenaBattleMan::FilterBattleType(int type)
	{
		TEAM_ARENA_LIST _matchmaking_teams;
		//LOG_TRACE("ArenaBattleManager::Team_player_map size: %d\n", _team_players_map.size());

		std::map<int, TeamArenaEntry *>::iterator it = _team_players_map.begin();
		while (it != _team_players_map.end())
		{
			if (it->second->status == PLAYER_STAT_MATCHMAKING && it->second->start_matchmaking_timestamp+600 < GetTime())
			{
				CancelTeamEntryForMatchMaking(it->second->teamid,true);
				return;
			}

			if (it->second->battle_mode == type && it->second->status == PLAYER_STAT_MATCHMAKING)
				_matchmaking_teams.push_back(it->second);

			it++;
		}

		bool foundfirstresult = false;
		bool foundsecondresult = false;
		TeamArenaEntry *teamA;
		TeamArenaEntry *teamB;

		//LOG_TRACE("ArenaBattleManager::Matchmaking_teams size: %d\n", _matchmaking_teams.size());
		// Search for Two available teams, add criteria later
		for (int i = 0; i < _matchmaking_teams.size(); i++)
		{

			if (!foundfirstresult)
			{
				teamA = _matchmaking_teams.at(i);
				foundfirstresult = true;
			}
			else
			{
				teamB = _matchmaking_teams.at(i);
				foundsecondresult = true;
			}

			if (foundfirstresult && foundsecondresult)
			{
				teamA->status = PLAYER_STAT_BATTLECREATE;
				teamB->status = PLAYER_STAT_BATTLECREATE;

				// Create battle server and register teams as in battle
				CreateBattleServer(teamA, teamB, type);
				return;
			}
		}
	}

	void ArenaBattleMan::CreateBattleServer(TeamArenaEntry *TeamA, TeamArenaEntry *TeamB, int battle_mode)
	{
		if (_servers.size() > 0) {
			int mapidx = rand() % _servers.size();
			ServerInfo *serverbattle = &_servers[mapidx];
		
			ArenaBattleStart startbattle;
			startbattle.battle_id = ++_battle_id_index;
			startbattle.map_tag = serverbattle->world_tag;
			startbattle.battle_type = battle_mode;
			startbattle.defender = TeamA->teamid;
			startbattle.attacker = TeamB->teamid;
			startbattle.max_player_cnt = (battle_mode == ARENA_BATTLE_3V3) ? ARENA_BATTLE_3V3_NUM * 2 : ARENA_BATTLE_6V6_NUM * 2;
			startbattle.start_time = GetTime();
			startbattle.end_time = GetTime() + 900;

			TeamA->battle_id = startbattle.battle_id;
			TeamB->battle_id = startbattle.battle_id;

			BattleInfo *battle = new BattleInfo;
			battle->battle_id = startbattle.battle_id;
			battle->battle_type = startbattle.battle_type;
			battle->battle_mode = startbattle.battle_type;
			battle->TeamA_ID = TeamA->teamid;
			battle->TeamB_ID = TeamB->teamid;
			battle->server_idx = mapidx;
			battle->world_tag = serverbattle->world_tag;
			battle->end_time = startbattle.end_time;
			battle->start_time = startbattle.start_time;
			battle->status = BATTLE_STAT_CREATE;
			_battle_map.insert({battle->battle_id, battle});

			GProviderServer::GetInstance()->DispatchProtocol(serverbattle->server_id, startbattle);
			LOG_TRACE("ArenaBattleManager: Start battle on server %d, world_tag= %d, battle_id=%d defender=%d attacker=%d\n",
					  serverbattle->server_id, serverbattle->world_tag, startbattle.battle_id, startbattle.defender, startbattle.attacker);
		} else {
			for (int i = 0; i < TeamA->team_members.size(); i++)
			{
				GProviderServer::GetInstance()->Send(TeamA->sid, ArenaBattleCancel(TeamA->team_members[i]->roleid, TeamA->teamid));
				LOG_TRACE("ArenaBattleManager: Sending ArenaBattleCancel to roleid: %d, teamid: %d", TeamA->team_members[i]->roleid, TeamA->teamid);
			}

			for (int i = 0; i < TeamB->team_members.size(); i++)
			{
				GProviderServer::GetInstance()->Send(TeamB->sid, ArenaBattleCancel(TeamB->team_members[i]->roleid, TeamB->teamid));
				LOG_TRACE("ArenaBattleManager: Sending ArenaBattleCancel to roleid: %d, teamid: %d", TeamB->team_members[i]->roleid, TeamB->teamid);
			}
			CancelTeamEntryForMatchMaking(TeamA->teamid, false);
			CancelTeamEntryForMatchMaking(TeamB->teamid, false);
		}
	}

	void ArenaBattleMan::OnBattleStart(int battle_id, int retcode, unsigned int sid)
	{
		Thread::Mutex::Scoped l(lock_op);
		BattleInfo *pbi = GetBattleByBattleID(battle_id);

		TeamArenaEntry *TeamA;
		TeamArenaEntry *TeamB;

		TeamA = GetTeamByTeamID(pbi->TeamA_ID);
		TeamB = GetTeamByTeamID(pbi->TeamB_ID);

		if (!pbi)
		{
			LOG_TRACE("Unable to find Battle ID from map %d", battle_id);
			return;
		}
		if (!TeamA || !TeamB)
		{
			LOG_TRACE("Unable to find Teams In battle list");
			return;
		}

		ServerInfo *serverbattle = &_servers[pbi->server_idx];
		if (!serverbattle)
		{
			LOG_TRACE("Unable to find Server Battle  from map %d", pbi->server_idx);
			return;
		}

		LOG_TRACE("Starting invite to battle_id: %d, TeamA: %d - %d, TeamB: %d - %d, server id: %d, worldtag: %d\n", battle_id, TeamA->teamid, TeamA->sid, TeamB->teamid, TeamB->sid, serverbattle->server_id, serverbattle->world_tag);

		for (int i = 0; i < TeamA->team_members.size(); i++)
		{
			GProviderServer::GetInstance()->Send(TeamA->sid, ArenaBattleInvite(
																 TeamA->team_members[i]->roleid,
																 TeamA->teamid,
																 pbi->battle_mode,
																 pbi->battle_id,
																 serverbattle->world_tag));
		}
		for (int i = 0; i < TeamB->team_members.size(); i++)
		{
			GProviderServer::GetInstance()->Send(TeamB->sid, ArenaBattleInvite(
																 TeamB->team_members[i]->roleid,
																 TeamB->teamid,
																 pbi->battle_mode,
																 pbi->battle_id,
																 serverbattle->world_tag));
		}

		LOG_TRACE("Sended Invite Signal to Gameserver for battle_id: %d", battle_id);
	}

	ArenaBattleMan::BattleInfo *ArenaBattleMan::GetBattleByBattleID(int battle_id)
	{
		BATTLE_MAP::iterator it = _battle_map.find(battle_id);
		if (it != _battle_map.end())
			return it->second;

		return NULL;
	}

	ArenaBattleMan::TeamArenaEntry *ArenaBattleMan::GetTeamByTeamID(int team_id)
	{
		TEAM_ARENA_MAP::iterator it = _team_players_map.find(team_id);
		if (it != _team_players_map.end())
			return it->second;

		return NULL;
	}

	// deconstructor for team
	void ArenaBattleMan::CancelTeamEntryForMatchMaking(int teamid, bool cancel)
	{
		TeamArenaEntry *team = _team_players_map.at(teamid);

		for (int i = 0; i < team->team_members.size(); i++)
		{
			delete team->team_members[i];
		}
		team->team_members.clear();
		_team_players_map.erase(teamid);
		if (cancel)
			LOG_TRACE("ArenaBattleManager::Cancel Team Entry Matchmaking: teamid: %d\n", teamid);

		delete team;
	}
	// deconstructor for battle cancel for any reason
	void ArenaBattleMan::CancelBattleEntry(int battle_id, bool cancel)
	{
		BattleInfo *battle = _battle_map.at(battle_id);
		CancelTeamEntryForMatchMaking(battle->TeamA_ID);
		CancelTeamEntryForMatchMaking(battle->TeamB_ID);
		_battle_map.erase(battle_id);
		if (cancel)
			LOG_TRACE("ArenaBattleManager::Cancel battle_id: %d\n", battle_id);

		delete battle;
	}

	void ArenaBattleMan::FinishBattleEntry(int battle_id, int result, int winner, bool cancel)
	{
		Thread::Mutex::Scoped l(lock_op);
		BattleInfo *battle = _battle_map.at(battle_id);
		if(battle != NULL){
			battle->status = BATTLE_STAT_CLOSE;
			if(result == 1){
				if(winner == 1){
					SendTeamBattleReward(battle->battle_mode, battle_id, battle->TeamB_ID);
				} else {
					SendTeamBattleReward(battle->battle_mode, battle_id, battle->TeamA_ID);
				}
			}


			CancelTeamEntryForMatchMaking(battle->TeamA_ID);
			CancelTeamEntryForMatchMaking(battle->TeamB_ID);
			if (cancel)
				LOG_TRACE("ArenaBattleManager::Finishing battle_id: %d, result %d, winner: %d\n", battle_id, result, winner);
		}
	}

	void ArenaBattleMan::SendTeamBattleReward(int battle_mode,int battle_id, int team_id)
	{
		int reward = (battle_mode == ARENA_BATTLE_3V3) ? 5 : 10;
		TeamArenaEntry *team = _team_players_map.at(team_id);
		if(team != NULL){
			for (int i = 0; i < team->team_members.size(); i++)
			{
				Thread::RWLock::RDScoped l(UserContainer::GetInstance().GetLocker());
				PlayerInfo *pinfo = NULL;
				pinfo = UserContainer::GetInstance().FindRole(team->team_members[i]->roleid);
				if (NULL != pinfo){
					Game2AU proto;
					proto.userid = pinfo->userid;
					proto.qtype = Game2AU::ADD_TOUCH_MONEY;
					proto.info = Marshal::OctetsStream() << reward;

					if (!GAuthClient::GetInstance()->SendProtocol(proto))
					{
						Log::log(LOG_ERR, "role:%d failed to send arena reward battle_id:%d", team->team_members[i]->roleid, battle_id);
					}
				}
			}
		}
	}

	time_t ArenaBattleMan::GetTime()
	{
		return Timer::GetTime() + _adjust_time;
	}

	void ArenaBattleMan::BroadcastBattleStatus(int status)
	{
		ChatBroadCast cbc;
		cbc.channel = GN_CHAT_CHANNEL_SYSTEM;
		cbc.srcroleid = status;
		LinkServer::GetInstance().BroadcastProtocol(cbc);
	}
};
