<?php

namespace system\models;

use system\data\character\character;
use system\data\config;
use system\data\server;
use system\libs\database;
use system\libs\func;
use system\libs\GRole;
use system\libs\socket;
use system\libs\stream;
use system\libs\struct\GMRoleData;
use system\libs\system;

if (!defined('IWEB')) {
    die("Error!");
}

class serverModel
{

    public static $auth;
    public static $gacd;
    public static $gamedb;
    public static $gdeliveryd;
    public static $gfactiond;
    public static $glinkd1;
    public static $logservice;
    public static $uniquenamed;
    public static $gs;

    static function sendChatMessage($msg, $chanel)
    {
        if (!empty($msg)) {
            stream::writeByte($chanel);
            stream::writeByte(0);
            stream::writeInt32(0);
            stream::writeString($msg);
            stream::writeOctets("");
            stream::pack(character::$pack['sendChat']);

            if (socket::sendPacket(3, socket::packInt(29300) . stream::$writeData) != "server:0") {
                system::jms("success", "Сообщение отправлено");
                system::log("Отправлено сообщение в чат");
            } else
                system::jms("danger", "Ошибка отправки сообщения");
        } else
            system::jms("info", "Пустое сообщение не отправлено");
    }

    static function statusServer()
    {
        $datas = socket::sendPacket(57, socket::packString("/proc/meminfo"));

        $dataMemFile = explode("\n", $datas);
        $data = array();
        foreach ($dataMemFile as $line) {
            if (!empty($line)) {
                list($key, $val) = explode(":", str_replace(" kB", "", $line));
                $data[$key] = intval((int)trim($val) / 1024);
            }
        }
        return $data;
    }

    static function mail($data, $online = false)
    {
        if (!empty($data['idChar']) && is_numeric($data['idChar'])) {
            stream::writeInt32(344);
            stream::writeInt32(32);
            stream::writeByte(3);
            stream::writeInt32($data['idChar']);
            stream::writeString($data['titleItem']);
            stream::writeString($data['messageItem']);
            stream::writeInt32($data['idItem']);
            stream::writeInt32(0);
            stream::writeInt32($data['countItem']);
            stream::writeInt32($data['maxCountItem']);
            stream::writeOctets($data['octetItem'], true);
            stream::writeInt32($data['prototypeItem']);
            stream::writeInt32($data['timeItem']);
            stream::writeInt32(0);
            stream::writeInt32(0);
            stream::writeInt32($data['maskItem']);
            stream::writeInt32($data['moneyItem']);
            stream::pack(character::$pack['sendMail']);
            if (socket::sendPacket(3, socket::packInt(config::$gdeliverydPort) . stream::$writeData) != "server:0") {
                if (!$online) {
                    system::jms("success", "Почта отпралена на персонажа " . $data['idChar']);
                    system::log("Отправлена почта на " . $data['idChar'] . ", предмет: " . $data['idItem'] . " в кол-ве: " . $data['countItem']);
                }// else return true;
                if (database::query("SELECT * FROM mail WHERE idItem='" . database::safesql($data['idItem']) . "'")) {
                    if (database::num() == 0) {
                        $insertKey = "";
                        $insertValue = "";
                        foreach ($data as $key => $value) {
                            if ($key != "idChar") {
                                $insertKey .= "$key,";
                                $insertValue .= "'" . database::safesql($value) . "',";
                            }
                        }
                        database::query("INSERT INTO mail (" . rtrim($insertKey, ",") . ") VALUES (" . rtrim($insertValue, ",") . ")");
                    } else {
                        $update = "";
                        foreach ($data as $key => $value) {
                            if ($key != "idChar") {
                                $update .= $key . "='" . database::safesql($value) . "',";
                            }
                        }
                        database::query("UPDATE mail SET " . rtrim($update, ",") . " WHERE idItem='" . database::safesql($data['idItem']) . "'");
                    }
                } else {
                    system::jms("info", "Почта отправлена но возникла проблема при записи истории в базу данных");
                }
            } else {
                system::jms("danger", "Ошибка отправки почты");
            }
        } else {
            system::jms("info", "Укажите ID персонажа для отправки почты");
        }
    }

    static function sendMailAllOnline($data)
    {
        $online = array();
        stream::writeInt32(0);
        stream::writeInt32(0);
        stream::writeInt32(0);
        stream::writeOctets("");
        stream::pack(character::$pack['GMRoleOnline']);
        stream::$readData = socket::sendPacket(5, socket::packInt(config::$gdeliverydPort) . stream::$writeData);
        if (stream::$readData != "server:0") {
            stream::readCUint32();
            stream::$length = stream::readCUint32();
            $online = GRole::readData(character::$functions['GMRoleOnline']);
        }
        if ($online['count']['value'] > 0) {
            foreach ($online['users'] as $user) {
                $data['idChar'] = $user['roleid']['value'];
                stream::$writeData = "";
                self::mail($data, true);
            }
            system::log("Отправлена почта всем кто в сети");
            system::jms("success", "Почта отправлена " . $online['count']['value'] . " персонаж(у/ам)");
        }

    }

