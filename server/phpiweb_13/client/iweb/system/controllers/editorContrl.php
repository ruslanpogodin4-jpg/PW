<?php

namespace system\controllers;

use system\data\character\character;
use system\data\lang;
use system\data\server;
use system\libs\ArrayToXml;
use system\data\config;
use system\libs\func;
use system\libs\GRole;
use system\libs\stream;
use system\libs\struct\octetFly;
use system\libs\struct\octetWeapon;
use system\libs\struct\roleSkill;
use system\libs\struct\roleSkills;
use system\libs\system;
use system\libs\xml;
use system\models\editorModel;

if (!defined('IWEB')) {
    die("Error!");
}

class editorContrl
{

    static function index()
    {
        // system::debug($sharpening);
        //echo $item['data'] = bin2hex(substr($item['data'], 0, strlen($item['data'])));

        $bonus = editorModel::getBonus();
        $sharpening = editorModel::getSharpening();
        $desk = func::getDesk();
        $fileElement = json_decode(base64_decode(gzinflate(file_get_contents(dir . "/system/data/items.json"))), true);
        $fileIcon = json_decode(base64_decode(gzinflate(file_get_contents(dir . "/system/data/icons.json"))), true);

        system::$site_title = "Визуальный Редактор";
        if (system::$user['visual_edit']) {
            $id = (isset($_GET['id']) && is_numeric($_GET['id'])) ? $_GET['id'] : 1024;
            $role = GRole::readCharacter($id, false);

            $role = $role['role'];
            if ($role) {
                $newArr = array();
                $pItems = "";
                $realLine = 0;
                $items_data['items'] = "";
                if ($role['pocket']['invcount']['value'] > 0) {
                    $item_index = 1;
                    $item_line = 1;
                    $pocketItems = "";
                    foreach ($role['pocket']['inv'] as $key => $item) {

                        $elementItem = editorModel::getItemFromElement($item['id']['value'], $fileElement);
                        $pocketItems .= editorModel::itemData($elementItem, $item, $sharpening, $bonus, $desk, $key, $fileElement, $fileIcon, "inv");
                        if ($item_line == 8 or $item_index == $role['pocket']['invcount']['value']) {
                            $item_line = 1;
                            $realLine++;
                            $items_data['items'] .= "<li>$pocketItems</li>";
                            $pocketItems = "";
                        } else {
                            $item_line += 1;
                        }
                        $item_index++;
                        $item['data']['value'] = bin2hex(substr($item['data']['value'], 0, strlen($item['data']['value'])));
                        $newArr[] = $item;
                    }
                    $pItems = htmlspecialchars(json_encode($newArr));
                }
                $line = ($role['pocket']['icapacity']['value'] / 8) - $realLine;
                for ($i = 0; $i < $line; $i++)
                    $items_data['items'] .= "<li></li>";


                $newArr = array();
                $equipmentItems = '';
                $eItems = "";
                if (isset($role['equipment']['eqp']) && $role['equipment']['eqpcount']['value'] > 0) {

                    foreach ($role['equipment']['eqp'] as $key => $item) {
                        $elementItem = editorModel::getItemFromElement($item['id']['value'], $fileElement);
                        $equipmentItems .= editorModel::itemData($elementItem, $item, $sharpening, $bonus, $desk, $key, $fileElement, $fileIcon, "eqp");

                        $item['data']['value'] = bin2hex(substr($item['data']['value'], 0, strlen($item['data']['value'])));
                        $newArr[] = $item;
                    }
                    //   system::debug($newArr);
                    $eItems = htmlspecialchars(json_encode($newArr));

                }

                $newArr = array();
                $storeItems = '';
                $sItems = "";
                $items_store['items'] = "";
                $realLine = 0;
                if (isset($role['storehouse']['store']) && $role['storehouse']['storecount']['value'] > 0) {
                    $item_line = 1;
                    $item_index = 1;
                    foreach ($role['storehouse']['store'] as $key => $item) {
                        $elementItem = editorModel::getItemFromElement($item['id']['value'], $fileElement);
                        $storeItems .= editorModel::itemData($elementItem, $item, $sharpening, $bonus, $desk, $key, $fileElement, $fileIcon, "store");
                        if ($item_line == 8 or $item_index == $role['storehouse']['storecount']['value']) {
                            $item_line = 1;
                            $realLine++;
                            $items_store['items'] .= "<li>$storeItems</li>";
                            $storeItems = "";
                        } else {
                            $item_line += 1;
                        }
                        $item_index++;
                        $item['data']['value'] = bin2hex(substr($item['data']['value'], 0, strlen($item['data']['value'])));
                        $newArr[] = $item;
                    }
                    $sItems = htmlspecialchars(json_encode($newArr));
                }
                $line = ($role['storehouse']['capacity']['value'] / 8) - $realLine;
                for ($i = 0; $i < $line; $i++)
                    $items_store['items'] .= "<li></li>";


                $newArr = array();
                $cardsItems = '';
                $cItems = "";
                $realLine = 0;
                $items_card['items'] = "";
                if (isset($role['storehouse']['card']) && $role['storehouse']['cardcount']['value'] > 0) {
                    $item_line = 1;
                    $item_index = 1;
                    foreach ($role['storehouse']['card'] as $key => $item) {
                        $elementItem = editorModel::getItemFromElement($item['id']['value'], $fileElement);
                        $cardsItems .= editorModel::itemData($elementItem, $item, $sharpening, $bonus, $desk, $key, $fileElement, $fileIcon, "card");
                        if ($item_line == 8 or $item_index == $role['storehouse']['cardcount']['value']) {
                            $item_line = 1;
                            $realLine++;
                            $items_card['items'] .= "<li>$cardsItems</li>";
                            $cardsItems = "";
                        } else {
                            $item_line += 1;
                        }
                        $item_index++;
                        $item['data']['value'] = bin2hex(substr($item['data']['value'], 0, strlen($item['data']['value'])));
                        $newArr[] = $item;
                    }
                    $cItems = htmlspecialchars(json_encode($newArr));
                }
                $line = ($role['storehouse']['size3']['value'] / 8) - $realLine;
                for ($i = 0; $i < $line; $i++)
                    $items_card['items'] .= "<li></li>";
                //     stream::putRead($role['status']['skills']['value']);
//                $skills = new roleSkills();
//                $skills->count = stream::readInt32(false);
//                for ($i = 0; $i < $skills->count; $i++) {
//                    $skills->skills[$i] = new roleSkill();
//                    $skills->skills[$i]->id = stream::readInt32(false);
//                    $skills->skills[$i]->craft = stream::readInt32(false);
//                    $skills->skills[$i]->level = stream::readInt32(false);
//                }

//                $statusSkills = '';
//                $s = file_get_contents(dir . "/system/data/skills.json");
//                $s = json_decode($s, true);

//                if ($skills->count > 0) {
//
//                    foreach ($skills->skills as $item) {
//                        //system::debug($item);
//                        if (isset($s[$item->id]['icon'])) {
//                            $new = explode(".", $s[$item->id]['icon']);
//
//                            $image = dir . "/system/data/icons_skills/" . $new[0] . ".png";
//                        } else
//                            $image = dir . "/system/data/icons_skills/unknown.png";
//
//                        if (file_exists($image)) {
//                            $src = 'data: ' . mime_content_type($image) . ';base64,' . base64_encode(file_get_contents($image));
//                            $statusSkills .= "<img data-toggle=\"tooltip\" title='' style='padding: 3px' src='$src' />";
//                        } else {
//                            $statusSkills .= "Skill:$item->id ";
//                        }
//                    }
//                }
                //    stream::putRead(stream::$readData_copy, stream::$p_copy);
                // system::debug($role);
                system::load("editor");
                //base
                system::set("{id}", $role['base']['id']['value']);
//                system::set("{name}", $role['base']['name']['value']);
//                system::set("{race}", $role['base']['race']['value']);
//                system::set("{gender}", editorModel::selectProp(lang::$gender, $role['base']['gender']['value']));
//                system::set("{cls}", editorModel::selectProp(lang::$cls, $role['base']['cls']['value']));
                system::set("{spouse}", $role['base']['spouse']['value']);

                //status
                system::set("{level}", $role['status']['level']['value']);
                system::set("{level2}", editorModel::selectProp(lang::$level2, $role['status']['level2']['value']));
                system::set("{exp}", $role['status']['exp']['value']);
                system::set("{sp}", $role['status']['sp']['value']);
                system::set("{hp}", $role['status']['hp']['value']);
                system::set("{mp}", $role['status']['mp']['value']);
                system::set("{pariah_time}", $role['status']['pariah_time']['value']);
                system::set("{reputation}", $role['status']['reputation']['value']);
                system::set("{pp}", $role['status']['pp']['value']);
                system::set("{worldtag}", $role['status']['worldtag']['value']);
                system::set("{posx}", $role['status']['posx']['value']);
                system::set("{posy}", $role['status']['posy']['value']);
                system::set("{posz}", $role['status']['posz']['value']);
                system::set("{pariah_time}", $role['status']['pariah_time']['value']);
                system::set("{invader_state}", editorModel::selectProp(lang::$invader_state, $role['status']['invader_state']['value']));
//                system::set("{status-skills}", $statusSkills);


                //meridian_data

                foreach (lang::$template['meridian_data'] as $key => $val) {
                    system::set("{lang_meridian_$key}", $val);
                }
                system::set("{meridian_data_lvl}", $role['status']['meridian_data']['lvl']['value']);
                system::set("{meridian_data_life_dot}", $role['status']['meridian_data']['life_dot']['value']);
                system::set("{meridian_data_die_dot}", $role['status']['meridian_data']['die_dot']['value']);
                system::set("{meridian_data_free_up}", $role['status']['meridian_data']['free_up']['value']);
                system::set("{meridian_data_paid_up}", $role['status']['meridian_data']['paid_up']['value']);
                system::set("{meridian_data_login_time}", $role['status']['meridian_data']['login_time']['value']);
                system::set("{meridian_data_login_days}", $role['status']['meridian_data']['login_days']['value']);
                system::set("{meridian_data_trigrams_map1}", $role['status']['meridian_data']['trigrams_map1']['value']);
                system::set("{meridian_data_trigrams_map2}", $role['status']['meridian_data']['trigrams_map2']['value']);
                system::set("{meridian_data_trigrams_map3}", $role['status']['meridian_data']['trigrams_map3']['value']);
                system::set("{meridian_data_reserved1}", $role['status']['meridian_data']['reserved1']['value']);
                system::set("{meridian_data_reserved2}", $role['status']['meridian_data']['reserved2']['value']);
                system::set("{meridian_data_reserved3}", $role['status']['meridian_data']['reserved3']['value']);
                system::set("{meridian_data_reserved4}", $role['status']['meridian_data']['reserved4']['value']);

                //property
                foreach (lang::$template['property'] as $key => $val) {
                    system::set("{lang_property_$key}", $val);
                }
                system::set("{property_vitality}", $role['status']['property']['vitality']['value']);
                system::set("{property_energy}", $role['status']['property']['energy']['value']);
                system::set("{property_strength}", $role['status']['property']['strength']['value']);
                system::set("{property_agility}", $role['status']['property']['agility']['value']);
                system::set("{property_max_hp}", $role['status']['property']['max_hp']['value']);
                system::set("{property_max_mp}", $role['status']['property']['max_mp']['value']);
                system::set("{property_hp_gen}", $role['status']['property']['hp_gen']['value']);
                system::set("{property_mp_gen}", $role['status']['property']['mp_gen']['value']);
                system::set("{property_walk_speed}", $role['status']['property']['walk_speed']['value']);
                system::set("{property_run_speed}", $role['status']['property']['run_speed']['value']);
                system::set("{property_swim_speed}", $role['status']['property']['swim_speed']['value']);
                system::set("{property_flight_speed}", $role['status']['property']['flight_speed']['value']);
                system::set("{property_attack}", $role['status']['property']['attack']['value']);
                system::set("{property_damage_low}", $role['status']['property']['damage_low']['value']);
                system::set("{property_damage_high}", $role['status']['property']['damage_high']['value']);
                system::set("{property_attack_speed}", $role['status']['property']['attack_speed']['value']);
                system::set("{property_attack_range}", $role['status']['property']['attack_range']['value']);
                system::set("{property_addon_damage_low-metal}", $role['status']['property']['addon_damage_low']['metal']['value']);
                system::set("{property_addon_damage_low-tree}", $role['status']['property']['addon_damage_low']['tree']['value']);
                system::set("{property_addon_damage_low-water}", $role['status']['property']['addon_damage_low']['water']['value']);
                system::set("{property_addon_damage_low-fire}", $role['status']['property']['addon_damage_low']['fire']['value']);
                system::set("{property_addon_damage_low-ground}", $role['status']['property']['addon_damage_low']['ground']['value']);
                system::set("{property_addon_damage_high-metal}", $role['status']['property']['addon_damage_high']['metal']['value']);
                system::set("{property_addon_damage_high-tree}", $role['status']['property']['addon_damage_high']['tree']['value']);
                system::set("{property_addon_damage_high-water}", $role['status']['property']['addon_damage_high']['water']['value']);
                system::set("{property_addon_damage_high-fire}", $role['status']['property']['addon_damage_high']['fire']['value']);
                system::set("{property_addon_damage_high-ground}", $role['status']['property']['addon_damage_high']['ground']['value']);
                system::set("{property_damage_magic_low}", $role['status']['property']['damage_magic_low']['value']);
                system::set("{property_damage_magic_high}", $role['status']['property']['damage_magic_high']['value']);
                system::set("{property_resistance-metal}", $role['status']['property']['resistance']['metal']['value']);
                system::set("{property_resistance-tree}", $role['status']['property']['resistance']['tree']['value']);
                system::set("{property_resistance-water}", $role['status']['property']['resistance']['water']['value']);
                system::set("{property_resistance-fire}", $role['status']['property']['resistance']['fire']['value']);
                system::set("{property_resistance-ground}", $role['status']['property']['resistance']['ground']['value']);
                system::set("{property_defense}", $role['status']['property']['defense']['value']);
                system::set("{property_armor}", $role['status']['property']['armor']['value']);
                system::set("{property_max_ap}", $role['status']['property']['max_ap']['value']);


                //pocket
                system::set("{pocket-all-count}", $role['pocket']['icapacity']['value']);
                system::set("{pocket-count}", $role['pocket']['invcount']['value']);
                system::set("{pocket-money}", $role['pocket']['money']['value']);
                system::set("{pocket-items}", $items_data['items']);
                system::set("{pItems}", $pItems);
                //equipment
                system::set("{equipment-items}", $equipmentItems);
                system::set("{eItems}", $eItems);

                //storehouse
                system::set("{store-all-count}", $role['storehouse']['capacity']['value']);
                system::set("{store-count}", $role['storehouse']['storecount']['value']);
                system::set("{storehouse-money}", $role['storehouse']['money']['value']);
                system::set("{sItems}", $sItems);
                system::set("{storehouse-items}", $items_store['items']);

                //card
                system::set("{card-count}", $role['storehouse']['cardcount']['value']);
                system::set("{card-all-count}", $role['storehouse']['size3']['value']);
                system::set("{cItems}", $cItems);
                system::set("{card-items}", $items_card['items']);

                //vardata
                foreach (lang::$template['var_data'] as $key => $val) {
                    system::set("{lang_$key}", $val);
                }
                system::set("{vardata_vd_version}", $role['status']['var_data']['vd_version']['value']);
                system::set("{vardata_pk_count}", $role['status']['var_data']['pk_count']['value']);
                system::set("{vardata_pvp_cooldown}", $role['status']['var_data']['pvp_cooldown']['value']);
                system::set("{vardata_pvp_flag}", editorModel::selectProp(lang::$yn, $role['status']['var_data']['pvp_flag']['value']));
                system::set("{vardata_dead_flag}", editorModel::selectProp(lang::$yn, $role['status']['var_data']['dead_flag']['value']));
                system::set("{vardata_is_drop}", editorModel::selectProp(lang::$yn, $role['status']['var_data']['is_drop']['value']));
                system::set("{vardata_resurrect_state}", $role['status']['var_data']['resurrect_state']['value']);
                system::set("{vardata_resurrect_exp_reduce}", $role['status']['var_data']['resurrect_exp_reduce']['value']);
                system::set("{vardata_instance_hash_key1}", $role['status']['var_data']['instance_hash_key1']['value']);
                system::set("{vardata_instance_hash_key2}", $role['status']['var_data']['instance_hash_key2']['value']);
                system::set("{vardata_trashbox_size}", $role['status']['var_data']['trashbox_size']['value']);
                system::set("{vardata_last_instance_timestamp}", $role['status']['var_data']['last_instance_timestamp']['value']);
                system::set("{vardata_last_instance_tag}", $role['status']['var_data']['last_instance_tag']['value']);
                system::set("{vardata_last_instance_pos_x}", $role['status']['var_data']['last_instance_pos']['x']['value']);
                system::set("{vardata_last_instance_pos_y}", $role['status']['var_data']['last_instance_pos']['y']['value']);
                system::set("{vardata_last_instance_pos_z}", $role['status']['var_data']['last_instance_pos']['z']['value']);
                system::set("{vardata_dir}", $role['status']['var_data']['dir']['value']);
                system::set("{vardata_resurrect_hp_factor}", $role['status']['var_data']['resurrect_hp_factor']['value']);
                system::set("{vardata_resurrect_mp_factor}", $role['status']['var_data']['resurrect_mp_factor']['value']);
                system::set("{vardata_instance_reenter}", $role['status']['var_data']['instance_reenter']['value']);
                system::set("{vardata_last_world_type}", $role['status']['var_data']['last_world_type']['value']);
                system::set("{vardata_last_logout_pos_x}", $role['status']['var_data']['last_logout_pos']['x']['value']);
                system::set("{vardata_last_logout_pos_y}", $role['status']['var_data']['last_logout_pos']['y']['value']);
                system::set("{vardata_last_logout_pos_z}", $role['status']['var_data']['last_logout_pos']['z']['value']);

                //sky
                foreach (lang::$template['realm_data'] as $key => $val) {
                    system::set("{lang_realm_data_$key}", $val);
                }
                system::set("{realm_data_level}", $role['status']['realm_data']['level']['value']);
                system::set("{realm_data_exp}", $role['status']['realm_data']['exp']['value']);
                system::set("{realm_data_reserved1}", $role['status']['realm_data']['reserved1']['value']);
                system::set("{realm_data_reserved2}", $role['status']['realm_data']['reserved2']['value']);

                //weapon
                foreach (lang::$template['weapon'] as $key => $val) {
                    system::set("{lang_weapon_$key}", $val);
                }

                //property
//                system::set("{vitality}", $role['status']['property']['vitality']['value']);
//                system::set("{energy}", $role['status']['property']['energy']['value']);
//                system::set("{strength}", $role['status']['property']['strength']['value']);
//                system::set("{agility}", $role['status']['property']['agility']['value']);
//                system::set("{max_hp}", $role['status']['property']['max_hp']['value']);
//                system::set("{max_mp}", $role['status']['property']['max_mp']['value']);
//                system::set("{max_ap}", editorModel::selectProp(lang::$max_ap, $role['status']['property']['max_ap']['value']));

                system::show('content');
                system::clear();
            } else {
                system::info("Ошибка персанажа", "Не удалось получить данные о персонаже, возможно его не существует или сервер выключен");
            }
        } else system::info("Нет доступа", "У вас нет доступа к этой функции");
    }

    static function xml()
    {
        system::$site_title = "XML Редактор";
        if (system::$user['xml_edit']) {
            $id = (is_numeric($_GET['id'])) ? $_GET['id'] : 1024;
            if ($role = GRole::readCharacter($id, true)) {
                if (stream::$error > 0) {
                    system::info("Чтение персонажа", "Возникло: " . stream::$error . " ошибок при чтении персонажа, редактирование не возможно!", "info");
                } else {
                    system::load("xml");
                    system::set("{id}", $id);
                    system::set("{xml}", xml::encode($role['role']));
                    system::show('content');
                    system::clear();
                }
            } else
                system::info("Ошибка персонажа", "Не удалось получить данные о персонаже, возможно его не существует или сервер выключен");
        } else system::info("Нет доступа", "У вас нет доступа к этой функции");

    }

    static function chars()
    {
        system::$site_title = "Менеджер персонажей";
        system::load("chars");
        system::set("{save_gamedbg}", "cd " . config::$serverPath . "/" . server::$server['gamedbd']['dir'] . "; ./" . server::$server['gamedbd']['program'] . " " . server::$server['gamedbd']['config'] . " exportclsconfig");
        system::show('content');
        system::clear();
    }

}