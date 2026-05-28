<?php

namespace system\models;

use system\data\character\character;
use system\data\config;
use system\data\lang;
use system\libs\database;
use system\libs\func;
use system\libs\GRole;
use system\libs\socket;
use system\libs\stream;
use system\libs\struct\GRoleData;
use system\libs\struct\roleLevelUp;
use system\libs\system;

if (!defined('IWEB')) {
    die("Error!");
}

class editorModel
{
    static $visual;

    static function saveVisual($data)
    {
        //ini_set('memory_limit', '-1');
        ini_set('memory_limit', '-1');

        $role = GRole::readCharacter($data['id'], false);
        // system::debug($data['visual']);
        foreach ($data['visual'] as $key => $value) {

            $param = explode("-", $key);
            // system::debug($param);
            if (isset($param[1])) {
                if (isset($param[2])) {
                    if (isset($param[3]))
                        $role['role'][$param[0]][$param[1]][$param[2]][$param[3]]['value'] = $value;
                    else
                        $role['role'][$param[0]][$param[1]][$param[2]]['value'] = $value;
                } else {
                    if ($param[1] == "eqp") {
                        $items = json_decode($value, true);
                        for ($i = 0; $i < count($items); $i++) {
                            $items[$i]['data']['value'] = @pack("H*", $items[$i]['data']['value']);
                        }
                        $role['role']['equipment']['eqpcount']['value'] = count($items);
                        $role['role'][$param[0]][$param[1]] = $items;
                    } elseif ($param[1] == "inv") {
                        $items = json_decode($value, true);
                        for ($i = 0; $i < count($items); $i++) {
                            $items[$i]['data']['value'] = @pack("H*", $items[$i]['data']['value']);
                        }
                        $role['role']['pocket']['invcount']['value'] = count($items);
                        $role['role'][$param[0]][$param[1]] = $items;
                    } elseif ($param[1] == "store") {

                        $items = json_decode($value, true);
                        for ($i = 0; $i < count($items); $i++) {
                            $items[$i]['data']['value'] = @pack("H*", $items[$i]['data']['value']);
                        }
                        $role['role']['storehouse']['storecount']['value'] = count($items);
                        $role['role'][$param[0]][$param[1]] = $items;

                    } elseif ($param[1] == "card") {

                        $items = json_decode($value, true);
                        for ($i = 0; $i < count($items); $i++) {
                            $items[$i]['data']['value'] = @pack("H*", $items[$i]['data']['value']);
                        }
                        $role['role']['storehouse']['cardcount']['value'] = count($items);
                        $role['role'][$param[0]][$param[1]] = $items;

                    } else {
                        $role['role'][$param[0]][$param[1]]['value'] = $value;

                    }
                }
            } else {
                $role['role'][$param[0]] = $value;
            }
        }
        GRole::writeCharacter($data['id'], $role, false);
    }

    static function selectProp($arr, $value)
    {
        $result = "";
        foreach ($arr as $key => $val) {
            if ($value == $key) $active = " selected"; else $active = "";
            $result .= "<option value='{$key}' {$active}>$val</option>";
        }
        return $result;
    }

    //work
    static function levelUp($id, $level)
    {
        $role = GRole::readCharacter($id, false);
        if ($role) {
            if (is_numeric($id) && !empty($id)) {
                if ($level >= 1) {
                    $levelUP = new roleLevelUp();

                    $role['role']['status']['pp']['value'] = $level * 5;
                    $role['role']['status']['property']['vitality']['value'] = 5;
                    $role['role']['status']['property']['energy']['value'] = 5;
                    $role['role']['status']['property']['strength']['value'] = 5;
                    $role['role']['status']['property']['agility']['value'] = 5;

                    $levelUP->levelProperty($role['role']['base']['cls']['value'], $level, $role['role']['status']['property']);
                    $role['role']['status']['level']['value'] = $level;
                    $role['role']['status']['exp']['value'] = 0;
                    if ($role['role']['status']['hp']['value'] < $role['role']['status']['property']['max_hp']['value']) $role['role']['status']['property']['hp']['value'] = $role['role']['status']['property']['max_hp']['value'];
                    if ($role['role']['status']['mp']['value'] < $role['role']['status']['property']['max_mp']['value']) $role['role']['status']['property']['mp']['value'] = $role['role']['status']['property']['max_mp']['value'];

                    if (GRole::writeCharacter($id, $role, false))
                        system::log("Изменен уровень персонажа " . $id);
                } else
                    system::jms("info", "Уровень меньше 1 или не указан");
            } else
                system::jms("info", lang::$notValidCharID);
        } else
            system::jms("danger", "Ошибка получение персонажа");
    }

