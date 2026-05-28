<?php

if (!defined('IWEB')) {
    die("Error!");
}

include dir . "/system/data/lang/".\system\data\config::$site_lang."/site.php";

function __autoload($name)
{
    $path = dir . DIRECTORY_SEPARATOR . str_replace("\\", "/", $name) . ".php";

    if (file_exists($path))
        include $path;
    //else
    // \system\libs\system::debug("Error include file: $name");
}

if (file_exists(dir . "/system/data/config.php"))
    require_once dir . "/system/data/character/" . \system\data\config::$version . "/character.php";
