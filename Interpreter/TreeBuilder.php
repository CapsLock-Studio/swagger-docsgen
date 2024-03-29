<?php

namespace SWGCodeGener\Interpreter;

require_once(dirname(__FILE__).'/Parser.php');
require_once(dirname(__FILE__).'/Dispatcher.php');

class TreeBuilder
{
    private $fileName;
    private $fileContent;
    private $treeType;
    private $trees = null;

    public function __construct($fileName)
    {
        $this->fileName = $fileName;
        $this->fileContent = file_get_contents($fileName);
    }

    public function build()
    {
        $tokens = token_get_all($this->fileContent);
        $tree = array();
        $first = true;
        $curState = 0;
        foreach ($tokens as $t) {
            if (is_array($t) && 371 === $t[0]) {
                $parser = new Parser($t[1]);
                $parser->parse();
                while ($params = $parser->getParams()) {
                    Dispatcher::dispatch($params, $tree);
                }
            }
        }
        //echo json_encode($tree, JSON_PRETTY_PRINT) . "\n";
        //die();
        return $tree;
    }

    public function getType()
    {
        return $this->type;
    }
}

?>
