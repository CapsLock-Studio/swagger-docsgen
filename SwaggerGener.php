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
        // var_dump($paths);
        foreach ($paths as $path) {
            $tb = new TreeBuilder($path);
            $this->assignTree($tb->build());
            //echo json_encode($tb->build(), JSON_PRETTY_PRINT) . "\n";
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
                                    $resource, $this->modelTable, $model);
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

        if (isset($resource['models'][$model]))
            return $resource;
        $resource['models'][$model] = $modelTable[$model];
        $relatedModels = $this->getRelatedModel($model, $this->modelTable);
        foreach ($relatedModels as $model) {
            $resource = $this->bindModelToResource($resource, $modelTable, $model);
        }
        return $resource;
    }

    private function getRelatedModel($model, $modelTable, $relatedModels = null)
    {
        //var_dump($model);
        if (is_null($relatedModels)) {
            $relatedModels = array();
        }
        foreach ($modelTable[$model]['properties'] as $property) {
            $type = $this->getType($property);
            if (false === TypeUtils::isPrimitive($type)) {
                array_push($relatedModels, $type);
                $relatedModels = array_merge($relatedModels,
                    $this->getRelatedModel($type, $modelTable, $relatedModels));
            }
        }
        return $relatedModels;
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
                    //$body['apis'] = array_values($body['apis']);
                    //array_push($this->resourceList, $body);
                    break;

                case 'model':
                    $this->modelTable[$body['id']] = $body;
                    break;
            }
        }
    }
}

/*
$sg = new SwaggerGener();
$sg->gen();
*/
