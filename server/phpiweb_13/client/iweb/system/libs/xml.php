<?php

namespace system\libs;

if (!defined('IWEB')) {
    die("Error!");
}

class xml
{
    static function go_xml($data, &$xml_data)
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item' . $key;
            }
            if (is_array($value)) {
                $subnode = $xml_data->addChild($key);
                    self::go_xml($value, $subnode);
            } else {
                    $xml_data->addAttribute($key, htmlspecialchars($value));
            }
        }
    }

    static function encode($data)
    {
        if (is_array($data) || is_object($data)) {
            $xml_data = new \SimpleXMLElement('<?xml version="1.0"?><role></role>');
            self::go_xml($data, $xml_data);
            $dom = dom_import_simplexml($xml_data)->ownerDocument;
            $dom->formatOutput = true;
            return $dom->saveXML();
        } else {
            return $data;
        }
    }

    static function decode($data)
    {
        $data = simplexml_load_string($data);
        $data = str_replace("item", "", json_encode($data));
        $data = json_decode($data, TRUE);
        return $data;
    }
}
