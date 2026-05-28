<?php

namespace system\libs;

use system\data\character\character;
use system\data\config;

class GRole
{

    static $cycle = false;

    static function readItemData($struct)
    {

        $data = array();
        foreach ($struct as $key => $value) {
            if (is_array($value)) {
                // system::debug($value);
                if (self::$cycle) {
                    if (self::$cycle > 0) {
                        for ($i = 0; $i < self::$cycle; $i++) {
                            if ($key == "bonus") {
                                // system::debug($value);
                                $data[$key][$i]['id'] = array("type" => $value['id'], "value" => stream::readInt32(false));
                                $data[$key][$i]['type'] = array("type" => 0, "value" => 0);

                                if ($data[$key][$i]['id']['value'] & 1 << 17) {
                                    $data[$key][$i]['type']['value'] ^= 1 << 17;
                                    $data[$key][$i]['id']['value'] ^= 1 << 17;
                                }

                                if ($data[$key][$i]['id']['value'] & 1 << 16) {
                                    $data[$key][$i]['type']['value'] ^= 1 << 16;
                                    $data[$key][$i]['id']['value'] ^= 1 << 16;
                                }
                                if ($data[$key][$i]['id']['value'] & 1 << 15) {
                                    $data[$key][$i]['type']['value'] ^= 1 << 15;
                                    $data[$key][$i]['id']['value'] ^= 1 << 15;
                                }
                                if ($data[$key][$i]['id']['value'] & 1 << 14) {
                                    $data[$key][$i]['type']['value'] ^= 1 << 14;
                                    $data[$key][$i]['id']['value'] ^= 1 << 14;
                                }
                                if ($data[$key][$i]['id']['value'] & 1 << 13) {
                                    $data[$key][$i]['type']['value'] ^= 1 << 13;
                                    $data[$key][$i]['id']['value'] ^= 1 << 13;
                                }

                                if ($data[$key][$i]['id']['value'] != 410 && $data[$key][$i]['id']['value'] != 336 && $data[$key][$i]['id']['value'] != 472) {
                                    $data[$key][$i]['stat'] = array("type" => $value['stat'], "value" => stream::readInt32(false));
                                }

                                if ($data[$key][$i]['type']['value'] & 1 << 14) {
                                    $data[$key][$i]['dopStat1'] = array("type" => $value['dopStat1'], "value" => stream::readInt32(false));
                                }

                                if ($data[$key][$i]['type']['value'] & 1 << 14 && $data[$key][$i]['type']['value'] & 1 << 13) {
                                    $data[$key][$i]['dopStat2'] = array("type" => $value['dopStat2'], "value" => stream::readInt32(false));
                                }

                            } else {
                                $data[$key][$i] = self::readItemData($value);
                            }
                        }
                    }
                    self::$cycle = false;
                } else {
                    $data[$key] = self::readItemData($value);
                }
            } else {
                $data[$key] = stream::getValue($value);
            }
        }
        return $data;
    }

    static function writeItemData($data)
    {
        foreach ($data as $key => $value) {
            if (isset($value['value']) && isset($value['type'])) {
                stream::putValue($value['value'], $value['type']);
            } else {
                if ($key == "BonusInfo") {
                    if (isset($value['bonus'])) {
                        stream::writeInt32(count($value['bonus']), false);
                        foreach ($value['bonus'] as $k => $v) {
                            stream::writeInt32($v['id']['value'] + $v['type']['value'], false);
                            if ($v['id']['value'] != 410 && $v['id']['value'] != 336 && $v['id']['value'] != 472)
                                stream::writeInt32($v['stat']['value'], false);
                            if ($v['type']['value'] & 1 << 14)
                                stream::writeInt32($v['dopStat1']['value'], false);
                            if ($v['type']['value'] & 1 << 14 && $v['type']['value'] & 1 << 13)
                                stream::writeInt32($v['dopStat2']['value'], false);
                        }
                    } else {
                        stream::writeInt32(0, false);
                    }
                }

                if ($key == "cellInfo") {
                    $cellStone = isset($value['cellStone']) ? count($value['cellStone']) : 0;
                    stream::writeInt16($cellStone, false);
                    $cellFlag = isset($value['cellFlag']['value']) ? $value['cellFlag']['value'] : 0;
                    stream::writeInt16($cellFlag, false);
                    if (isset($value['cellStone']))
                        foreach ($value['cellStone'] as $kCell => $vCell)
                            stream::writeInt32($vCell['id']['value'], false);
                }
            }
        }
    }

