<?php

namespace system\models;

use system\data\character\character;
use system\data\config;
use system\data\lang;
use system\libs\database;
use system\libs\GRole;
use system\libs\socket;
use system\libs\stream;
use system\libs\struct\GMRoleData;
use system\libs\system;

if (!defined('IWEB')) {
    die("Error!");
}

class indexModel
{

    //work
    static function statusServer()
    {
        return (@fsockopen(config::$server_hostname, config::$linkPort)) ? true : false;
    }

    //work
    static function login($username, $password)
    {
        if ((boolean)preg_match("#^[aA-zZ0-9_]+$#", $username)) {
            if ((boolean)preg_match("#^[aA-zZ0-9_]+$#", $password)) {
                database::query("SELECT * FROM users WHERE name='" . database::safesql($username) . "' AND password='" . database::safesql(md5($password)) . "'");
                if (database::num() > 0) {
                    $user = database::assoc();
                    $_SESSION['id'] = $user['id'];
                    $_SESSION['user'] = $user['name'];
                    system::jms('reload', "");
                } else {
                    system::jms('danger', "Ошибка авторизации, не верный логин или пароль!");
                }
            } else
                system::jms('danger', "Имя пользователя содержит не верные символы!");
        } else
            system::jms('danger', "Пароль содержит не верные символы!");
        database::clear();
    }

    //work
    static function onlineList()
    {
        $online = array();
        $onlineList['count'] = 0;
        stream::writeInt32(0);
        stream::writeInt32(0);
        stream::writeInt32(0);
        stream::writeOctets("");
        stream::pack(character::$pack['GMRoleOnline']);
        stream::$readData = socket::sendPacket(5, socket::packInt(config::$gdeliverydPort) . stream::$writeData, 2048*2048);
        if (stream::$readData != "server:0") {
            stream::readCUint32();
            stream::$length = stream::readCUint32();
            $online = GRole::readData(character::$functions['GMRoleOnline']);
            $onlineList['count'] = $online['count']['value'];
        }
        $onlineList['data'] = "";
        if ($onlineList['count'] > 0) {
            foreach ($online['users'] as $user) {
                $onlineList['data'] .= "    <tr>
        <td>{$user['userid']['value']}</td>
        <td>{$user['roleid']['value']}</td>
        <td>{$user['name']['value']}</td>
        <td><a class=\"badge badge-primary\" href='" . config::$site_adr . "/?controller=editor&page=xml&id={$user['roleid']['value']}'>XML</a>
            <a class=\"badge badge-success\" href='" . config::$site_adr . "/?controller=editor&id={$user['roleid']['value']}'>Визуальный</a>
            <a class=\"badge badge-warning\" href='" . config::$site_adr . "/?controller=server&page=mail&id={$user['roleid']['value']}'>Почта</a>
            <a class=\"badge badge-info\" href='javascript:void(0)' data-toggle=\"modal\" data-target=\"#ban\" onclick='ban(" . $user['roleid']['value'] . ", 3)'>Бан чата</a>
            <a class=\"badge badge-light\" href='javascript:void(0)' data-toggle=\"modal\" data-target=\"#ban\" onclick='ban(" . $user['roleid']['value'] . ", 4)'>Бан перса</a>
            <a class=\"badge badge-danger\" onclick='kickRole(" . $user['roleid']['value'] . ")' href='javascript:void(0)'>Кикнуть</a>
        </td>
    </tr>";
            }
        }
        return $onlineList;
    }

