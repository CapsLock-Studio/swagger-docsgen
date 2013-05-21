<?php

namespace SWGCodeGener\Utility;

class DirDiscoverer
{
    private $paths = array();
    private $excludedPaths;

    public function __construct($dirPaths, $excludedPaths = null)
    {
        $this->excludedPaths = is_null($excludedPaths) ? null : $excludedPaths;
        foreach ($dirPaths as $dirPath) {
            if (!file_exists($dirPath))
                die($dirPath . ': the discover path is not exist' . "\n");

            $this->paths = array_merge($this->paths, $this->discover($dirPath));
        }
    }

    public function getPaths()
    {
        return $this->paths;
    }

    private function discover($path)
    {
        $paths = array();
        // is dir
        if (is_dir($path)) {
            if ($dh = opendir($path)) {
                while (($file = readdir($dh)) !== false) {
                    if (substr($file, 0, 1) !== '.') {
                        $file = $path . DIRECTORY_SEPARATOR . $file;
                        $paths = array_merge($paths, $this->discover($file));
                    }
                }
                closedir($dh);
            }
        }
        // is file
        else {
            $path = realpath($path);
            if (null === $this->excludedPaths)
                array_push($paths, $path);
            else {
                foreach ($this->excludedPaths as $excludedPath) {
                    if (strpos($path, $excludedPath) === 0)
                        break;
                }
                array_push($paths, $path);
            }
        }
        return $paths;
    }
}