    static function getItemsMail($id = "")
    {
        $items = "";
        $fileElement = json_decode(base64_decode(gzinflate(file_get_contents(dir . "/system/data/items.json"))),true);
        $fileIcon = json_decode(base64_decode(gzinflate(file_get_contents(dir . "/system/data/icons.json"))),true);

        if (isset($id) && is_numeric($id)) {
            database::query("SELECT * FROM mail WHERE idItem='{$id}'");
            $items = json_encode(database::assoc());
        } else {
            $history = database::query("SELECT * FROM mail");
            echo "<input type='hidden'>";

            for ($i = 0; $i < database::num($history); $i++) {
                $item = database::assoc($history);
                $elItem = editorModel::getItemFromElement($item['idItem'], $fileElement);
                $icon = (isset($fileIcon[$elItem['icon']])) ?$fileIcon[$elItem['icon']] : $fileIcon["unknown.dds"];
                $items .= "<option value='" . $item['idItem'] . "' data-content='<img src=\"data:image/png;base64,$icon\"> {$item['idItem']} {$elItem['name']}'></option>";
            }

        }
        return $items;
    }

    static function checkStatusServer()
    {
        $proc = array();
        foreach (server::$server as $key => $server) {
            $program = (!isset($server['pid_name'])) ? $server['program'] : $server['pid_name'][config::$serverTypeAuth];

            $getRecv = socket::sendPacket(2, socket::packString($program), 1024 * 100);

            if ($getRecv != "off") {
                $getProcess = explode("\n", trim($getRecv));
                //$getProcess = array_diff($getProcess, array(''));
                foreach ($getProcess as $value) {
                    $proc[$key]['process'][] = str_getcsv(preg_replace("/\s{2,}/", ' ', $value), " ", "", "\n");
                }
            }
            if ($getRecv != "off") {
                $proc[$key]['count'] = count($proc[$key]['process']);
                $proc[$key]['status'] = "<span style='color: green'>Включен</span>";
            } else {
                $proc[$key]['count'] = 0;
                $proc[$key]['status'] = "<span style='color:red;'>Выключен</span>";
            }
            //break;
        }
        return $proc;
    }

    static function getStartedLocation()
    {
        $Started = "";
        $getRecv = socket::sendPacket(2, socket::packString("gs"), 2048 * 10);
        if ($getRecv != "off") {
            $getProcess = explode("\n", trim($getRecv));
            $arr = func::listLocations(true);

            foreach ($getProcess as $process) {
                $get = str_getcsv(preg_replace("/\s{2,}/", ' ', $process), " ", "", "\n");
                $Started .= "<tr id='loc-{$get[1]}'>
    <td><label style='width: 100%' class='custom-control custom-checkbox'>
  <input type='checkbox' name='checkbox[{$get[1]}]' data-pid='{$get[1]}' id='location' class='location custom-control-input' value='{$get[11]}'>
  <span class='custom-control-indicator'></span>
  <span class='custom-control-description'>" . $arr[$get[11]] . "</span>
</label></td>
    <td>" . $get[2] . "%</td>
    <td>" . $get[3] . "%</td>
     <td><button onclick='killLocation({$get[1]})' class='btn btn-danger btn-sm'><i class='fas fa-power-off'></i></button></td></tr>";
            }
        }
        return $Started;

    }

    static function startLocation($data)
    {
        $server = server::$server['gs'];
        $error = false;
        if ($data['oneQuery'] == "true") {
            $firstLocation = $data['locations'][0];
            $count = count($data['locations']);
            $locations = array_slice($data['locations'], 1);
            $goStart = "";
            foreach ($locations as $location) {
                $goStart .= "$location ";
            }
            $startServerOneQuery = "cd {dir}; ./{program} $firstLocation gs.conf gmserver.conf gsalias.conf $goStart> {log_dir}/Locations_{$count}_iweb.log &";
            $startServerOneQuery = preg_replace("{{dir}}", config::$serverPath . "/" . $server['dir'], $startServerOneQuery);
            $startServerOneQuery = preg_replace("{{program}}", $server['program'], $startServerOneQuery);
            $startServerOneQuery = preg_replace("{{log_dir}}", config::$serverPath . "/logs", $startServerOneQuery);
            if (socket::sendPacket(0, socket::packString($server['program'] . " " . $firstLocation) . socket::packString($startServerOneQuery)) == "off") {
                $error = true;
            }

        } else {
            foreach ($data['locations'] as $item) {
                $startServer = "cd {dir}; ./{program} {config} > {log_dir}/{$item}_iweb.log &";
                $startServer = preg_replace("{{config}}", $item, $startServer);
                $startServer = preg_replace("{{dir}}", config::$serverPath . "/" . $server['dir'], $startServer);
                $startServer = preg_replace("{{program}}", $server['program'], $startServer);
                $startServer = preg_replace("{{log_dir}}", config::$serverPath . "/logs", $startServer);
                if (socket::sendPacket(0, socket::packString($server['program'] . " " . $item) . socket::packString($startServer)) == "off") {
                    $error = true;
                }
            }
        }
        if (!$error) {
            system::jms("success", "Локация запущена");
            system::log("Запуск локации");
        } else {
            system::jms("danger", "Не удалось запустить локацию");
        }
    }

