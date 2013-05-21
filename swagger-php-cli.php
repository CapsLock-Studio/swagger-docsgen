<?php

require_once('SwaggerGener.php');

use SWGCodeGener\SwaggerGener as SwaggerGener;

$shortopts = array(
    'h',// for help
    'r:', // the root path of the project
    'o:', // the output path of json docs
    'e:', // the excluded path
    'i:' // the excluded path
);

$longopts = array(
    'help', // for help
    'root_path:', // the discover path
    'output_path:', // the output path
    'excluded_path:', // the excluded path
    'init_path:' // the excluded path
);

$options = getopt(implode('', $shortopts), $longopts);

if (array_key_exists('h', $options) || array_key_exists('help', $options)) {
    echo <<<EOF
Usage: php swagger-php-cli.php [options]

    -e --excluded_path    set the excluded path. If not set, the one in
                                     configuration file will be used
    -h --help                   usage
    -i --init_path              set the path of the configuration file. If not set, the one
                                     in configuration file will be used
    -o --output_path        set the output path of the json docs. If not set, the one
                                     in configuration file will be used
    -r --root_path            set the root path of your project. If not set, the one
                                     in configuration file will be used
EOF;
exit;
}

$root_path = null;
$output_path = null;
$excluded_path = null;
$init_path = 'config.json';

array_walk($longopts, function($longopt, $idx) use ($shortopts, $options){
    while (substr($longopt, -1) === ':')
        $longopt = substr($longopt, 0, -1);
    $shortopt = $shortopts[$idx];
    while (substr($shortopt, -1) === ':')
        $shortopt = substr($shortopt, 0, -1);

    if (array_key_exists($shortopt, $options)) {
        $options[$longopt] = $options[$shortopt];
    }

    if (array_key_exists($longopt, $options))
        $$longopt = $options[$longopt];
});



$sg = new SwaggerGener($root_path, $output_path, $excluded_path, $init_path);
$sg->gen();

