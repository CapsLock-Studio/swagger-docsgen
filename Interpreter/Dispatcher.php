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
        'property'  => 'properties'
    );

    private static $currentResource = null;
    private static $currentModel = null;

    public static function dispatch($params, &$tree)
    {
        foreach (self::$dispatchRoute as $key => $manual) {
            if (array_key_exists($key, $params) && $params[$key] !== '') {
                if (!isset($params['resource'])) {
                    $params['resource'] = self::$currentResource;
                } else {
                    self::$currentResource = $params['resource'];
                }
                if (!isset($params['model'])) {
                    $params['model'] = self::$currentModel;
                } else {
                    self::$currentModel = $params['model'];
                }
                $interpreter = new Interpreter($params, $tree, Manual::get($manual), true);
                $interpreter->interprete();
                break;
            }
        }
    }
}