    //work
    static function teleportGD($roleID)
    {
        if (!empty($roleID) && is_numeric($roleID)) {
            if ($role = GRole::readCharacter($roleID, true)) {
                $role['role']['status']['posx']['value'] = 1284.897;
                $role['role']['status']['posy']['value'] = 219.618;
                $role['role']['status']['posz']['value'] = 1130.428;
                $role['role']['status']['worldtag']['value'] = 1;
                if (GRole::writeCharacter($roleID, $role, true))
                    system::log("Переименование телепортирован в $roleID ГД");
            } else
                system::jms("danger", "Ошибка получение персонажа");
        } else
            system::jms("danger", lang::$notValidCharID);
    }

    //work
    static function nullSpEp($roleID)
    {
        if (!empty($roleID) && is_numeric($roleID)) {
            if ($role = GRole::readCharacter($roleID, true)) {
                $role['role']['status']['sp']['value'] = 0;
                $role['role']['status']['exp']['value'] = 0;
                if (GRole::writeCharacter($roleID, $role, true))
                    system::log("Обнуление духа и опыта " . $roleID);
            } else
                system::jms("danger", "Ошибка получение персонажа");
        } else
            system::jms("danger", lang::$notValidCharID);
    }

    //work
    static function addGold($roleID, $count)
    {
        if (!empty($roleID) && is_numeric($roleID)) {
            stream::writeInt32($roleID);
            stream::writeInt32($count);
            stream::pack(character::$pack['DebugAddCash']);
            if (socket::sendPacket(6, socket::packInt(config::$dbPort) . stream::$writeData) != "server:0") {
                system::jms("success", "Золото отправлено");
                system::log("Отправлено золото $count на аккаунт " . $roleID);
            } else {
                system::jms("danger", "Золото не отправлено, возможно сервер выключен");
            }
        } else
            system::jms("danger", lang::$notValidCharID);
    }

    //work
    static function deleteRole($id)
    {
        if (!empty($id) && is_numeric($id)) {
            stream::writeInt32($id, true, -1);
            stream::writeByte(0);
            stream::pack(character::$pack['DeleteRole']);
            if (socket::sendPacket(6, socket::packInt(config::$dbPort) . stream::$writeData) != "server:0") {
                system::jms("success", "Персонаж удален");
                system::log("Удаление персонажа " . $id);
            } else
                system::jms("danger", "Не удалось удалить персонажа");
        } else
            system::jms("danger", lang::$notValidCharID);
    }

    //work
    static function renameRole($id, $oldName, $newName)
    {
        if (!empty($id) && is_numeric($id)) {
            stream::writeInt32($id, true, -1);
            stream::writeString($oldName);
            stream::writeString($newName);
            stream::pack(character::$pack['RenameRole']);;
            if (socket::sendPacket(6, socket::packInt(config::$dbPort) . stream::$writeData) != "server:0") {
                system::jms("success", "Персонаж переименован");
                system::log("Переименование персонажа $id с $oldName на $newName");
            } else
                system::jms("danger", "Не удалось переименовать персонажа");
        } else
            system::jms("danger", lang::$notValidCharID);
    }

    //work
    static function charsList($id)
    {
        if (!empty($id) && is_numeric($id)) {
            $list = GRoleData::getListRoles($id);
            $roleList = "<table>";
            if ($list['count']['value'] > 0) {
                foreach ($list['roles'] as $role) {
                    $roleList .= "<tr>  <td>" . $role['id']['value'] . "&nbsp;&blacktriangleright;&nbsp;</td> <td><b> " . $role['name']['value'] . "&nbsp;&nbsp;&nbsp; </b> </td>
<td><a class=\"badge badge-primary\" href='" . config::$site_adr . "/?controller=editor&page=xml&id=" . $role['id']['value'] . "'>XML</a> 
<a class=\"badge badge-success\" href='" . config::$site_adr . "/?controller=editor&id=" . $role['id']['value'] . "'>Редактор</a> 
<a class=\"badge badge-warning\" href='" . config::$site_adr . "/?controller=server&page=mail&id=" . $role['id']['value'] . "'>Почта</a>
<a class=\"badge badge-info\" href='javascript:void(0)' data-toggle=\"modal\" data-target=\"#ban\" onclick='ban(" . $role['id']['value'] . ", 3)'>Бан чата</a>
<a class=\"badge badge-light\" href='javascript:void(0)' data-toggle=\"modal\" data-target=\"#ban\" onclick='ban(" . $role['id']['value'] . ", 4)'>Бан перса</a>
<a class=\"badge badge-danger\" href='javascript:void(0)' onclick='goDelChar(" . $role['id']['value'] . ")'>Удалить</a></td></tr>";
                }
                $roleList .= "</table>";
                echo $roleList;
            } else
                echo "<p class=\"alert alert-info\">Персонажи не найдены или не удалось получить данных от сервера</p>";
        } else
            system::jms("danger", lang::$notValidCharID);
    }

