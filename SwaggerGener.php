<?php

namespace SWGCodeGener;

require_once(dirname(__FILE__).'/Interpreter/TreeBuilder.php');
require_once(dirname(__FILE__).'/Utility/DirDiscoverer.php');
require_once(dirname(__FILE__).'/Utility/TypeUtils.php');
require_once(dirname(__FILE__).'/DocsGener/StaticSwaggerDocGenerater.php');
require_once(dirname(__FILE__).'/Configuration/Configuration.php');
require_once(dirname(__FILE__).'/Configuration/Manual.php');

use SWGCodeGener\Utility\TypeUtils as TypeUtils;
use SWGCodeGener\Utility\DirDiscoverer as DirDiscoverer;
use SWGCodeGener\Interpreter\TreeBuilder as TreeBuilder;
use SWGCodeGener\DocsGener\StaticSwaggerDocGenerater as StaticSwaggerDocGenerater;
use SWGCodeGener\Configuration\Configuration as Configuration;
use SWGCodeGener\Configuration\Manual as Manual;

class SwaggerGener
{
    private $resourceList = array();
    private $modelTable = array();
    private $dirDiscoverer;
    private $docGener;

    public function __construct($discoverPath=null, $outputDir = null, $excludedPath = null, $config_path = null)
    {
        Configuration::init(realpath($config_path));
        Manual::init(Configuration::get('templatePath'));
        //Configuration::display();

        if (is_null($discoverPath))
            $discoverPath = Configuration::get('discoverPath');
        if (is_null($outputDir))
            $outputDir = Configuration::get('outputDir');
        if (is_null($excludedPath))
            $excludedPath = Configuration::get('excludedPath');

        $this->dirDiscoverer = new DirDiscoverer($discoverPath, $excludedPath);
        $this->docGener = new StaticSwaggerDocGenerater($outputDir);
    }

    public function gen()
    {
        $paths = $this->dirDiscoverer->getPaths();
        foreach ($paths as $path) {
            $tb = new TreeBuilder($path);

            $this->assignTree($tb->build());
        }
        $this->resourceList = array_values($this->resourceList);
        $this->resourceList = $this->bindModelToResourceList();
        //echo json_encode($this->resourceList, JSON_PRETTY_PRINT) . "\n";
        //echo json_encode($this->modelTable, JSON_PRETTY_PRINT) . "\n";
        $this->docGener->gen($this->resourceList);
    }

    private function bindModelToResourceList()
    {
        $modelBindedResourceList = array();
        foreach ($this->resourceList as $resource) {
            $modelBindedResource = $resource;
            foreach ($resource['apis'] as $api) {
                foreach ($api['operations'] as $operation) {
                    if (isset($operation['responseClass'])) {
                        $model = TypeUtils::getType($operation['responseClass']);
                        if (false === TypeUtils::isPrimitive($model)) {
                            $modelBindedResource = $this->bindModelToResource(
                                $modelBindedResource, $this->modelTable, $model);
                        }
                    }
                    if (isset($operation['parameters'])) {
                        foreach ($operation['parameters'] as $parameter) {
                            $model = $parameter['dataType'];
                            if (false === TypeUtils::isPrimitive($model)) {
                                $modelBindedResource = $this->bindModelToResource(
                                    $modelBindedResource, $this->modelTable, $model);
                            }
                        }
                    }
                }
            }
            array_push($modelBindedResourceList, $modelBindedResource);
        }
        return $modelBindedResourceList;
    }

    private function bindModelToResource($resource, $modelTable, $model)
    {
        $protoModel = $this->extractModelName($model);
        if (isset($resource['models'][$model])) {
            return $resource;
        }
        if (!isset($modelTable[$protoModel])) {
            echo '[Warning] Resource: '.$resource['resource'].' model ' . $model . ' not found. on line ' . __LINE__ .
            " of " .__FILE__. "\n";
            return $resource;
        }
        $resource['models'][$model] = $modelTable[$protoModel];
        $resource['models'][$model] = $this->precompile($resource['models'][$model], $model);
        $relatedModels = $this->getRelatedModel($model, $this->modelTable);
        foreach ($relatedModels as $model) {
            $resource = $this->bindModelToResource($resource, $modelTable, $model);
        }
        return $resource;
    }

    private function getRelatedModel($model, $modelTable, $relatedModels = null)
    {
        if (is_null($relatedModels)) {
            $relatedModels = array();
        }
        if (!isset($modelTable[$model])) {
            //echo '[Warning] Model ' . $model . ' not found. on line ' . __LINE__ . " of " . __FILE__."\n";
            return $relatedModels;
        }
        foreach ($modelTable[$model]['properties'] as $property) {
            $type = $this->getType($property);
            if (false === TypeUtils::isPrimitive($type)) {
                array_push($relatedModels, $type);
                @$this->precompile($this->modelTable[$this->extractModelName($type)],
                    $type);
                $relatedModels = array_merge($relatedModels,
                    $this->getRelatedModel($type, $modelTable, $relatedModels));
            }
        }
        return $relatedModels;
    }

    private function precompile($model, $prototype)
    {
        $args = $this->extractModelArgs($prototype);
        foreach ($args as $key => $value) {
            if (isset($model['properties'][$key]))
                $model['properties'][$key] = array(
                    'type' => $value
                );
        }
        $model['id'] = $prototype;
        if (!isset($this->modelTable[$prototype]))
            $this->modelTable[$prototype] = $model;
        return $model;
    }

    private function extractModelName($model)
    {
        $model = trim($model);
        if (substr($model, -1) !== ')')
            return $model;
        else
            return substr($model, 0, strpos($model, '('));
    }

    private function extractModelArgs($model)
    {
        $model = trim($model);
        if (substr($model, -1) !== ')')
            return array();
        else {
            $args = substr($model, strpos($model, '(') + 1, -1);
            $args = explode(',', $args);
            $pairedArgs = array();
            foreach ($args as $arg) {
                list($key, $value) = explode(':', $arg, 2);
                $pairedArgs[trim($key)] = trim($value);
            }
            return $pairedArgs;
        }
    }

    private function getType($property)
    {
        if ($property['type'] !== 'Array') {
            return $property['type'];
        }
        return array_pop($property['items']);
    }

    private function assignTree($tree)
    {
        if (null === $tree)
            return;
        foreach ($tree as $type => $body) {
            switch ($type) {
                case 'resource':
                    $resourceList = &$this->resourceList;
                    array_walk($body, function($content, $key) use (&$resourceList) {
                        $content['apis'] = array_values($content['apis']);
                        if (isset($resourceList[$key])) {
                            $resourceList[$key]['apis'] = array_merge(
                                $resourceList[$key]['apis'], $content['apis']);
                        } else {
                            $resourceList[$key] = $content;
                        }
                    });
                    break;

                case 'model':
                    foreach ($body as $key => $model) {
                        $this->modelTable[$model['id']] = $model;
                    }
                    break;
            }
        }
    }
}

/*
$sg = new SwaggerGener();
$sg->gen();
*/
