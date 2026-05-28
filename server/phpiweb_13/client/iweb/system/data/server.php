<?php
namespace system\data;
if (!defined('IWEB')) {die("Error!");}
class server
{
    static $server = array(
        "logservice" => array(
            "dir" => "logservice",
            "program" => "logservice",
            "config" => "logservice.conf",
            "type" => 0
        ),
        "uniquenamed" => array(
            "dir" => "uniquenamed",
            "program" => "uniquenamed",
            "config" => "gamesys.conf",
            "type" => 0
        ),
        "auth" => array(
            "dir" => "auth/build/",
            "program" => "authd.sh",
            "config" => "start",
            "pid_name" => array("auth" => "auth", "authd" => "authd", "gauthd" => "gauthd"),
            "type" => 1,

        ),
        "gamedbd" => array(
            "dir" => "gamedbd",
            "program" => "gamedbd",
            "config" => "gamesys.conf",
            "type" => 0
        ),
        "gacd" => array(
            "dir" => "gacd",
            "program" => "gacd",
            "config" => "gamesys.conf",
            "type" => 0
        ),
        "gfactiond" => array(
            "dir" => "gfactiond",
            "program" => "gfactiond",
            "config" => "gamesys.conf",
            "type" => 0
        ),
        "gdeliveryd" => array(
            "dir" => "gdeliveryd",
            "program" => "gdeliveryd",
            "config" => "gamesys.conf",
            "type" => 0
        ),
        "glinkd" => array(
            "dir" => "glinkd",
            "program" => "glinkd",
            "config" => "gamesys.conf 1",
            "type" => 0
        ),
        "glinkd2" => array(
            "dir" => "glinkd",
            "program" => "glinkd",
            "config" => "gamesys.conf 2",
            "type" => 0
        ),
        "glinkd3" => array(
            "dir" => "glinkd",
            "program" => "glinkd",
            "config" => "gamesys.conf 3",
            "type" => 0
        ),
        "glinkd4" => array(
            "dir" => "glinkd",
            "program" => "glinkd",
            "config" => "gamesys.conf 4",
            "type" => 0
        ),
        "gs" => array(
            "dir" => "gamed",
            "program" => "gs",
            "config" => "gs01",
            "type" => 0
        )
    );

    static $serverStop = array(
        "glinkd",
        "logservice",
        "java",
        "gacd",
        "gs",
        "gfactiond",
        "gdeliveryd",
        "uniquenamed",
        "gamedbd",
    );
}