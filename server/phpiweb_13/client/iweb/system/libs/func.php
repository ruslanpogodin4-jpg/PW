<?php

namespace system\libs;

use system\data\config;
use system\data\lang;

if (!defined('IWEB')) {
    die("Error!");
}

class func
{

    static $error = "";

//
    static function readImage($nameImage)
    {
        $path = dir . "/system/data/$nameImage.";
        if ($data['image'] = imagecreatefrompng($path . "png")) {
            if ($file = fopen($path . 'txt', 'r')) {
                $data['imageWidth'] = (int)fgets($file);
                $data['imageHeight'] = (int)fgets($file);
                $data['rows'] = (int)fgets($file);
                $data['cols'] = (int)fgets($file);
                $data['line'] = array();
                $count = 1;
                while (!feof($file)) {
                    $data['line'][$count] = @mb_convert_encoding(trim(fgets($file)), "UTF-8", "GB2312");
                    $count++;
                }
                fclose($file);
            } else {
                self::$error = "Не удалось прочитать файл с названием иконок";
            }
        } else {
            self::$error = "Не удалось получить картинку";
        }
        return $data;
    }

    static function uploadImage($imageName)
    {
        ignore_user_abort();
        set_time_limit(0);
        header('Connection: close');
        @ob_end_flush();
        @ob_flush();
        @flush();
        if (session_id()) {
            session_write_close();
        }
        //database::query("TRUNCATE TABLE iconItems");
        $image = self::readImage($imageName);
        $imageTColor = imagecreatetruecolor($image['imageWidth'], $image['imageHeight']);
        $i = 0;
        $iconsArray = array();
        foreach ($image['line'] as $number => $value) {
            if ($value == '') continue;
            if ($number > $image['cols']) $row = floor(($number - 1) / $image['cols']); else $row = 0;
            $col = $number - ($row * $image['cols']) - 1;
            if ($col < 0) $col = 0;
            imageCopy($imageTColor, $image['image'], 0, 0, $col * $image['imageWidth'], $row * $image['imageHeight'], $image['imageWidth'], $image['imageHeight']);
            ob_start();
            imagejpeg($imageTColor, null, 90);
            $result = ob_get_clean();
            $icons[$value] = array('icon' => $result);
//            if (!database::query("INSERT INTO `items_icons` (`name`, `icon`) VALUES ('" . $value . "', '" . database::escape($result) . "')"))
//                self::$error = "Ошибка загрузки в базу";
            $iconsArray[$value] = base64_encode($result);
            $i++;
        }

        $fw1 = fopen(dir . "/system/data/icons.json", "w+");
        fwrite($fw1, gzdeflate(base64_encode(json_encode($iconsArray))));
        fclose($fw1);
        $iconsArray = array();

        if (empty(self::$error)) {
            system::log("Загрузка иконок в базу");
            system::jms("success", "Иконки загружены в базу");
        } else
            system::jms("danger", "Ошибка загрузки иконок");

    }