    static function getItemFromElement($id, $itemArray)
    {
        // if ($item1 = database::squery("SELECT * FROM items WHERE itemID='$id'")) {
        if (isset($itemArray[$id])) {
            $iconNameArr = explode("/", $itemArray[$id]['icon']);
            $item['name'] = $itemArray[$id]['name'];
            $item['list'] = $itemArray[$id]['list'];
            $item['icon'] = end($iconNameArr);
            if (isset($itemArray[$id]['color']))
                $item['color'] = $itemArray[$id]['color'];
            else
                $item['color'] = 0;

        } else {
            $item['name'] = "Неизвестно";
            $item['list'] = 999;
            $item['icon'] = "unknown.dds";
            $item['color'] = 0;
        }
        return $item;
    }

    static function ban($id, $time, $type, $reason)
    {

        stream::writeInt32(-1);
        stream::writeInt32(0);
        stream::writeInt32($id);
        stream::writeInt32($time);
        stream::writeString($reason);
        switch ($type) {
            case 1:
                $msg = "Аккаунт $id заблокирован на $time";
                stream::pack(character::$pack['accForbid']); // бан акка
                break;
            case 2:
                $msg = "Чат на аккаунт $id заблокирован на $time";
                stream::pack(character::$pack['chatAccForbid']); // бан чата акка
                break;
            case 3:
                $msg = "Чат на персонаже $id заблокирован на $time";
                stream::pack(character::$pack['chatCharForbid']); // бан чата персонажа
                break;
            case 4:
                $msg = "Персонаж $id заблокирован на $time";
                stream::pack(character::$pack['forbid']); // бан персонажа
                break;
            default:
                $msg = "Не верный тип бана";
                break;
        }
        if (socket::sendPacket(4, socket::packInt(config::$gdeliverydPort) . stream::$writeData)) {
            system::log($msg);
            system::jms("success", $msg);
        } else
            system::jms("danger", "Ошибка блокировки персонажа/аккаунта");
    }

    static function getBonus()
    {
        $fp = fopen(dir . '/system/data/bonus.txt', "r");
        if (!$fp) die('Error opening file bonus.txt');
        $bonus = array();
        $cnt = 0;
        while (!feof($fp)) {
            $line = fgets($fp);
            $line = substr($line, 0, strlen($line) - 1);
            $bonus['names'][$cnt] = $line;
            $line = fgets($fp);
            $line = explode(',', substr($line, 0, strlen($line) - 1));
            $bonus['ids'][$cnt] = $line;
            $cnt++;
        }
        fclose($fp);
        return $bonus;
    }

    static function getSharpening()
    {
        $sharpening = array();
        $fp = fopen(dir . '/system/data/sharpening.txt', "r");
        if (!$fp) die('Error opening file sharpening.txt');
        $cnt = 0;
        while (!feof($fp)) {
            $line = fgets($fp);
            $line = substr($line, 0, strlen($line) - 1);
            $sharpening['names'][$cnt] = $line;
            $line = fgets($fp);
            $line = explode(',', substr($line, 0, strlen($line) - 1));
            $sharpening['ids'][$cnt] = $line;
            $cnt++;
        }
        fclose($fp);
        return $sharpening;
    }

    static function checkSharpening($id, $sharpening)
    {
        foreach ($sharpening['ids'] as $i => $val) {
            if (in_array($id, $val)) return true;
        }
        return false;
    }

