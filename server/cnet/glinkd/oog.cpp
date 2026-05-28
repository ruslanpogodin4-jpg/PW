#include <cstddef>
#include "oog.hpp"

OOG* OOG::instance = 0;

void OOG::Init()
{
	size_t i = 0;
	pool[i].opcode = 0x03;		pool[i++].load_state = OOG_HIGHT_LIMIT * 5;
	pool[i].opcode = 0x22;		pool[i++].load_state = OOG_LOW_LIMIT;
	pool[i].opcode = 0x52;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0x5B;		pool[i++].load_state = OOG_HIGHT_LIMIT * 2;
	pool[i].opcode = 0x62;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0x64;		pool[i++].load_state = OOG_HIGHT_LIMIT * 5;
	pool[i].opcode = 0x66;		pool[i++].load_state = OOG_HIGHT_LIMIT * 5;
	pool[i].opcode = 0x6B;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0x74;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0x80;		pool[i++].load_state = OOG_HIGHT_LIMIT * 5;
	pool[i].opcode = 0xCA;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0xCE;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0xD9;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0xE3;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0xF0;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0x352;		pool[i++].load_state = OOG_HIGHT_LIMIT * 5;
	pool[i].opcode = 0x3B7;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0x3B9;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0x3BA;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0xFA1;		pool[i++].load_state = OOG_HIGHT_LIMIT;
	pool[i].opcode = 0x12D0;	pool[i++].load_state = OOG_HIGHT_LIMIT * 5; 
	pool[i].opcode = 0x1517;	pool[i++].load_state = OOG_HIGHT_LIMIT;
}

size_t OOG::Get(int opcode)
{
	for (size_t i = 0; pool[i].opcode && i < OOG_HIGHT_COUNT; i++)
		if (opcode ==  pool[i].opcode)
			return pool[i].load_state;
	return OOG_HORMAL_LIMIT;
}

bool OOG::Check(size_t & ls)
{
	return (ls >= OOG_MAX_LIMIT);
}

void OOG::HeartBeat(size_t & ls)
{
	if (ls >  OOG_TIMER_LIMIT)
		ls -= OOG_TIMER_LIMIT;
}




