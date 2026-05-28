

class OOG
{
private:	
	
enum
	{
	OOG_PLAYER_MAX_LIMIT = 3000u,	//Максимальное количество игроков в ООГ векторе
	OOG_MAX_LIMIT = 9500u,			//Максимальный лимит ООГ пакетов
	OOG_TIMER_LIMIT = 250u,			//Уменьшение ООГ лимита по таймеру
	OOG_LOW_LIMIT = 2u,				//Сколько добавлять в лимит за легкий пакет
	OOG_HORMAL_LIMIT = 10u,			//Сколько добавлять в лимит за средний пакет
	OOG_HIGHT_LIMIT = 40u,			//Сколько добавлять в лимит за тяжелый пакет
	OOG_HIGHT_COUNT = 128u,			//Количество тяжелых пакетов
	};
	
	struct
	{
		size_t opcode;
		size_t load_state;
	} pool[OOG_HIGHT_COUNT];	

public:	

	void Init();
	size_t Get(int opcode);
	bool Check(size_t & ls);
	void HeartBeat(size_t & ls);

	static OOG * GetInstance()
	{
		if (!instance)
		instance = new OOG();
		return instance;
	}
	static OOG * instance;
	
};