    static function getWeapon($item, $sharpening, $bonusData, $fileElement)
    {
        $dot = 0;
        $cell = 0;
        $addAtkSpeed = 0;
        $stone = "";
        $bonusItem = "";

        stream::putRead($item['data']['value'], 0);
        $itemRes = GRole::readItemData(character::$items['weapon']);
        stream::putRead(stream::$readData_copy, stream::$p_copy);

        if (isset($itemRes['BonusInfo']['bonus'])) {
            foreach ($itemRes['BonusInfo']['bonus'] as $key => $bonus) {
                if (($bonus['id']['value'] == 331) || ($bonus['id']['value'] == 337) || ($bonus['id']['value'] == 338) || ($bonus['id']['value'] == 339))
                    $addAtkSpeed += $bonus['stat']['value'];
                elseif (($bonus['type']['value'] == 16384) && self::checkSharpening($bonus['id']['value'], $sharpening)) {
                    $dot = $bonus['dopStat1']['value'];
                    $addBonus = func::bonus($bonus['id']['value'], $bonusData, $bonus['stat']['value']);
                    $bonusItem .= "<br><span style=\"color: lightcoral;\">Заточка: " . $addBonus . "</span>";
                    $addBonus = explode(" +", $addBonus);
                    ($itemRes['MinPhysAtk']['value']) ? $itemRes['MinPhysAtk']['value'] += $addBonus[1] : $itemRes['MinPhysAtk']['value'];
                    ($itemRes['MaxPhysAtk']['value']) ? $itemRes['MaxPhysAtk']['value'] += $addBonus[1] : $itemRes['MaxPhysAtk']['value'];
                    ($itemRes['MinMagAtk']['value']) ? $itemRes['MinMagAtk']['value'] += $addBonus[1] : $itemRes['MinMagAtk']['value'];
                    ($itemRes['MaxMagAtk']['value']) ? $itemRes['MaxMagAtk']['value'] += $addBonus[1] : $itemRes['MaxMagAtk']['value'];
                } elseif ($bonus['type']['value'] == 40960) {
                    $stoneEl = editorModel::getItemFromElement($itemRes['cellInfo']['cellStone'][$cell]['id']['value'], $fileElement);
                    $stone .= "<br><span style=\"color: #a1e2f1\">" . $stoneEl['name'] . " " . func::bonus($bonus['id']['value'], $bonusData, $bonus['stat']['value']) . "</span>";
                    $cell++;
                } else
                    $bonusItem .= "<br><span style=\"color: #835bff\">" . func::bonus($bonus['id']['value'], $bonusData, $bonus['stat']['value']) . "</span>";
            }
        }
        $dot = ($dot != 0) ? "+$dot" : "";
        $cellDesk = ($itemRes['cellInfo']['cellCount']['value'] > 0) ? "(ячеек: " . $itemRes['cellInfo']['cellCount']['value'] . ")" : "";
        $description = " $cellDesk $dot <br>Уровень " . $itemRes['Rang']['value'];
        $description .= "<br>Скорость атаки " . round(20 / ($itemRes['AtkSpeed']['value'] - $addAtkSpeed), 2);
        $description .= "<br>Дальность " . $itemRes['Distance']['value'];
        $description .= ($itemRes['MinPhysAtk']['value'] != 0) ? "<br>Физическая атака " . $itemRes['MinPhysAtk']['value'] . "-" . $itemRes['MaxPhysAtk']['value'] : "";
        $description .= ($itemRes['MinMagAtk']['value'] != 0) ? "<br>Магическая атака " . $itemRes['MinMagAtk']['value'] . "-" . $itemRes['MaxMagAtk']['value'] : "";
        $description .= "<br>Прочность " . ($itemRes['strength']['value'] / 100) . "/" . ($itemRes['maxStrength']['value'] / 100);
        $description .= ($itemRes['class']['value'] != func::allCharSum()) ? "<br>Ограниечение по классу " . func::getCharClass($itemRes['class']['value']) : "";
        $description .= "<br>Требуемый уровень " . $itemRes['level']['value'];
        $description .= ($itemRes['strong']['value'] != 0) ? "<br>Требуемая сила " . $itemRes['strong']['value'] : "";
        $description .= ($itemRes['endurance']['value'] != 0) ? "<br>Требуемая выносливость " . $itemRes['endurance']['value'] : "";
        $description .= ($itemRes['agility']['value'] != 0) ? "<br>Требуемая ловкость " . $itemRes['agility']['value'] : "";
        $description .= ($itemRes['intellect']['value'] != 0) ? "<br>Требуемая интеллект  " . $itemRes['intellect']['value'] : "";
        $description .= (!empty($bonusItem)) ? $bonusItem : "";
        $description .= (!empty($stone)) ? $stone : "";
        $description .= (!empty($itemRes['creator']['value'])) ? "<br><span style=\"color: lawngreen\">Создатель: " . func::reColorDesc($itemRes['creator']['value']) . "</span>" : "";

        return $description;
    }

    static function getFly($item)
    {
        stream::putRead($item['data']['value'], 0);
        $itemRes = GRole::readItemData(character::$items['fly']);
        //system::debug($itemRes);
        stream::putRead(stream::$readData_copy, stream::$p_copy);
        $description = "<br>Уровень " . $itemRes['element_level']['value'] . "<br>";
        $description .= "Обыкновенный полет +" . round($itemRes['speed_increase1']['value'], 2) . " м\сек<br>";
        $description .= "Ускоренные полет +" . round($itemRes['speed_increase2']['value'], 2) . " м\сек<br>";
        $description .= "Время действия " . $itemRes['max_time']['value'] . "\\" . $itemRes['cur_time']['value'] . " (сек)<br>";
        $description .= "Ограничение по классу " . func::getCharClass($itemRes['require_class']['value']) . "<br>";
        $description .= "Требуемый уровень " . $itemRes['require_level']['value'];
        $description .= (!empty($itemRes['creator']['value'])) ? "<br><span style=\"color: lawngreen\">Создатель: " . $itemRes['creator']['value'] . "</span>" : "";
        return $description;
    }

