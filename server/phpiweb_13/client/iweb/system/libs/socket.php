<?php

namespace system\libs;

use system\data\config;

if (!defined('IWEB')) {
    die ("Error! File works only in the system!");
}

class socket
{

    static $socket;
    static $error;
    static $buf = "";

    static function create()
    {
        self::$socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!self::$socket) {
            self::error(socket_strerror(socket_last_error(self::$socket)));
        }
    }

    private static function error($message)
    {
        system::debug("Socket server: " . $message);
        exit(0);
    }

    static function connect()
    {
        self::create();
        $connect = @socket_connect(self::$socket, config::$server_hostname, config::$server_port);
        if (!$connect) {
            self::error(socket_strerror(socket_last_error(self::$socket)));
        }
    }

    static function send($message, $flag = 0, $len = 0)
    {
        if ($len == 0) $len = strlen($message);
        $byte = @socket_send(self::$socket, $message, $len, $flag);
        if ($byte === false) self::$error = 0;
    }

    static function sendPack($opcode, $data)
    {
        $pack = pack("i", $opcode);
        $pack .= pack("i", strlen(config::$server_key));
        $pack .= config::$server_key;
        $pack .= pack("i", strlen($data));
        $pack .= $data;
        self::send($pack);
    }

//    static function sendToPw($data, $packet){
////        self::send($data);
////        self::send($packet);
//    }

    static function recv($len = 1024, $flag = 0, $ret_byte = false)
    {
        $byte = @socket_recv(self::$socket, self::$buf, $len, $flag);
        if ($byte == false) self::$buf = "rf0";
        if ($ret_byte == false) return self::$buf; else return $byte;
    }

    static function writeFile($localFile, $remoteFile)
    {
        $localFile = file_get_contents($localFile);
        self::send("write_file|$remoteFile|$localFile");
        return self::recv();
    }

    static function readFile($remoteFile, $localFile = false)
    {
        self::$buf = "";
        self::send("read_file|$remoteFile");
        $getFile = self::recv(1024 * 1024);
        if ($getFile != "rf0") {
            if ($localFile != false) {
                $fileWrite = fopen($localFile, "a+");
                fwrite($fileWrite, $getFile);
                fclose($fileWrite);
                return "file write";
            } else {
                return $getFile;
            }
        } else {
            return "file not write";
        }
    }

    static function getConsole($screen)
    {
        self::send("read_console|$screen");
        self::$buf = self::recv(1024 * 1024, MSG_WAITALL);
        return self::$buf;
    }

    static function packInt($int){
        return pack("i", $int);
    }

    static function packString($string){
        return self::packInt(strlen($string)).$string;
    }

    static function sendPacket($opcode, $data="", $recvLen = 2048, $flag = 0){
        self::connect();
        $pack = self::packInt($opcode);
        $pack .= self::packString(config::$server_key);
        self::send($pack.$data);
        $recv = self::recv($recvLen, $flag);
        if ($recv != "key:0") {
            self::close();
            return $recv;
        } else {system::debug("Server error key"); exit(0);}
    }


    static function close()
    {
        self::$buf = "";
        socket_close(self::$socket);
    }

}