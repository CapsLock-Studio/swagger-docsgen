<?php

namespace SWGCodeGener\Configuration;

abstract class GlobalConf
{
    protected static $registerTbl = array();
    protected static $instance;
    protected static $fileContent;

    public static function init($file)
    {
        $fileContent = file_get_contents($file);
        $obj = json_decode($fileContent);
        self::$fileContent = $fileContent;
        self::$instance = $obj;
    }

    public static function get($property)
    {
        return self::$registerTbl[get_called_class()]->{$property};
    }

    public static function display()
    {
        var_dump(self::$registerTbl);
    }

    protected static function register($val)
    {
        self::$registerTbl[get_called_class()] = $val;
    }
}

//Manual::init('hierarchy.json');