    static function getFashion($item)
    {
        stream::putRead($item['data']['value'], 0);
        $itemRes = GRole::readItemData(character::$items['fashion']);
        stream::putRead(stream::$readData_copy, stream::$p_copy);
        $description = '<br>Требуемый уровень <b>' . $itemRes['require_level']['value'] . '</b>';
        $description .= '<br>Пол <b>' . lang::$gender[$itemRes['gender']['value']] . '</b>';
        return $description;
    }

    static function getArmor($item, $sharpening, $bonusData, $fileElement)
    {
        $dot = 0;
        $cell = 0;
        $addAtkSpeed = 0;
        $stone = "";
        $bonusItem = "";

        stream::putRead($item['data']['value'], 0);
        $itemRes = GRole::readItemData(character::$items['armor']);
        stream::putRead(stream::$readData_copy, stream::$p_copy);

        if (isset($itemRes['BonusInfo']['bonus'])) {
            foreach ($itemRes['BonusInfo']['bonus'] as $key => $bonus) {
                if (($bonus['id']['value'] == 331) || ($bonus['id']['value'] == 337) || ($bonus['id']['value'] == 338) || ($bonus['id']['value'] == 339))
                    $addAtkSpeed += $bonus['stat']['value'];
                elseif (($bonus['type']['value'] == 16384) && self::checkSharpening($bonus['id']['value'], $sharpening)) {
                    $dot = $bonus['dopStat1']['value'];
                    $addBonus = func::bonus($bonus['id']['value'], $bonusData, $bonus['stat']['value']);
                    $bonusItem .= "<br><span style=\"color: lightcoral;\">Заточка: " . $addBonus . "</span>";
                    $addBonus = explode(" +", $addBonus);
                    $itemRes['HP']['value'] += $addBonus[1];
                } elseif ($bonus['type']['value'] == 40960) {
                    $stoneEl = editorModel::getItemFromElement($itemRes['cellInfo']['cellStone'][$cell]['id']['value'], $fileElement);
                    $stone .= "<br><span style=\"color: #a1e2f1\">" . $stoneEl['name'] . " " . func::bonus($bonus['id']['value'], $bonusData, $bonus['stat']['value']) . "</span>";
                    $cell++;
                } else
                    $bonusItem .= "<br><span style=\"color: #835bff\">" . func::bonus($bonus['id']['value'], $bonusData, $bonus['stat']['value']) . "</span>";
            }
        }
        $dot = ($dot != 0) ? "+$dot" : "";
        $cellDesk = ($itemRes['cellInfo']['cellCount']['value'] > 0) ? "(ячеек: " . $itemRes['cellInfo']['cellCount']['value'] . ")" : "";
        $description = " $cellDesk $dot";
        $description .= ($itemRes['PhysDef']['value']) ? "<br>Защита <b>+{$itemRes['PhysDef']['value']}</b>" : "";
        $description .= ($itemRes['Dodge']['value']) ? "<br>Уклон <b>+{$itemRes['Dodge']['value']}</b>" : "";
        $description .= ($itemRes['Mana']['value']) ? "<br>Мана <b>+{$itemRes['Mana']['value']}</b>" : "";
        $description .= ($itemRes['HP']['value']) ? "<br>Здоровье <b>+{$itemRes['HP']['value']}</b>" : "";
        $description .= ($itemRes['MetalDef']['value']) ? "<br>Защита от металла <b>+{$itemRes['MetalDef']['value']}</b>" : "";
        $description .= ($itemRes['WoodDef']['value']) ? "<br>Защита от дерева <b>+{$itemRes['WoodDef']['value']}</b>" : "";
        $description .= ($itemRes['WaterDef']['value']) ? "<br>Защита от воды <b>+{$itemRes['WaterDef']['value']}</b>" : "";
        $description .= ($itemRes['FireDef']['value']) ? "<br>Защита от огня <b>+{$itemRes['FireDef']['value']}</b>" : "";
        $description .= ($itemRes['EarthDef']['value']) ? "<br>Защита от земли <b>+{$itemRes['EarthDef']['value']}</b>" : "";
        $description .= "<br>Прочность: <b>" . ($itemRes['CurDurab']['value'] / 100) . '/' . ($itemRes['MaxDurab']['value'] / 100) . "</b>";
        $description .= ($itemRes['ClassReq']['value'] != func::allCharSum()) ? "<br>Ограниечение по классу " . func::getCharClass($itemRes['ClassReq']['value']) : "";
        $description .= ($itemRes['LvlReq']['value']) ? "<br>Требуемый уровень <b>{$itemRes['LvlReq']['value']}</b>" : "";
        $description .= ($itemRes['StrReq']['value']) ? "<br>Требуемый сила <b>{$itemRes['StrReq']['value']}</b>" : "";
        $description .= ($itemRes['ConReq']['value']) ? "<br>Требуемая выносливость <b>{$itemRes['ConReq']['value']}</b>" : "";
        $description .= ($itemRes['DexReq']['value']) ? "<br>Требуемая ловкость <b>{$itemRes['DexReq']['value']}</b>" : "";
        $description .= ($itemRes['IntReq']['value']) ? "<br>Требуемая интеллект <b>{$itemRes['IntReq']['value']}</b>" : "";
        $description .= (!empty($bonusItem)) ? $bonusItem : "";
        $description .= (!empty($stone)) ? $stone : "";
        $description .= (!empty($itemRes['creator']['value'])) ? "<br><span style=\"color: lawngreen\">Создатель: " . $itemRes['creator']['value'] . "</span>" : "";

        return $description;
    }