    static function readChat()
    {

        $getFile = socket::sendPacket(57, socket::packString(config::$logsPath. "/" . config::$chatFile), 1024 * 100);
        $charArr = array();
        if ($getFile != "File not found" && $getFile != "rf0" && $getFile != "") {
            $chat = array_filter(preg_split("/\n/", $getFile));
            $count = 0;

            foreach (array_reverse($chat, true) as $value) {
                if ($count < config::$countChatMsg) {
                    preg_match_all("/([0-9]{0,}-[0-9]{0,}-[0-9]{0,} [0-9]{0,}:[0-9]{0,}:[0-9]{0,}) ([A-z0-9-]{0,}) ([A-z0-9-]{0,}): ([A-z0-9-]{0,}) : ([A-z0-9-]{0,}): ([A-z-]{0,})=([0-9]{0,}) ([A-z-]{0,})=([0-9]{0,}) ([A-z-]{0,})=([A-z0-9+=]{0,})/", $value, $data);
                    // system::debug($data);
                    //$data = $data[0];
                    $msg = str_replace(array("", "", "", ""), "", iconv("UTF-16LE", "UTF-8", base64_decode($data[11][0])));
                    preg_match_all("/(<1>[<]\^)([a-z0-9]{0,})(.*?)([>])/", $msg, $itemInChat);
                    if ($itemInChat[0]) {
                        $msg = str_replace($itemInChat[0][0], "<span style='color:#{$itemInChat[2][0]}; font-weight: bold'>{$itemInChat[3][0]}</span>", $msg);
                    }
                    preg_match_all("/(<5>[<])(.*?)([>])/", $msg, $charInChat);
                    if ($charInChat[0]) {
                        $msg = str_replace($charInChat[0][0], "<span style='color:#ffffff; font-weight: bold'>{$charInChat[2][0]}</span>", $msg);
                    }
                    preg_match_all("/(<0>[<])([0-9]{0,}):([0-9]{0,})([>])/", $msg, $smileInChat);
                    if ($smileInChat[0]) {
                        if (file_exists(dir . "/system/data/smile/{$smileInChat[2][0]}/{$smileInChat[3][0]}.gif"))
                            $msg = str_replace($smileInChat[0][0], "<img src='" . config::$site_adr . "/system/data/smile/{$smileInChat[2][0]}/{$smileInChat[3][0]}.gif' />", $msg);
                        else
                            $msg = str_replace($smileInChat[0][0], "<img src='" . config::$site_adr . "/system/data/smile/0/{$smileInChat[3][0]}.gif' />", $msg);

                        if (isset($smileInChat[0][1])) $msg = str_replace($smileInChat[0][1], "<img src='" . config::$site_adr . "/system/data/smile/{$smileInChat[2][1]}/{$smileInChat[3][1]}.gif' />", $msg);
                        if (isset($smileInChat[0][2])) $msg = str_replace($smileInChat[0][2], "<img src='" . config::$site_adr . "/system/data/smile/{$smileInChat[2][2]}/{$smileInChat[3][2]}.gif' />", $msg);
                    }
                    // $msg = str_replace("/([<]\^)([a-z0-9]{0,})([\[А-яA-z0-9 -.\]]{0,})([>])/")
                    if ($data[8][0] == "fid") {
                        $clanID = $data[9][0];
                        $data[9][0] = 3;
                    } else $clanID = 0;

                    $charArr[$data[9][0]][] = array(
                        "data" => $data[0][0],
                        "role" => $data[7][0],
                        "msg" => $msg,
                        "clan" => $clanID
                    );
                } else break;
                $count++;
            }
        }
        return $charArr;
    }

    static function getDesk()
    {
        $deskMain = array();
        $df = fopen(dir . "/system/data/item_ext_desc.txt", "r");
        fgets($df);
        fgets($df);
        fgets($df);
        fgets($df);
        fgets($df);
        while (!feof($df)) {
            $line = fgets($df);
            if (!empty($line) or $line != " ") {
                $desc = explode("  ", $line, 2);
                $deskMain[$desc[0]] = (!isset($desc[1])) ? "null" : str_replace('"', "", $desc[1]);
            }
        }
        return $deskMain;
    }

    static function reColorDesc($string)
    {
        for ($i = 0; $i < strlen($string); $i++) {
            $pos = strpos($string, "^", $i);
            if ($pos !== false) {
                $color = substr($string, $pos + 1, 6);
                $string = substr_replace($string, "<span style=\"color:#$color\">", $pos, 7);
                $string .= "</span>";
            }
        }
        $string = htmlspecialchars(str_replace('\r', "<br/>", $string));
        return $string;
    }

    static function getCharClass($cl, $check = true)
    {
        $clreq = '';
        if ($cl != self::allCharSum() || !$check) {
            if ($cl & 0x1) $clreq .= 'Воин, ';
            if ($cl & 0x1 << 1) $clreq .= 'Маг, ';
            if ($cl & 0x1 << 2) $clreq .= 'Шаман, ';
            if ($cl & 0x1 << 3) $clreq .= 'Друид, ';
            if ($cl & 0x1 << 4) $clreq .= 'Обор, ';
            if ($cl & 0x1 << 5) $clreq .= 'Убийца, ';
            if ($cl & 0x1 << 6) $clreq .= 'Лучник, ';
            if ($cl & 0x1 << 7) $clreq .= 'Жрец, ';
            if ($cl & 0x1 << 8) $clreq .= 'Страж, ';
            if ($cl & 0x1 << 9) $clreq .= 'Мистик, ';
            if ($cl & 0x1 << 10) $clreq .= 'Призрак, ';
            if ($cl & 0x1 << 11) $clreq .= 'Жнец, ';
            $clreq = substr($clreq, 0, strlen($clreq) - 2);
        }
        return $clreq;
    }

