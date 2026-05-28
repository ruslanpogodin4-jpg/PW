<?php

namespace system\libs\struct;

use system\data\character\character;
use system\data\config;
use system\libs\GRole;
use system\libs\socket;
use system\libs\stream;

if (!defined('IWEB')) {
    die("Error!");
}

class GRoleData
{

    // public $opcode = NULL;
    // public $len = NULL;
    // public $localsid = NULL;
    // public $retcode = NULL;
    public $base = NULL;
    public $status = NULL;
    public $pocket = NULL;
    public $equipment = NULL;
    public $storehouse = NULL;
    public $task = NULL;

    static function getListRoles($id)
    {
        stream::writeInt32(2147483648);
        stream::writeInt32($id);
        stream::pack(character::$pack['GetUserRoles']);
        stream::$readData = socket::sendPacket(4, socket::packInt(config::$dbPort) . stream::$writeData);
        if (stream::$readData != "server:0") {
            stream::$p = 2;
            stream::$length = stream::readCUint32();
            stream::readInt32();
            stream::readInt32();
            return GRole::readData(character::$functions['GetUserRoles']);
        }
        return null;
    }

    function getRoleFaction($id)
    {

//        stream::$p = 0;
//        stream::writeInt32($id, true, -1, 1);
//        stream::pack(0x11FF);
//        stream::Send(config::$serverIP, config::$dbPort);
//
//        $role = new roleFaction();
//        $role->opcode = stream::readCUint32();
//        $role->status = stream::readCUint32();
//        $role->unc1 = stream::readInt32();
//        $role->unc2 = stream::readInt32();
//        $role->rid = stream::readInt32();
//        $role->name = stream::readString();
//        $role->fid = stream::readInt32();
//        $role->cls = stream::readByte();
//        $role->race = stream::readByte();
//        $role->delayexpel = stream::readOctets(true);
//        $role->extend = stream::readOctets(true);
//        $role->title = stream::readString();
//
//        return $role;
    }

    function getRoleFactionInfo()
    {

//        stream::$p = 0;
//        stream::writeInt32(1024, true, -1, 1);
//        stream::pack(0x11FE);
//        stream::Send(config::$serverIP, config::$dbPort);
//
//        $role = new roleFactionInfo();
//        $role->opcode = stream::readCUint32();
//        $role->status = stream::readCUint32();
//        $role->unc1 = stream::readInt32();
//        $role->unc2 = stream::readInt32();
//        $role->fid = stream::readInt32();
//        $role->name = stream::readString();
//        $role->level = stream::readByte();
//        $role->master_id = stream::readInt32();
//        $role->master_role = stream::readByte();
//        $role->count = stream::readCUint32();
//        for ($i = 0; $role->count > $i; $i++) {
//            $role->members[$i] = new factionMember();
//            $role->members[$i]->rid = stream::readInt32();
//            $role->members[$i]->role = stream::readByte();
//        }
//        $role->announce = stream::readString();
//        $role->sysinfo = stream::readOctets(true);
//        stream::$p = 0;
//
//        return $role;
    }

}