    static function getAmulet($item, $sharpening, $bonusData, $fileElement)
    {
        $dot = 0;
        $cell = 0;
        $addAtkSpeed = 0;
        $stone = "";
        $bonusItem = "";

        stream::putRead($item['data']['value'], 0);
        $itemRes = GRole::readItemData(character::$items['amulet']);
        stream::putRead(stream::$readData_copy, stream::$p_copy);

        if (isset($itemRes['BonusInfo']['bonus'])) {
            foreach ($itemRes['BonusInfo']['bonus'] as $key => $bonus) {
                if (($bonus['id']['value'] == 331) || ($bonus['id']['value'] == 337) || ($bonus['id']['value'] == 338) || ($bonus['id']['value'] == 339))
                    $addAtkSpeed += $bonus['stat']['value'];
                elseif (($bonus['type']['value'] == 16384) && self::checkSharpening($bonus['id']['value'], $sharpening)) {
                    $dot = $bonus['dopStat1']['value'];
                    $addBonus = func::bonus($bonus['id']['value'], $bonusData, $bonus['stat']['value']);
                    $bonusItem .= "<br><span style=\"color: lightcoral;\">Заточка: " . $addBonus . "</span>";
                    $addBonus = explode(" +", $addBonus);
                    $itemRes['PhysDef']['value'] += $addBonus[1];
                } elseif ($bonus['type']['value'] == 40960) {
                    $stoneEl = editorModel::getItemFromElement($itemRes['cellInfo']['cellStone'][$cell]['id']['value'], $fileElement);
                    $stone .= "<br><span style=\"color: #a1e2f1\">" . $stoneEl['name'] . " " . func::bonus($bonus['id']['value'], $bonusData, $bonus['stat']['value']) . "</span>";
                    $cell++;
                } else
                    $bonusItem .= "<br><span style=\"color: #835bff\">" . func::bonus($bonus['id']['value'], $bonusData, $bonus['stat']['value']) . "</span>";
            }
        }
        $dot = ($dot != 0) ? "+$dot" : "";
        $cellDesk = ($itemRes['cellInfo']['cellCount']['value'] > 0) ? "(ячеек: " . $itemRes['cellInfo']['cellCount']['value'] . ")" : "";
        $description = " $cellDesk $dot";
        $description .= ($itemRes['PhysAtk']['value']) ? "<br>Физическая атака <b>+{$itemRes['PhysAtk']['value']}</b>" : "";
        $description .= ($itemRes['MagAtk']['value']) ? "<br>Магическая атака <b>+{$itemRes['MagAtk']['value']}</b>" : "";
        $description .= ($itemRes['PhysDef']['value']) ? "<br>Защита <b>+{$itemRes['PhysDef']['value']}</b>" : "";
        $description .= ($itemRes['Dodge']['value']) ? "<br>Уклон <b>+{$itemRes['Dodge']['value']}</b>" : "";
        $description .= ($itemRes['MetalDef']['value']) ? "<br>Защита от металла <b>+{$itemRes['MetalDef']['value']}</b>" : "";
        $description .= ($itemRes['WoodDef']['value']) ? "<br>Защита от дерева <b>+{$itemRes['WoodDef']['value']}</b>" : "";
        $description .= ($itemRes['WaterDef']['value']) ? "<br>Защита от воды <b>+{$itemRes['WaterDef']['value']}</b>" : "";
        $description .= ($itemRes['FireDef']['value']) ? "<br>Защита от огня <b>+{$itemRes['FireDef']['value']}</b>" : "";
        $description .= ($itemRes['EarthDef']['value']) ? "<br>Защита от земли <b>+{$itemRes['EarthDef']['value']}</b>" : "";
        $description .= "<br>Прочность: <b>" . ($itemRes['CurDurab']['value'] / 100) . '/' . ($itemRes['MaxDurab']['value'] / 100) . "</b>";
        $description .= ($itemRes['ClassReq']['value'] != func::allCharSum()) ? "<br>Ограниечение по классу " . func::getCharClass($itemRes['ClassReq']['value']) : "";
        $description .= ($itemRes['LvlReq']['value']) ? "<br>Требуемый уровень <b>{$itemRes['LvlReq']['value']}</b>" : "";
        $description .= ($itemRes['StrReq']['value']) ? "<br>Требуемый сила <b>{$itemRes['StrReq']['value']}</b>" : "";
        $description .= ($itemRes['ConReq']['value']) ? "<br>Требуемая выносливость <b>{$itemRes['ConReq']['value']}</b>" : "";
        $description .= ($itemRes['DexReq']['value']) ? "<br>Требуемая ловкость <b>{$itemRes['DexReq']['value']}</b>" : "";
        $description .= ($itemRes['IntReq']['value']) ? "<br>Требуемая интеллект <b>{$itemRes['IntReq']['value']}</b>" : "";
        $description .= (!empty($bonusItem)) ? $bonusItem : "";
        $description .= (!empty($stone)) ? $stone : "";
        $description .= (!empty($itemRes['creator']['value'])) ? "<br><span style=\"color: lawngreen\">Создатель: " . $itemRes['creator']['value'] . "</span>" : "";

        return $description;
    }

