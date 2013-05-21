<?php

namespace SWGCodeGener\DocsGener;

require_once(dirname(__FILE__).'/../Configuration/Configuration.php');

use SWGCodeGener\Configuration\Configuration as Configuration;

class StaticSwaggerDocGenerater
{
    private $outputDir;
    private $resourceList;
    //private $apiDocs = array();

    public function __construct($outputDir = null, $resourceList = null)
    {
        $this->outputDir = $outputDir;
        $this->resourceList = $resourceList;
    }

    private function buildDir($path)
    {
        if (!file_exists($path)) {
            return mkdir($path, 0755, true);
        } else {
            return is_dir($path);
        }
    }

    public function gen($resourceList = null, $outputDir = null)
    {
        $apiDocs = array();
        $dir = is_null($outputDir) ? $this->outputDir : $outputDir;
        if (false === $this->buildDir($dir)) {
            die("invalid output path");
        }
        $resourceList = is_null($resourceList) ? $this->resourceList : $resourceList;

        $apiDocs['apis'] = array();
        array_walk($resourceList, function($resource) use (&$apiDocs, $dir) {
            $path = $resource['resource'];
            array_push($apiDocs['apis'], array('path' => $path));
            $path = $dir . DIRECTORY_SEPARATOR . substr($path, 1);
            file_put_contents($path,
                json_encode($resource, JSON_PRETTY_PRINT));
            echo '[Generated] ' . $path . "\n";
        });
        $path = $dir . DIRECTORY_SEPARATOR . 'api-docs.json';
        $apiDocs['apiVersion'] = Configuration::get('apiVersion');
        $apiDocs['swaggerVersion'] = Configuration::get('apiVersion');
        $apiDocs['basePath'] = Configuration::get('resourceListPath');
        file_put_contents($path,
                json_encode($apiDocs, JSON_PRETTY_PRINT));
        echo '[Generated] ' . $path . "\n";
        echo '[Done] ^_^' . "\n";
    }
}