    static function allCharSum()
    {
        if (config::$version < 29) $all_req = 255;
        if (config::$version < 151) $all_req = 1023; else
            $all_req = 4095;
        return $all_req;
    }

    static function bonus($id, $bonuses, $stat)
    {
        foreach ($bonuses['ids'] as $i => $val) {
            if (in_array($id, $val)) {
                if (strpos($bonuses['names'][$i], '^f') !== false) {
                    $st = self::autoType($stat, 1);
                    $name = str_replace('^f', '', $bonuses['names'][$i]);
                } else
                    if (strpos($bonuses['names'][$i], '^pf') !== false) {
                        $st = self::autoType($stat, 3);
                        $name = str_replace('^pf', '', $bonuses['names'][$i]);
                    } else
                        if (strpos($bonuses['names'][$i], '^p') !== false) {
                            $st = self::autoType($stat, 2);
                            $name = str_replace('^p', '', $bonuses['names'][$i]);
                        } else {
                            $st = $stat;
                            $name = $bonuses['names'][$i];
                        }
                $bon = sprintf($name, $st);

                return $bon;
            }
        }
        return 'Неизвестный бонус ' . $id;
    }

    static function autoType($a, $n)
    {
        $b = pack("I", $a);
        if ($n == 1) {
            $b = unpack("f", $b);
            $b[1] = round($b[1], 2);
        } else
            if ($n == 2) {
                $b = unpack("i", $b);
                $b[1] = $b[1] * 0.05;
            } else
                if ($n == 3) {
                    $b = unpack("f", $b);
                    $b[1] = round($b[1] * 100, 2);
                } else $b = unpack("i", $b);
        return $b[1];
    }

    static function readConf($fileName)
    {
        $fileGsConfig = socket::sendPacket(57, socket::packString($fileName), 2048 * 1000);
        if ($fileGsConfig != "File not found") {
            $fileGsConfig = explode("\n", $fileGsConfig);
            $newConf = array();
            $newNum = "all";
            foreach ($fileGsConfig as $value) {
                if (!empty($value) && substr($value, 0, 1) !== ";") {
                    if (substr($value, 0, 1) == "[") {
                        preg_match_all("/(\[)(.*?)(\])/", $value, $matches);
                        //system::debug( $matches[2]);
                        if (isset($matches[2][0]))
                            $newNum = $matches[2][0];
                    } else {
                        $newValue = explode("=", $value);
                        if (isset($newValue[0]) && isset($newValue[1]))
                            $newConf[$newNum][trim($newValue[0], "\n\t\r\v ")] = trim($newValue[1], "\n\t\r\v ");
                    }
                }
            }
            //system::debug($newConf);
            return $newConf;
        } else return false;
    }

    static function writeConf($path, $data)
    {

        $configData = "";
        foreach ($data as $key => $value) {
            $configData .= "[$key]\n";
            foreach ($value as $key1 => $value1) {
                $configData .= "$key1 = $value1\n";
            }
            $configData .= "\n";
        }

        socket::sendPacket(58, socket::packString($path) . socket::packString($configData));
        return true;
    }

    static function listLocations($array = false)
    {
        $gsConf = self::readConf("/home/gamed/gs.conf");
        if ($gsConf) {
            $serverLocations = $gsConf['General']['world_servers'] . $gsConf['General']['instance_servers'];
            $serverLocations = explode(";", $serverLocations);
            if (!$array) {
                $inst = "";
                foreach ($serverLocations as $location) {
                    if (!empty($location)) {
                        if (isset(lang::$locations[$location])) $locationName = lang::$locations[$location]; else $locationName = "[$location] Не известная локация";
                        $inst .= "<option class='listloc' id='gs-{$location}' value=\"{$location}\">" . $locationName . "</option>";
                    }
                }
                return $inst;
            } else {
                $inst = array();
                foreach ($serverLocations as $location) {
                    if (isset(lang::$locations[$location])) $locationName = lang::$locations[$location]; else $locationName = "[$location] Не известная локация";
                    $inst[$location] = $locationName;
                }
                return $inst;
            }
        } else
            return false;
    }

}