    static function getElf($item, $fileElement)
    {

        stream::putRead($item['data']['value'], 0);
        $itemRes = GRole::readItemData(character::$items['elf']);
        stream::putRead(stream::$readData_copy, stream::$p_copy);
        //system::debug($itemRes);
        $description = ($itemRes['sharpening']['value']) ? " +" . $itemRes['sharpening']['value'] : $description = "";
        $description .= "<br>Уровень " . $itemRes['Lvl']['value'];
        $description .= "<br>Сила " . $itemRes['Strength']['value'];
        $description .= "<br>Ловкость " . $itemRes['Agility']['value'];
        $description .= "<br>Выносливость " . $itemRes['Endurance']['value'];
        $description .= "<br>Интелект " . $itemRes['Intelligence']['value'];
        $description .= "<br>Текущий показатель энергии: " . $itemRes['CEnergy']['value'] . "/999999";
        //$description .= "<br>Уровень ".$itemRes['Lvl']['value'];
        // $description .= "<br>Уровень ".$itemRes['Lvl']['value'];
        return $description;

    }

    static function getCard($item, $fileElement)
    {
        stream::putRead($item['data']['value'], 0);
        $itemRes = GRole::readItemData(character::$items['card']);
        stream::putRead(stream::$readData_copy, stream::$p_copy);
        //system::debug($itemRes);
        switch ($itemRes['CardGrade']['value']) {
            case 0:
                $CardGrade = "C";
                break;
            case 1:
                $CardGrade = "B";
                break;
            case 2:
                $CardGrade = "A";
                break;
            case 3:
                $CardGrade = "S";
                break;
            default:
                $CardGrade = $itemRes['CardGrade']['value'];
                break;

        }

        switch ($itemRes['CardType']['value']) {
            case 0:
                $CardType = "Разрушение";
                break;
            case 1:
                $CardType = "Уничтожение";
                break;
            case 2:
                $CardType = "Долголетин";
                break;
            case 3:
                $CardType = "Здоровье";
                break;
            case 4:
                $CardType = "Тайна";
                break;
            case 5:
                $CardType = "Загадка";
                break;
            default:
                $CardType = $itemRes['CardType']['value'];
                break;
        }

        $description = "<br>Уровень " . $CardGrade;
        $description .= "<br>Тип " . $CardType;
        $description .= "<br>Требуемый уровень " . $itemRes['RequiredXp']['value'];
        $description .= "<br>Уровень контроля " . $itemRes['RequiredPoints']['value'];
        $description .= "<br>Уровень карты " . $itemRes['LvL']['value'] . "/" . $itemRes['MaxLvL']['value'];
        $description .= "<br>Текущее колво опыта " . $itemRes['CurrentExp']['value'];
        $description .= "<br>Количество перерождений " . $itemRes['Reborn']['value'];
        return $description;
    }

