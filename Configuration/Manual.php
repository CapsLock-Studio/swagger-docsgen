<?php

namespace SWGCodeGener\Configuration;

require_once(dirname(__FILE__).'/GlobalConf.php');

class Manual extends GlobalConf
{
    protected static $manuals;

    public static function init($file)
    {
        parent::init($file);
        $obj = json_decode(json_encode(self::$instance), true);
        self::$manuals = new \stdClass();
        self::convertToManual($obj, null);
        //echo json_encode(self::$manuals, JSON_PRETTY_PRINT);
        self::register(self::$manuals);
    }

    private static function convertToManual($structure, $absoluteHierarchy)
    {
        $neededKeys = null;
        $absoluteHierarchy = is_null($absoluteHierarchy) ? '' : $absoluteHierarchy . '.';
        foreach ($structure as $key => $content) {
            $manual = self::getKey($key);
            self::$manuals->{$manual} = new \stdClass();
            self::$manuals->{$manual}->relativeHierarchy = $key;
            self::$manuals->{$manual}->absoluteHierarchy = $absoluteHierarchy . $key;
            self::$manuals->{$manual}->rule = array();

            foreach ($content as $attr => $value) {
                $neededKeys = is_null($neededKeys) ? '' : $neededKeys . ',';
                if (is_array($value)) {
                    $keys = self::convertToManual(array($attr => $value), $absoluteHierarchy . $key);
                    $neededKeys .= $keys;
                    array_push(self::$manuals->{$manual}->rule,
                        self::createInterpreterRule($keys, self::getKey($attr), self::isArray($attr)));
                } else {
                    $rule = self::createRule($attr, $value);
                    array_push(self::$manuals->{$manual}->rule, $rule);
                    $neededKeys .= $attr;
                    $neededKeys .= self::extractNeededKeys($value);
                }
            }
        }
        return $neededKeys;
    }

    private static function extractNeededKeys($desc)
    {
        $neededKeys = '';
        $keys = explode(',', $desc);
        foreach ($keys as $key) {
            if (substr($key, 0,1) === '{' && substr($key, -1) === '}') {
                $neededKeys = $neededKeys === '' ? '' : $neededKeys . ',';
                $neededKeys .= substr($key, 1, -1);
            }
        }
        return $neededKeys;
    }

    private static function createInterpreterRule($key, $manual, $isArray)
    {
        $obj = new \stdClass();
        $obj->key = $key;
        $obj->type = 'interpreter';
        $obj->desc = $manual;
        if (strpos($key, ',') !== false) {
            $isArray = false;
        }
        $obj->isArray = $isArray;
        return $obj;
    }

    private static function createRule($key, $desc)
    {
        $key = trim($key);
        $rule = new \stdClass();
        $rule->key = $key;
        if (trim($desc) === '$global') {
            $type = 'global';
            $desc = null;
        } else if (is_null($desc) || @strpos($desc, ' ') === false) {
            $type = 'token';
        } else {
            $type = 'rule';
        }
        $rule->type = $type;
        $rule->desc = $desc;
        return $rule;
    }

    private static function isArray($key)
    {
        return substr($key, -2) === '[]';
    }

    private static function getKey($keyWithType)
    {
        $keys = explode('.', $keyWithType);
        return $keys[0];
    }
}

//Manual::init('template.json');
//var_dump(Manual::get('resource'));
