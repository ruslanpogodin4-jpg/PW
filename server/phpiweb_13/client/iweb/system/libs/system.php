<?php

namespace system\libs;

use system\data\character\character;
use system\data\config;
use system\data\lang;
use system\libs\element\element;
use system\libs\element\element_config;
use system\models\editorModel;
use system\models\indexModel;
use system\models\serverModel;
use system\models\settingsModel;

if (!defined('IWEB')) {
    die("Error!");
}

class system
{

    private static $template, $data;
    public static $site_title, $user, $version = "1.3", $result = array("content" => "", "adr" => ""), $elStatus = 0;

    static function debug($value)
    {
        echo "<pre style='background: #bdbdbd; border: 1px solid #5b6669; border-radius: 2px; padding: 10px;word-wrap:break-word; width:98%'>";
        if (is_array($value) or is_object($value)) {
            print_r($value);
        } else {
            echo $value;
        }
        echo "</pre>";
    }

    static function accept()
    {
        $accept = explode(";", config::$accessIP);
        if (count($accept) > 0 && config::$access == "on") {
            foreach ($accept as $value) {
                if (stristr($value, "/")) {
                    $diapason = explode("/", $value);
                    $realIP = explode(".", $_SERVER['REMOTE_ADDR']);
                    $acceptIP = explode(".", $diapason[0]);
                    if ($realIP[0] == $acceptIP[0] && $realIP[1] == $acceptIP[1] && $realIP[2] >= $acceptIP[2] && $realIP[2] <= $diapason[1] && $realIP[3] >= $acceptIP[3] && $realIP[3] <= $diapason[1])
                        return true;
                } else {
                    if ($_SERVER['REMOTE_ADDR'] == $value)
                        return true;
                }
            }
        } else return true;
        return false;
    }

    static function jms($type, $message)
    {
        echo json_encode(array("type" => $type, "message" => $message));
    }

    static function run()
    {
        header("Content-Type: text/html; charset=utf-8");
        if (file_exists(dir . "/system/data/config.php")) {
            if (self::accept()) {
                if (isset($_GET['function']))
                    self::goJS($_GET['function']);
                else {
                    self::routing();

                    self::load('widgetChat');
                    self::show('widgetChat');
                    self::clear();

                    self::load("main");
                    foreach (lang::$template['menu'] as $key => $value) {
                        system::set("{lang_menu_$key}", $value);
                    }
                    self::set("{title}", config::$site_title);
                    self::set("{content}", self::$result['content']);
                    if (config::$widgetChat == "on")
                        self::set("{widgetChatPattern}", self::$result['widgetChat']);
                    else
                        self::set("{widgetChatPattern}", "");
                    self::set("{widgetChat}", config::$widgetChat);
                    self::show("main", true);
                    self::clear(true);
                }
            } else {
                die("No access");
            }
        } else {
            header("Location: install1.php");
        }
    }

    static function log($action)
    {
        if (config::$logActions == "on") {
            if (!empty($action)) {
                database::query("INSERT INTO logs (ip, date, user, action) VALUES ('" . $_SERVER['REMOTE_ADDR'] . "','" . time() . "','" . $_SESSION['user'] . "','" . $action . "')");
            }
        }
    }