    static function getDisk($item, $fileElement){

//        stream::putRead($item['data']['value'], 0);
//        system::debug(stream::readInt16(false));
//        system::debug(stream::readByte());
//        system::debug(stream::readByte());
//        system::debug(stream::readByte());
//        system::debug(stream::readByte());
//        system::debug(stream::readInt32());
//        system::debug(stream::readInt16(false));
//        system::debug(stream::readInt16(false));
//        //system::debug(stream::readSingle());
//        system::debug(stream::readInt16(false));
//        system::debug(stream::readInt16(false));
//        system::debug(stream::readInt16(false));
//        stream::putRead(stream::$readData_copy, stream::$p_copy);


        return 0;
    }

    static function itemData($elementItem, $item, $sharpening, $bonusData, $desk, $key, $fileElement, $fileIcon, $type = "inv")
    {
        if ($elementItem['list'] == 3) {
            $description = self::getWeapon($item, $sharpening, $bonusData, $fileElement);
        } elseif ($elementItem['list'] == 6) {
            $description = self::getArmor($item, $sharpening, $bonusData, $fileElement);
        } elseif ($elementItem['list'] == 9) {
            $description = self::getAmulet($item, $sharpening, $bonusData, $fileElement);
        } elseif ($elementItem['list'] == 22) {
            $description = self::getFly($item);
        } elseif ($elementItem['list'] == 83) {
            $description = self::getFashion($item);
        } elseif ($elementItem['list'] == 119) {
            $description = self::getElf($item, $fileElement);
        } elseif ($elementItem['list'] == 184) {
            $description = self::getCard($item, $fileElement);
        } elseif ($elementItem['list'] == 197) {
            $description = self::getDisk($item, $fileElement);
        } else {
            $description = "";
        }

        $itemDesk = (isset($desk[$item['id']['value']])) ? func::reColorDesc($desk[$item['id']['value']]) : "";
        switch ($type) {
            case"inv":
                $class = "item_inv";
                $typeKey = "";
                $typeJS = 0;
                break;
            case"eqp":
                $class = "player__equipment-item cell-{$item['pos']['value']}";
                $typeKey = "-eqp";
                $typeJS = 1;
                break;
            case"store":
                $class = "item_inv";
                $typeKey = "-store";
                $typeJS = 2;
                break;
            case"card":
                $class = "item_inv";
                $typeKey = "-card";
                $typeJS = 3;
                break;
        }

        $elementItem['icon'] = strtolower($elementItem['icon']);
        $icon = (isset($fileIcon[$elementItem['icon']])) ? $fileIcon[$elementItem['icon']] : $fileIcon["unknown.dds"];

        $elementItem['name'] = str_replace("'", "`", $elementItem['name']);
        switch ($elementItem['color']) {
            case 0://+
                $elementItem['name'] = "<span style=\"color: #ffffff;\">{$elementItem['name']}</span>";
                break;
            case 1://+
                $elementItem['name'] = "<span style=\"color: #8080ff;\">{$elementItem['name']}</span>";
                break;
            case 2://+
                $elementItem['name'] = "<span style=\"color: #ffdc50;\">{$elementItem['name']}</span>";
                break;
            case 3://+
                $elementItem['name'] = "<span style=\"color: #aa32ff;\">{$elementItem['name']}</span>";
                break;
            case 4://+
                $elementItem['name'] = "<span style=\"color: #ff6000;\">{$elementItem['name']}</span>";
                break;
            case 5:
                $elementItem['name'] = "<span style=\"color: #ffffff;\">{$elementItem['name']}</span>";
                break;
            case 6:
                $elementItem['name'] = "<span style=\"color: #b0b0b0;\"'>{$elementItem['name']}</span>";
                break;
            case 7://+
                $elementItem['name'] = "<span style=\"color: #00ffae;\">{$elementItem['name']}</span>";
                break;
            case 8://+
                $elementItem['name'] = "<span style=\"color: #ff0000;\">{$elementItem['name']}</span>";
                break;
            case 9://+
                $elementItem['name'] = "<span style=\"color: #ff0000;\">{$elementItem['name']}</span>";
                break;
            case 10://+
                $elementItem['name'] = "<span style=\"color: #80ffff;\">{$elementItem['name']}</span>";
                break;
        }

        $pocketItems = "<div class=\"$class\" onclick='editPocketItem($key,$typeJS, {$elementItem['list']})' data-html=\"true\" data-tip=\"tooltip\" data-key$typeKey='$key' data-toggle=\"modal\" data-target=\"#pocketItem\" data-original-title='<b>{$elementItem['name']}</b>{$description}<br>{$itemDesk}'><img src='data:image/png;base64,$icon'><span class='item_count'>{$item['count']['value']}</span></div>";

        return $pocketItems;
    }

}