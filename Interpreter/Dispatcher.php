<?php

namespace SWGCodeGener\Interpreter;

require_once(dirname(__FILE__).'/Parser.php');
require_once(dirname(__FILE__).'/Interpreter.php');
require_once(dirname(__FILE__).'/../Configuration/Manual.php');

use SWGCodeGener\Configuration\Manual as Manual;

class Dispatcher
{
    private static $dispatchRoute = array(
        'resource' => 'resource',
        'url'          => 'apis',
        'model'     => 'model',
        'property' => 'properties'
    );

    private static $currentResource = null;

    public static function dispatch(Parser $parser, &$tree)
    {
        $params = $parser->getParams();
        foreach (self::$dispatchRoute as $key => $manual) {
            if (array_key_exists($key, $params) && $params[$key] !== '') {
                if ($params['resource'] === '') {
                    $params['resource'] = self::$currentResource;
                } else {
                    self::$currentResource = $params['resource'];
                }
                $interpreter = new Interpreter($params, $tree, Manual::get($manual), true);
                $interpreter->interprete();
                break;
            }
        }
    }
}