    //work
    static function accountList()
    {
        $account['count'] = 0;
        $account['data'] = "";
        $users = socket::sendPacket(59, "", 4096 * 4096, MSG_WAITALL);
        $users = explode("\n", trim($users));
        $usersList = json_decode($users[1], true);
        if ($users != "mysql:1") {
            $account['count'] = $users[0];
            if ($account['count'] > 0) {
                foreach ($usersList as $user) {
                    if ($user['group'] == "gm")
                        $group = "<span class='badge badge-danger'>" . lang::$user_group[$user['group']] . "</span>";
                    else
                        $group = "<span class='badge badge-success'>" . lang::$user_group[$user['group']] . "</span>";

                    $account['data'] .= "<tr>
                                    <td><small>{$user['id']}</small></td>
                                    <td><small>{$user['name']}</small></td>
                                    <td><small>{$user['email']}</small></td>
                                    <td><small>{$group}</small></td>
                                    <td><small>{$user['creatime']}</small></td>
                                    <td><small><a class=\"badge badge-success\" href='javascript:void(0)' data-toggle=\"modal\" data-target=\"#getChar\" onclick='getChars(" . $user['id'] . ")'>Персонажи</a>
                                     <a class=\"badge badge-primary\" href='javascript:void(0)' data-toggle=\"modal\" data-target=\"#addCash\" onclick='addCash(" . $user['id'] . ")'>Выдать голд</a>
                                     <a class=\"badge badge-warning\" href='javascript:void(0)' data-toggle=\"modal\" data-target=\"#editGM\" onclick='editGM(" . $user['id'] . ")'>GM Права</a>
                                     <a class=\"badge badge-info\" href='javascript:void(0)' data-toggle=\"modal\" data-target=\"#ban\" onclick='ban(" . $user['id'] . ", 2)'>Бан чата</a>
                                     <a class=\"badge badge-danger\" href='javascript:void(0)' data-toggle=\"modal\" data-target=\"#ban\" onclick='ban(" . $user['id'] . ", 1)'>Бан акк</a></td>
                               </small> </tr>";
                }
            }
        }
        return $account;
    }

    static function gm($data, $function = "add")
    {
        $error = false;
        if ($function == "add") {
            if (socket::sendPacket(60, socket::packString("DELETE FROM auth WHERE userid='{$data['id']}'")) == "mysql:7") {
                if (isset($data['params']) && count($data['params']) > 0) {
                    foreach ($data['params'] as $value) {
                        if (socket::sendPacket(60, socket::packString("INSERT INTO auth (userid, zoneid, rid) VALUES ('" . $data['id'] . "', '1', '" . $value . "')")) != "mysql:7") {
                            $error = true;
                        }
                    }
                    if (!$error) {
                        system::jms("success", "ГМ права установлены для аккаунта " . $data['id']);
                        system::log("Изменение ГМ прав на аккаунт " . $data['id']);
                    } else
                        system::jms("danger", "Возникла ошибка при выдаче прав1!");
                } else
                    system::jms("success", "ГМ права удалены с аккаунта " . $data['id']);
            } else
                system::jms("danger", "Возникла ошибка при выдаче прав2!");

        } else if ($function == "check") {
            $result = socket::sendPacket(61, socket::packInt($data));
            if ($result != "mysql:0") {
                $permission = explode("\n", $result);
                $count = $permission[0];
                $permission = array_slice($permission, 1);
                if ($count > 0) {
                    echo json_encode($permission);
                } else
                    echo 0;
            }
        }
    }

    static function kickUser($role, $time = "1", $reason = "GM", $gm = 32)
    {
        stream::writeInt32($gm);
        stream::writeInt32(1);
        stream::writeInt32($role);
        stream::writeInt32($time);
        stream::writeString($reason);
        stream::pack(character::$pack['forbid']);
        if (socket::sendPacket(4, socket::packInt(config::$gdeliverydPort) . stream::$writeData) != "server:0") {
            system::jms('success', "Персонаж $role отключен от сервера!");
            system::log("Персонаж $role отключен от сервера");
        } else
            system::jms('error', "Ошибка подключения к серверу!");

    }

    static function addUser($data)
    {
        $username = strtolower(trim($data['username']));
        $password = strtolower(trim($data['password']));
        $email = strtolower(trim($data['email']));
        if (!empty($username)) {
            if (!empty($username)) {
                if ($data['type_password'] == "base64") {
                    $hashPassword = base64_encode(md5($username . $password, true));
                } elseif ($data['type_password'] == "md5") {
                    $hashPassword = "0x" . md5($username . $password);
                } else {
                    $hashPassword = $password;
                }

                if (socket::sendPacket(60, socket::packString("call adduser('$username', '$hashPassword', '0', '0', '0', '0', '$email', '0', '0', '0', '0', '0', '0', '0', '', '', '$hashPassword')")) != "mysql:7") {
                    system::jms("danger", "Возникла ошибка при создание аккаунта!");
                } else {
                    system::jms("success", "Аккаунт создан");
                }
            } else {
                system::jms("danger", "Укажите пароль!");
            }
        } else {
            system::jms("danger", "Укажите имя пользователя!");
        }

//        for ($i = 1500; $i < 6000; $i++){
//            socket::sendPacket(60, socket::packString("call adduser('test_$i', 'test_$i', '0', '0', '0', '0', 'test_$i', '0', '0', '0', '0', '0', '0', '0', '', '', 'test_$i')"));
//        }
    }

}