    static function goJS($function)
    {
        if ((boolean)preg_match("#^[aA-zZ0-9\-_]+$#", $function)) {
            self::$user = self::isUser();
            if (self::$user) {
                switch ($_GET['function']) {
                    case "sendxml":
                        if (self::$user['xml_edit']) {
                            GRole::writeCharacterXML($_POST['id'], xml::decode($_POST['xml']), true);
                            self::log("Изменен персонаж " . $_POST['id'] . " через XML");
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "sendmail":
                        if (self::$user['send_mail']) {
                            serverModel::mail($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "teleport":
                        if (self::$user['teleport']) {
                            editorModel::teleportGD($_POST['id']);
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "nullchar":
                        if (self::$user['null_exp_sp']) {
                            editorModel::nullSpEp($_POST['id']);
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "levelup":
                        if (self::$user['level_up']) {
                            editorModel::levelUp($_POST['id'], $_POST['level']);
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "sendmsg":
                        if (self::$user['send_msg']) {
                            serverModel::sendChatMessage($_POST['msg'], $_POST['chanel']);
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "startServer":
                        if (self::$user['server_manager']) {
                            serverModel::startServer();
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                        case "startService":
                        if (self::$user['server_manager']) {
                            serverModel::startService($_POST['service']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "restartServer":
                        if (self::$user['server_manager']) {
                            serverModel::restartServer();
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "restartService":
                        if (self::$user['server_manager']) {
                            serverModel::restartService($_POST['service']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "stopServer":
                        if (self::$user['server_manager']) {
                            serverModel::stopServer();
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "clearServer":
                        if (self::$user['server_manager']) {
                            serverModel::clearServer();
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "stopService":
                        if (self::$user['server_manager']) {
                            serverModel::stopService($_POST['service']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "kickrole":
                        if (self::$user['kick_role']) {
                            indexModel::kickUser($_POST['id'], 1, "GM Edit Account");
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "addcash":
                        if (self::$user['add_gold']) {

                            editorModel::addGold($_POST['id'], $_POST['gold']);
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "getrole":
                        editorModel::charsList($_POST['id']);
                        break;

                    case "delrole":
                        if (self::$user['del_role']) {
                            editorModel::deleteRole($_POST['id']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "renamerole":
                        if (self::$user['rename_role']) {
                            editorModel::renameRole($_POST['id'], $_POST['oldname'], $_POST['newname']);
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "checkGM":
                        if (self::$user['gm_manager']) {
                            indexModel::gm($_POST['id'], "check");
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "managerGM":
                        if (self::$user['gm_manager']) {
                            indexModel::gm($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "getmailitems":
                        if (self::$user['send_mail']) {
                            echo serverModel::getItemsMail($_POST['id']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "sendmailall":
                        if (self::$user['send_mail']) {
                            serverModel::sendMailAllOnline($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "clearLog":
                        if (self::$user['logs']) {
                            settingsModel::clearLog();
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "icon":
                        if (isset($_GET['name'])) $name = $_GET['name']; else $name = 'unknown.dds';

//                        if (database::query("SELECT * FROM items_icons WHERE name='{$name}'")) {
//                            if (database::num() > 0) {
//                                $icon = database::assoc();
//                            } else {
//                                $re = database::query("SELECT * FROM items_icons WHERE name='unknown.dds'");
//                                $icon = database::assoc($re);
//                            }
                            Header("Content-type: image/jpeg");
                            echo base64_decode($name);
                       // }
                        break;

                    case "goreadelement":
                        if (self::$user['settings']) {
                            element::read();
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "elementStatus":
                        // if (self::$user['settings']) {
                        echo $_COOKIE['ELEMENT_LIST'] . "<br>";
                        echo $_COOKIE['ELEMENT_LISTS'] . "<br>";

                        // } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "savesettings":
                        if (self::$user['settings']) {
                            settingsModel::saveSettings($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "gouploadicon":
                        if (self::$user['settings']) {
                            func::uploadImage("iconlist_ivtrm");
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "killpid":
                        if (self::$user['server_manager']) {
                            serverModel::killPid($_POST['pid']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "killselectloc":
                        if (self::$user['server_manager']) {
                            serverModel::killPid($_POST['locations']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "ban":
                        if (self::$user['ban']) {
                            editorModel::ban($_POST['id'], $_POST['time'], $_POST['type'], $_POST['reason']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "start_location":
                        if (self::$user['server_manager']) {
                            serverModel::startLocation($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");

                        break;

                    case "adduserserver":
                        if (self::$user['gm_manager']) {
                            indexModel::addUser($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "getchat":
                        $chat = func::readChat();
                        if (isset($chat[$_GET['chl']])) {
                            $tag = "";
                            $color = "white";
                            switch ($_GET['chl']) {
                                case 0:
                                    $tag = "<span class='badge badge-secondary'>Осн</span>";
                                    $color = "white";
                                    break;

                                case 1:
                                    $tag = "<span class='badge badge-warning'>Мир</span>";
                                    $color = "yellow";
                                    break;

                                case 2:
                                    $tag = "<span class='badge badge-success'>Группа</span>";
                                    $color = "lightgreen";
                                    break;

                                case 3:
                                    $tag = "<span class='badge badge-primary'>Клан</span>";
                                    $color = "lightblue";

                                    break;
                            }
                            $chat[$_GET['chl']] = array_reverse($chat[$_GET['chl']], true);
                            foreach ($chat[$_GET['chl']] as $msg) {
                                if ($msg['clan'] != 0) $clan = "->" . $msg['clan']; else $clan = "";
                                echo $tag . " <span class='charName'>" . $msg['role'] . $clan . "</span>: <span style='color: $color'>" . $msg['msg'] . "</span><br>";
                            }
                        } else echo "Сообщений не найдено!";
                        break;
                    case "get_process":
                        $statusProcess = json_encode(serverModel::checkStatusServer());
                        echo $statusProcess;
                        break;
                    case "get_status":
                        $status = json_encode(serverModel::statusServer());
                        echo $status;
                        break;
                    case "get_locations":
                        $locations = serverModel::getStartedLocation();
                        echo $locations;
                        break;

                    case "addUserIweb":
                        if (self::$user['settings']) {
                            settingsModel::addUser($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "getUserIweb":
                        if (self::$user['settings']) {
                            settingsModel::getUser($_POST['id']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "updateUserIweb":
                        if (self::$user['settings']) {
                            settingsModel::updateUser($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "delUserIweb":
                        if (self::$user['settings']) {
                            settingsModel::delUser($_POST['id']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "addGroupIweb":
                        if (self::$user['settings']) {
                            settingsModel::addGroup($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "getGroupIweb":
                        if (self::$user['settings']) {
                            settingsModel::getGroup($_POST['id']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "updateGroupIweb":
                        if (self::$user['settings']) {
                            settingsModel::updateGroup($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "delGroupIweb":
                        if (self::$user['settings']) {
                            settingsModel::delGroup($_POST['id']);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "sendvisual":
                        if (self::$user['visual_edit']) {
                            editorModel::saveVisual($_POST);
                        } else system::jms("info", "У вас нет доступа к данной функции");
                        break;

                    case "readFly":

                        $role = GRole::readCharacter($_POST['id']);
                        stream::putRead($role['role'][$_POST['param1']][$_POST['param2']][$_POST['key']]['data']['value'],0);
                        $data = GRole::readItemData(character::$items[$_POST['type']]);
                       // system::debug($data);
                        echo json_encode($data);
                        stream::putRead(stream::$readData_copy, stream::$p_copy);
                        break;

                    case "writeFly":
                        //64002000360000002e01000048710000487100002c00010002000000c55c00000d000000000000005b020000160400000000000000000000100000000000904000000000020000000000000000000000030000008d250000040000008e25000000000040ed2300008e000000

                        stream::putWrite("");
                        GRole::writeItemData(json_decode($_POST['data'], true));
                        stream::putWrite(stream::$writeData_copy);
                        echo bin2hex(substr(stream::$writeData_copy, 0, strlen(stream::$writeData_copy)));
                        break;
                }
            } else {
                if ($function == "auth") {
                    indexModel::login($_POST['username'], $_POST['password']);
                }
            }
        } else self::notFound();
    }

    static function isUser()
    {
        if (isset($_SESSION['id'])) {
            database::query("SELECT users.*, groups.* FROM users, groups WHERE users.id='" . database::safesql($_SESSION['id']) . "' AND users.group_id=groups.id_group");
            if (database::num() > 0)
                return database::assoc();
        }
        return false;
    }

    static function routing()
    {
        self::$user = self::isUser();
        $controller = (isset($_GET["controller"]) && (boolean)preg_match("#^[aA-zZ0-9\-_]+$#", $_GET["controller"]) == true) ? $_GET["controller"] : "";
        $page = (isset($_GET["page"]) && (boolean)preg_match("#^[aA-zZ0-9\-_]+$#", $_GET["page"]) == true) ? $_GET["page"] : "";
        if (!self::$user) {
            call_user_func("system\\controllers\\indexContrl::login");
        } else {
            if ($controller == "")
                if (is_callable("system\\controllers\\indexContrl::index"))
                    call_user_func("system\\controllers\\indexContrl::index");
                else
                    system::notFound();
            else {
                $action = (empty($page)) ? "index" : $page;
                if (is_callable("system\\controllers\\{$controller}Contrl::" . $action))
                    call_user_func("system\\controllers\\{$controller}Contrl::" . $action);
                else
                    system::notFound();
            }
        }

    }

    static function info($title, $message, $type = 'primary')
    {
        self::load('info');
        self::set("{title}", $title);
        self::set("{message}", $message);
        self::set("{type}", $type);
        self::show("content");
        self::clear();

    }

    static function notFound()
    {
        $page404 = file_get_contents(dir . "/system/template/404.html");
        header("HTTP/1.0 404 Not Found");
        header("HTTP/1.1 404 Not Found");
        header("Status: 404 Not Found");
        echo $page404;
        die();
    }

//Templater
    static function load($name)
    {
        $path = dir . DIRECTORY_SEPARATOR . "system" . DIRECTORY_SEPARATOR . "template" . DIRECTORY_SEPARATOR . $name . ".html";
        if (file_exists($path)) {
            self::$template = file_get_contents($path);
            self::$template = str_replace("{adr}", config::$site_adr, self::$template);
            self::$template = str_replace("{site_title}", self::$site_title, self::$template);
        } else
            self::debug("Template file not found: $path");
    }

    static function set($name, $value)
    {
        self::$data[$name] = $value;
    }

    public static function foreachTpl($name, $array, $prefix = "", $data = array(), $replace = array())
    {
        $regex = '/\[(foreach-' . $name . ')\]((?>(?R)|.)*?)\[\/\1\]/is';
        preg_match($regex, self::$template, $matches);
        $newKey = array();
        $newValue = array();
        $newTemplate = "";
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $key1 => $value1) {
                    $newKey[] = "{" . $prefix . $key1 . "}";
                    if (empty($data))
                        $newValue[] = $value1;
                    else {
                        if (isset($data[$value1])) {
                            if (empty($replace))
                                $newValue[] = $data[$value1];
                            else {
                                $newValue[] = str_replace("{%%}", $data[$value1], $replace[$value1]);
                            }
                        } else $newValue[] = $value1;
                    }
                }
                $newTemplate .= str_replace($newKey, $newValue, trim($matches[2]));
                $newKey = array();
                $newValue = array();
            }
        }
        self::$template = preg_replace($regex, $newTemplate, self::$template);
    }

    static function show($name, $show = false)
    {
        if (self::$data) {
            foreach (self::$data as $key => $value) {
                self::$template = str_replace($key, $value, self::$template);
            }
        }

        self::$result[$name] = self::$template;

        if ($show)
            echo self::$result[$name];

    }

    static function clear($full = false)
    {
        self::$template = "";
        self::$data = array();
        if ($full) {
            self::$result = array("content" => "", "adr" => "");
        }
    }

}