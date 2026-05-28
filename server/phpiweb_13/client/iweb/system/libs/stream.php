<?php

namespace system\libs;
if (!defined('IWEB')) {
    die("Error!");
}

class stream
{

    static $readData = "";
    static $readData_copy = "";
    static $writeData = "";
    static $writeData_copy = "";
    static $p = 0;
    static $p_copy = 0;
    static $pack, $length = 8;
    static $error = 0;


    //Manager Function

    static function putRead($data, $p = 0)
    {
        self::$readData_copy = self::$readData;
        self::$readData = $data;
        self::$p_copy = self::$p;
        self::$p = $p;
    }

    static function putWrite($data)
    {
        self::$writeData_copy = self::$writeData;
        self::$writeData = $data;
    }

    static function cuint($data)
    {
        if ($data <= 127)
            return pack("C", $data);
        if ($data < 16384)
            return pack("n", $data | 32768);
        if ($data < 536870912)
            return pack("N", $data | 3221225472);
        return pack("c", -32) . pack("N", $data);
    }

    static function pack($id)
    {
        self::$writeData = self::cuint($id) . self::cuint(strlen(self::$writeData)) . self::$writeData;
    }

    static function getValue($value, $octetToInt = false)
    {
        $data['type'] = $value;
        switch ($value) {
            case "int16":
                $data['value'] = self::readInt16();
                break;

            case "int32":
                $data['value'] = self::readInt32();
                break;

            case "int16sm":
                $data['value'] = self::readInt16(false);
                break;

            case "int32sm":
                $data['value'] = self::readInt32(false);
                break;

            case "int16rev":
                $data['value'] = self::readInt16Rev();
                break;

            case "int32rev":
                $data['value'] = self::readInt32Rev();
                break;

            case "cuint":
                $data['value'] = self::readCUint32();
                if ($data['value'] > 0) GRole::$cycle = $data['value'];
                else GRole::$cycle = -1;
                break;

            case "cuint-nc":
                $data['value'] = self::readCUint32();
                break;

            case "cuint16":
                $data['value'] = self::readInt16();
                if ($data['value'] > 0) GRole::$cycle = $data['value'];
                else GRole::$cycle = -1;
                break;

            case "cuint32":
                $data['value'] = self::readInt32();
                if ($data['value'] > 0) GRole::$cycle = $data['value'];
                else GRole::$cycle = -1;
                break;

            case "cuint16sm":
                $data['value'] = self::readInt16(false);
                if ($data['value'] > 0) GRole::$cycle = $data['value'];
                else GRole::$cycle = -1;
                break;

            case "cuint32sm":
                $data['value'] = self::readInt32(false);
                if ($data['value'] > 0) GRole::$cycle = $data['value'];
                else GRole::$cycle = -1;
                break;

            case "cbyte":
                $data['value'] = self::readByte();
                if ($data['value'] > 0) GRole::$cycle = $data['value'];
                else GRole::$cycle = -1;
                break;

            case "float":
                $data['value'] = self::readSingle();
                break;

            case "float-sm":
                $data['value'] = self::readSingle(false);
                break;

            case "byte":
                $data['value'] = self::readByte();
                break;

            case "octets":
                $data['value'] = self::readOctets($octetToInt);
                break;

            case "string":
                $data['value'] = self::readString();
                break;
            case "color":
                $data['value'] = self::readColor();
                break;
            case "timestamp":
                $time = self::readInt32();
                $second =  $time-time();
                if ($second <= 0)
                    $data['value'] = 0;
                else
                    $data['value'] = $second;
                break;
            default:
                $data['value'] = "";
                break;
        }

        return $data;
    }

    static function putValue($data, $type, $intToOctet = false)
    {
        switch ($type) {
            case "int16":
                self::writeInt16($data);
                break;

            case "int32":
                self::writeInt32($data);
                break;

            case "int16sm":
                self::writeInt16($data, false);
                break;

            case "int32sm":
                self::writeInt32($data, false);
                break;

            case "int16rev":
                self::writeInt16Rev($data);
                break;

            case "int32rev":
                self::writeInt32Rev($data);
                break;

            case "cuint":
                GRole::$cycle = ($data > 0) ? $data : -1;
                self::writeCUint32($data);
                break;

            case "cuint16sm":
                GRole::$cycle = ($data > 0) ? $data : -1;
                self::writeInt16($data, false);
                break;

            case "cuint32sm":
                GRole::$cycle = ($data > 0) ? $data : -1;
                self::writeInt32($data, false);
                break;

            case "float":
                self::writeSingle($data);
                break;
            case "float-sm":
                self::writeSingle($data, false);
                break;

            case "byte":
                self::writeByte($data);
                break;

            case "octets":
                self::writeOctets($data, $intToOctet);
                break;

            case "string":
                self::writeString($data);
                break;

            case "color":
                self::writeColor($data);
                break;
            case "timestamp":
                $second = time() + $data;
                if (time() == $second)
                    self::writeInt32(0);
                else
                    self::writeInt32($second);
                break;
        }
    }

    //Read Function