    static function readData($structure, $octetToInt = false)
    {
        $data = array();
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                if (self::$cycle) {
                    if (self::$cycle > 0)
                        for ($i = 0; $i < self::$cycle; $i++)
                            $data[$key][$i] = self::readData($value, $octetToInt);
                    self::$cycle = false;
                } else
                    $data[$key] = self::readData($value);
            } else {
                if (isset(character::$addons[$key])) {
                    $getOctet = stream::getValue($value);
                    stream::putRead($getOctet['value'], 0);
                    foreach (character::$addons[$key] as $keyAddon => $valueAddon) {
                        if (is_array($valueAddon)) {
                            if (self::$cycle) {
                                if (self::$cycle > 0)
                                    for ($i = 0; $i < self::$cycle; $i++)
                                        $data[$key][$keyAddon][$i] = self::readData($valueAddon);
                                self::$cycle = false;
                            } else
                                $data[$key][$keyAddon] = self::readData($valueAddon);
                        } else
                            $data[$key][$keyAddon] = stream::getValue($valueAddon, $octetToInt);
                    }
                    stream::putRead(stream::$readData_copy, stream::$p_copy);
                } else
                    $data[$key] = stream::getValue($value, $octetToInt);
            }
        }
        return $data;
    }

    static function readCharacter($id, $dataToOctet = false)
    {
        $data['role'] = array();
        stream::writeInt32(-1);
        stream::writeInt32($id);
        stream::pack(character::$pack['getRole']);
        stream::$readData = socket::sendPacket(4, socket::packInt(config::$dbPort) . stream::$writeData, 2048 * 100);
        if (stream::$readData != "server:0") {
            stream::$pack = stream::readCUint32();
            stream::$length = stream::readCUint32();
            stream::readInt32();
            stream::readInt32();
            foreach (character::$structure['role'] as $keyStructure => $inStructure)
                $data['role'][$keyStructure] = self::readData($inStructure, $dataToOctet);
        }
        return $data;
    }

    static function writeData($data, $octetToData = true)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (isset($value['value']) && isset($value['type'])) {
                    stream::putValue($value['value'], $value['type'], $octetToData);
                } else {
                    if (isset(character::$addons[$key])) {
                        stream::putWrite("");
                        self::writeData($value, $octetToData);
                        stream::putWrite(stream::$writeData_copy);
                        stream::writeOctets(stream::$writeData_copy);
                    } else {
                        self::writeData($value, $octetToData);
                    }
                }
            }
        }
    }

    static function writeDataXml($data, $octetToData = true)
    {
        foreach ($data as $key => $value) {
            if ($key == "@attributes") {
                if (self::$cycle) {
                    if (self::$cycle > 0) {
                        foreach ($data[$key] as $keyI => $valueI) {
                            if (!isset($data[$key][$keyI]['@attributes'])) {
                                //stream::putValue($data[$key]['value'], $data[$key]['type'], $octetToData);
                                stream::putValue($data[$key][$keyI]['value'], $data[$key][$keyI]['type'], $octetToData);
                            } else {
                                stream::putValue($data[$key][$keyI]['@attributes']['value'], $data[$key][$keyI]['@attributes']['type'], $octetToData);
                            }
                        }
                    } else {
                        stream::putValue($data[$key]['value'], $data[$key]['type'], $octetToData);
                    }
                    self::$cycle = false;
                } else {
                    stream::putValue($data[$key]['value'], $data[$key]['type'], $octetToData);
                }
            } else {
                if (isset(character::$addons[$key])) {
                    stream::putWrite("");
                    foreach ($data[$key] as $keyA => $valueA) {
                        if (isset($valueA['@attributes'])) {
                            stream::putValue($valueA['@attributes']['value'], $valueA['@attributes']['type'], $octetToData);
                        } else {
                            foreach ($valueA as $keyAttr => $valueAttr) {
                                if (isset($valueAttr['@attributes'])) {
                                    stream::putValue($valueAttr['@attributes']['value'], $valueAttr['@attributes']['type'], $octetToData);
                                } else {
                                    foreach ($valueAttr as $keyAttr2 => $valueAttr2) {
                                        //system::debug($valueAttr2);
                                        if (isset($valueAttr2['@attributes'])) {
                                            stream::putValue($valueAttr2['@attributes']['value'], $valueAttr2['@attributes']['type'], $octetToData);
                                        } else
                                            system::debug($valueAttr);
                                        //stream::putValue($valueAttr[$keyAttr]['@attributes']['value'],  $valueAttr[$keyAttr]['@attributes']['type']);
                                    }
                                }
                            }
                        }
                    }
                    stream::putWrite(stream::$writeData_copy);
                    stream::writeOctets(stream::$writeData_copy);
                } else
                    self::writeDataXml($data[$key], $octetToData);
            }
        }
    }

    static function writeCharacterXML($id, $data, $octetToData = true)
    {
        stream::$writeData = "";
        stream::writeInt32(-1);
        stream::writeInt32($id);
        stream::writeByte(1);
        self::writeDataXml($data, $octetToData);
        stream::pack(character::$pack['putRole']);
        if (socket::sendPacket(3, socket::packInt(config::$dbPort) . stream::$writeData) != "server:0") {
            system::jms("success", "Персонаж сохранен");
        } else
            system::jms("danger", "Ошибка сохранения");
    }

    static function writeCharacter($id, $data, $octetToData = true)
    {
        stream::$writeData = "";
        stream::writeInt32(-1);
        stream::writeInt32($id);
        stream::writeByte(1);
        self::writeData($data, $octetToData);
        stream::pack(character::$pack['putRole']);
        //system::debug(stream::$writeData);
        if (socket::sendPacket(3, socket::packInt(config::$dbPort) . stream::$writeData) != "server:0") {
            system::jms("success", "Персонаж сохранен");
            return true;
        } else
            system::jms("danger", "Ошибка сохранения");
        return false;
    }

}