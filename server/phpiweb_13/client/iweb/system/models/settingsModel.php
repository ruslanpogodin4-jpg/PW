<?php

namespace system\models;

use system\libs\database;
use system\libs\system;

class settingsModel
{
    //work
    static function getLogs()
    {
        if ($get = database::query("SELECT * FROM logs ORDER BY id DESC LIMIT 15")) {
            $log = array();
            $rows_num = database::num($get);
            if ($rows_num > 0) {
                for ($i = 0; $rows_num > $i; $i++) {
                    $log[$i] = database::assoc($get);
                }
                return $log;
            }
        }
        return false;
    }

    static function clearLog()
    {
        if (database::query("delete from logs")) {
            system::jms("success", "Журнал очищен");
            system::log("Очистка журнала");
        } else system::jms("danger", "Журнал не очищен");
    }

    static function selectSettings($name, $value, $data)
    {

        $template = "<select class=\"form-control form-control-sm\" name='config[$name]'>";
        foreach ($data as $key => $val) {
            if ($value == $key) $selected = "selected"; else $selected = "";
            $template .= "<option value='$key' $selected>$val</option>";
        }
        $template .= "</select>";
        return $template;
    }

    static function listDir($path)
    {
        $dirArray = array();
        $dir = new \DirectoryIterator($path);
        foreach ($dir as $fileInfo) {
            if ($fileInfo->isDir() && !$fileInfo->isDot()) {
                $dirArray[$fileInfo->getFilename()] = $fileInfo->getFilename();
            }
        }
        return $dirArray;
    }

    static function saveSettings($data)
    {

        $configFile = "<?php\nnamespace system\data;\nif (!defined('IWEB')) {die(\"Error!\");}\nclass config\n{\n";

        foreach ($data['config'] as $key => $value) {
            $configFile .= "\tstatic $$key = \"$value\";\n";
        }

        $configFile .= "}";

        if ($file = @fopen(dir . "/system/data/config.php", "w")) {
            if (fwrite($file, $configFile)) {
                system::jms("success", "Файл настроек записан");
            } else {
                system::jms("danger", "Файл настроек не записан");
            }
            fclose($file);
        } else {
            system::jms("danger", "Файл настроек не найден или не доступен");
        }
    }

    //iweb user
    static function addUser($data)
    {
        $data['username'] = strtolower(trim($data['username']));
        $data['password'] = trim($data['password']);
        $data['group'] = strtolower(trim($data['group']));

        if (!empty($data['username'])) {
            if (!empty($data['password'])) {
                if (!empty($data['group'])) {
                    if (database::query("INSERT INTO users (name, password, group_id) VALUES ('" . $data['username'] . "','" . md5($data['password']) . "','" . $data['group'] . "')")) {
                        $newUser = database::squery("SELECT users.*, groups.* FROM users, groups WHERE name='" . database::safesql($data['username']) . "' AND users.group_id = groups.id_group");
                        echo json_encode(array(
                            "type" => "success",
                            "message" => "Пользователь <b>{$data['username']}</b> добавлен",
                            "id" => $newUser['id'],
                            "username" => $newUser['name'],
                            "group" => $newUser['title'],
                        ));
                    } else {
                        system::jms("danger", "errosr");
                    }
                } else {
                    system::jms("danger", "group");
                }
            } else {
                system::jms("danger", "password");
            }
        } else {
            system::jms("danger", "username");
        }

    }

    static function getUser($id)
    {
        if ($user = database::squery("SELECT id,name, group_id FROM users WHERE id='" . database::safesql($id) . "'"))
            echo json_encode($user);
    }

    static function updateUser($data)
    {
        $data['username'] = strtolower(trim($data['username']));
        $data['password'] = trim($data['password']);

        $password = (!empty($data['password'])) ? "password='" . md5(database::safesql($data['password'])) . "'," : "";

        if (!empty($data['username'])) {
            if (database::query("UPDATE users SET name='" . database::safesql($data['username']) . "', {$password} group_id='" . database::safesql($data['group']) . "' WHERE id='" . database::safesql($data['id']) . "'")) {
                system::jms("success", "Пользователь <b>{$data['username']}</b> изменен");

            } else {
                system::jms("danger", "Не удалось изменить пользователя");
            }
        } else {
            system::jms("danger", "Пустое имя пользователя");
        }
    }

    static function delUser($id)
    {
        if (!empty($id)) {
            if (database::query("DELETE FROM users WHERE id='" . database::safesql($id) . "'"))
                system::jms("success", "Пользователь удален");
            else system::jms("danger", "Ошибка при удалении пользователя");

        } else system::jms("danger", "ID пользователя пустое");
    }

    //iweb group
    static function addGroup($data)
    {
        $data['iwebTitle'] = trim($data['iwebTitle']);
        if (!empty($data['iwebTitle'])) {
            if (database::query("INSERT INTO groups (title,xml_edit,visual_edit,gm_manager,kick_role,ban,add_gold,level_up,rename_role,teleport,null_exp_sp,del_role,server_manager,send_msg,send_mail,settings,logs) VALUES ('" . $data['iwebTitle'] . "','" . $data['xml_edit'] . "','" . $data['visual_edit'] . "','" . $data['gm_manager'] . "','" . $data['kick_role'] . "','" . $data['ban'] . "','" . $data['add_gold'] . "','" . $data['level_up'] . "','" . $data['rename_role'] . "','" . $data['teleport'] . "','" . $data['null_exp_sp'] . "','" . $data['del_role'] . "','" . $data['server_manager'] . "','" . $data['send_msg'] . "','" . $data['send_mail'] . "','" . $data['settings'] . "','" . $data['logs'] . "')")) {
                system::jms("success", "successs");
            } else {
                system::jms("danger", "errosr");
            }
        } else {
            system::jms("danger", "title");
        }

    }

    static function getGroup($id)
    {
        if ($groups = database::squery("SELECT * FROM groups WHERE id_group='" . database::safesql($id) . "'"))
            echo json_encode($groups);
    }

    static function updateGroup($data)
    {
        $query = "";
        foreach ($data as $key => $val) {
            if ($key != "id") {
                $query .= "$key='" . database::safesql($val) . "', ";
            }
        }
        $query = rtrim($query, ", ");

        if (database::query("UPDATE groups SET $query WHERE id_group='" . database::safesql($data['id']) . "'")) {
            system::jms("success", "Группа <b>{$data['title']}</b> изменена");
        } else
            system::jms("danger", "Не удалось изменить группу");


    }

    static function delGroup($id)
    {
        if (!empty($id)) {
            if (database::query("DELETE FROM groups WHERE id_group='" . database::safesql($id) . "'"))
                system::jms("success", "Группа удалена");
            else system::jms("danger", "Ошибка при удалении группы");

        } else system::jms("danger", "ID группы пустое");
    }

}