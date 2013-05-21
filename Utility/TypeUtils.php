<?php

namespace SWGCodeGener\Utility;

class TypeUtils
{
    protected static $primitive = null;

    public static function isPrimitive($type)
    {
        if (is_null(self::$primitive)) {
            self::$primitive = array_flip(array('byte', 'boolean', 'Date', 'int', 'string', 'double', 'float', 'void'));
        }
        return array_key_exists($type, self::$primitive);
    }

    public static function getType($type)
    {
        $type = trim($type);
        if (self::isArray($type)) {
            return substr($type, strlen('List['),-1);
        } else {
            return $type;
        }
    }

    public static function isArray($type)
    {
        $type = strtolower(trim($type));
        return strpos($type, 'list[') === 0 && substr($type, -1) === ']';
    }

    public static function isRange($type)
    {
        return strpos($type, '-') !== false;
    }

    public static function getRange($type)
    {
        return explode('-', $type);
    }

    public static function isList($type)
    {
        @list($dummy, $type) = explode('(', trim($type), 2);
        $type = trim($type);
        return substr($type, -1) === ')';
    }

    public static function getList($type)
    {
        list($type, $values) = explode('(', $type, 2);
        return array(trim($type), explode('|', substr(trim($values), 1,-1)));
    }
}
