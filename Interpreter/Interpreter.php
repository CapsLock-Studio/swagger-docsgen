<?php

namespace SWGCodeGener\Interpreter;

require_once(dirname(__FILE__).'/../Configuration/Manual.php');
require_once(dirname(__FILE__).'/../Configuration/Configuration.php');

use SWGCodeGener\Configuration\Configuration as Configuration;
use SWGCodeGener\Configuration\Manual as Manual;

class Interpreter
{
    protected $hierarchy;
    protected $tree;
    protected $parser;
    protected $params;
    protected $startNode;
    protected $manual = array();

    public function __construct($params, &$tree, $manual, $useAbsoluteHierarchy = false)
    {
        $hierarchy = $useAbsoluteHierarchy ? 'absoluteHierarchy' : 'relativeHierarchy';
        $this->hierarchy = $manual->{$hierarchy};
        $this->manual = $manual->rule;
        $this->tree = &$tree;
        $this->params = $params;
        $this->pointToStartNode($this->hierarchy, $tree);
    }

    protected function pointToStartNode($path, &$tree)
    {
        $components = explode('.', $path);
        $node = array_shift($components);
        if ($node === "[]") {
            $this->startNode = &$tree[];
            return;
        }
        $node = $this->bindParam($node);
        if (!array_key_exists($node, $tree)) {
            $tree[$node] = array();
        }
        if (count($components) === 0) {
            $this->startNode = &$tree[$node];
        } else {
            $path = implode('.', $components);
            $this->pointToStartNode($path, $tree[$node]);
        }
    }

    protected function bindParam($node)
    {
        if (substr($node, 0, 1) === '{' && substr($node, -1) === '}'){
            return $this->params[substr($node, 1, -1)];
        }
        return $node;
    }

    protected function filterParams($key)
    {
        $exclude = substr($key, 0, 1) === '!';
        $params = array();
        $keys = array_keys($this->params);
        foreach ($keys as $k) {
            if (strstr($key, $k) xor $exclude) {
                if ($this->params[$k] !== '')
                    $params[$k] = $this->params[$k];
            }
        }
        return $params;
    }

    public function interprete()
    {
        $params = $this->params;
        //var_dump($params);
        foreach ($this->manual as $rule) {
            $key = $rule->key;
            if ($rule->type === 'interpreter'){
                $manual = $rule->desc;
                $params = $this->filterParams($key);
                if (count($params) === 0)
                    continue;
                if (isset($rule->isArray)
                    && $rule->isArray === true
                    && is_array($params[$key]))
                {
                    $params = $params[$key];
                    foreach ($params as $param) {
                        $p = array($key => $param);
                        $interpreter = new Interpreter($p, $this->startNode, Manual::get($manual));
                        $interpreter->interprete();
                    }
                } else {
                    $interpreter = new Interpreter($params, $this->startNode, Manual::get($manual));
                    $interpreter->interprete();
                }
            } else if ($rule->type === 'global') {
                $this->setAttr($key, Configuration::get($key));
            } else if (array_key_exists($key, $params)) {
                if (isset($params[$key]) && $params[$key] !== null) {
                    switch ($rule->type) {
                        case 'token':
                            $attr = is_null($rule->desc)? $key: $rule->desc;
                            $this->setAttr($attr, $params[$key]);
                        break;
                        case 'rule':
                            $attrList = explode(' ', $rule->desc);
                            $valueList = explode(' ', $params[$key], count($attrList));
                            while($attr = array_shift($attrList)) {
                                $value = array_shift($valueList);
                                $this->setAttr($attr, $value);
                            }
                        break;
                    }
                }
            }
        }
    }

    public function setAttr($attr, $value)
    {
        $attr = self::bindParam($attr);
        if (!is_array($value) && trim($value) === 'false')
            $value = false;
        else if (!is_array($value) && trim($value) === 'true')
            $value = true;
        $this->startNode[$attr] = $value;
    }
}