    static function killPid($pid)
    {
        if (is_array($pid)) {
            foreach ($pid as $item) {
                socket::sendPacket(7, socket::packInt($item));
            }
        } else
            socket::sendPacket(7, socket::packInt($pid));
    }

    static function startServer()
    {
        $error = true;
        $error1 = false;
        foreach (server::$server as $key => $server) {
            if ($key == "auth")
                $serverPIDName = $server['pid_name'][config::$serverTypeAuth];
            else
                $serverPIDName = $server['program'] . " " . $server['config'];

            $startServer = "cd {dir}; ./{program} {config} > {log_dir}/{program}_iweb.log &";

            $startServer = preg_replace("{{dir}}", config::$serverPath . "/" . $server['dir'], $startServer);
            $startServer = preg_replace("{{program}}", $server['program'], $startServer);
            $startServer = preg_replace("{{config}}", $server['config'], $startServer);
            $startServer = preg_replace("{{log_dir}}", config::$logsPath, $startServer);
            $result = socket::sendPacket(0, socket::packString($serverPIDName) . socket::packString($startServer));
           // system::debug($key . " " . $result);
            if ($result == "off") {
                $error = false;
            } elseif ($result == "0") {
                $error1 = $server['program'];
            }
        }
        if ($error) {
            system::jms("success", "Сервер запускается");
            system::log("Запуск сервера");
        } else if ($error1) {
            system::jms("danger", "Не удалось запустить " . $error1);
        } else
            system::jms("danger", "Не удалось запустить 1 или несколько сервисов");

    }

    static function stopServer($restart = false)
    {
        $error = true;
        foreach (server::$serverStop as $stop) {
            if (socket::sendPacket(1, socket::packString($stop)) == "off") {
                $error = false;
            }
        }
        socket::sendPacket(9);
        if (!$restart) {
            if (!$error) {
                system::jms("success", "Отправлена команда на выключение сервера");
                system::log("Остановка сервера");
            } else
                system::jms("danger", "Не удалось остановить 1 или несколько сервисов");
        }
    }

    static function startService($service){

        if ($service == "auth")
            $serverPIDName = server::$server[$service]['pid_name'][config::$serverTypeAuth];
        else
            $serverPIDName = server::$server[$service]['program'] . " " . server::$server[$service]['config'];

        $startServer = "cd {dir}; ./{program} {config} > {log_dir}/{program}_iweb.log &";

        $startServer = preg_replace("{{dir}}", config::$serverPath . "/" . server::$server[$service]['dir'], $startServer);
        $startServer = preg_replace("{{program}}", server::$server[$service]['program'], $startServer);
        $startServer = preg_replace("{{config}}", server::$server[$service]['config'], $startServer);
        $startServer = preg_replace("{{log_dir}}", config::$logsPath, $startServer);
        $result = socket::sendPacket(0, socket::packString($serverPIDName) . socket::packString($startServer));
        system::jms("success", "отправлена команда на включение сервиса $service");
    }

    static function stopService($service, $restart = false)
    {
            $program = (!isset(server::$server[$service]['pid_name'])) ? server::$server[$service]['program'] : server::$server[$service]['pid_name'][config::$serverTypeAuth];
            $getRecv = socket::sendPacket(2, socket::packString($program), 1024 * 100);
            if ($getRecv != "off") {
                $getProcess = explode("\n", trim($getRecv));
                $getInfo = str_getcsv(preg_replace("/\s{2,}/", ' ', $getProcess[0]), " ", "", "\n");
                self::killPid($getInfo[1]);
                if (!$restart) system::jms("success", "отправлена команда на выключение сервиса $service");
            }else{
                if (!$restart) system::jms("danger", "$service невозможно выключить повторно");
            }
    }

    static function restartService($service)
    {
        self::stopService($service, true);
        self::startService($service);
    }

    static function clearServer(){
        if (socket::sendPacket(9) == "1") {
            system::jms("success", "Кеш сервера очищен");
            system::log("Очистка кеша сервера");
        }
    }

    static function restartServer()
    {
        self::stopServer(true);
        self::startServer();
    }

}