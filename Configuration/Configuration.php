<?php

namespace SWGCodeGener\Configuration;

require_once(dirname(__FILE__).'/GlobalConf.php');

class Configuration extends GlobalConf
{
    public static function init($config_file)
    {
        parent::init($config_file);
        self::$instance->templatePath = realpath(self::$instance->templatePath);
        self::register(self::$instance);
        //var_dump(self::$instance);
    }
}

/*
Configuration::init('config.json');
var_dump(Configuration::get('apiVersion'));
*/