    static function readInt16($big = true)
    {
        if (self::$length >= self::$p + 2) {
            $data = substr(self::$readData, self::$p, 2);
            if (strlen($data) == 2) {
                if ($big == true) $result = unpack("n", $data); else $result = unpack("v", $data);
                self::$p += 2;
                return $result[1];
            } else self::$error+=1;
        }
        return 0;
    }

    static function readInt16Rev()
    {
        if (self::$length >= self::$p + 2) {
            $data = substr(self::$readData, self::$p, 2);
            if (strlen($data) == 2) {
                $result = unpack("n", strrev($data));
                self::$p += 2;
                return $result[1];
            } else self::$error+=1;
        }
        return 0;
    }

    static function readInt32($big = true)
    {
        if (self::$length >= self::$p + 4) {
            $data = substr(self::$readData, self::$p, 4);
            if (strlen($data) == 4) {
                if ($big) $result = unpack("i", strrev($data)); else $result = unpack("i", $data);
                self::$p += 4;
                return $result[1];
            } else self::$error+=1;
        }
        return 0;
    }

    static function readInt32Rev()
    {
        if (self::$length >= self::$p + 4) {
            $data = substr(self::$readData, self::$p, 4);
            if (strlen($data) == 4) {
                $result = unpack("N", strrev($data));
                self::$p += 4;
                return $result[1];
            } else self::$error+=1;
        }
        return 0;
    }

    static function readByte()
    {
        if (self::$length >= self::$p + 1) {
            $data = substr(self::$readData, self::$p, 1);
            if (strlen($data) == 1) {
                $result = unpack("C", $data);
                self::$p++;
                return $result[1];
            } else self::$error+=1;
        }
        return 0;

    }

    static function readOctets($toInt = false)
    {
        $size = self::readCUint32();
        $data = substr(self::$readData, self::$p, $size);
        if (strlen($data) == $size) {
            if ($toInt) {
                $result = bin2hex($data);
            } else
                $result = $data;
            self::$p += $size;
            return $result;
        } else self::$error+=1;
        return 0;
    }

    static function readCUint32()
    {
        $byte = self::ReadByte();

        self::$p -= 1;
        switch ($byte & 224) {
            case 224:
                self::ReadByte();
                return self::ReadInt32();
            case 192:
                return self::ReadInt32() & 1073741823;
            case 128:
            case 160:
                return self::ReadInt16() & 32767;
        }
        return self::ReadByte();
    }

    static function readString()
    {
        $size = self::readCUint32();
        $result = substr(self::$readData, self::$p, $size);
        if (strlen($result) == $size) {
            self::$p += $size;
            $result = iconv("UTF-16", "UTF-8", $result);
            return $result;
        } else self::$error+=1;
        return 0;
    }

    static function readSingle($big = true)
    {
        if (self::$length >= self::$p + 4) {
            $data = substr(self::$readData, self::$p, 4);
            if (strlen($data) == 4) {
                if ($big == true)
                    $result = unpack("f", strrev($data));
                else
                    $result = unpack("f", $data);
                self::$p += 4;
                return $result[1];
            } else self::$error+=1;
        }
        return 0;

    }

    static function readColor()
    {
        return dechex(self::readInt32Rev());
    }

    //Write Function

    static function writeInt16($data, $big = true)
    {
        if ($big == true)
            self::$writeData .= pack("n", $data);
        else
            self::$writeData .= pack("v", $data);

    }

    static function writeInt16Rev($data)
    {
        self::$writeData .= strrev(pack("n", $data));
    }

    static function writeInt32($data, $big = true, $arg1 = false, $arg2 = false)
    {
        if ($big == true) {
            if ($arg1 !== false && $arg2 === false)
                self::$writeData .= pack("N*", $arg1, $data);
            else if ($arg1 !== false && $arg2 !== false)
                self::$writeData .= pack("N*", $arg1, $arg2, $data);
            else
                self::$writeData .= pack("N*", $data);
        } else
            self::$writeData .= pack("V*", $data);
    }

    static function writeInt32Rev($data)
    {
        self::$writeData .= strrev(pack("N", $data));
    }

    static function writeByte($data)
    {
        self::$writeData .= pack("C", $data);
    }

    static function writeOctets($data, $toData = false)
    {
        if ($toData) {
            $pack = @pack("H*", $data);
            self::$writeData .= self::cuint(strlen($pack)) . $pack;
        } else {
            self::$writeData .= self::cuint(strlen($data)) . $data;
        }
    }

    static function writeCUint32($data, $big = true)
    {
        if ($data <= 127)
            self::writeByte($data);
        else {
            if ($data < 16384)
                self::WriteInt16($data | 32768, $big);
            else
                if ($data < 536870912)
                    self::WriteInt32($data | 3221225472);
        }
    }

    static function writeString($data)
    {
        $result = iconv("UTF-8", "UTF-16LE", $data);
        self::$writeData .= self::cuint(strlen($result)) . $result;
    }

    static function writeSingle($data, $big = true)
    {
        if ($big == true)
            self::$writeData .= strrev(pack("f", $data));
        else
            self::$writeData .= pack("f", $data);
    }

    static function writeColor($data)
    {
        self::writeInt32Rev(hexdec($data));
    }

}