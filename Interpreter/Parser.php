<?php

namespace SWGCodeGener\Interpreter;

require_once(dirname(__FILE__).'/../Utility/TypeUtils.php');

use SWGCodeGener\Utility\TypeUtils as TypeUtils;

class Parser {

    /**
    * The string that we want to parse
    */
    private $string;
    /**
    * Storge for the short description
    */
    private $shortDesc;
    /**
    * Storge for the long description
    */
    private $longDesc;
    /**
    * Storge for all the PHPDoc parameters list
    * each list contain a sort of paramters
    */
    private $paramsList = array();

    private $curParams = null;

    private $parsingLvl = array();

    private $preLvl = null;

    private $idx = 0;
    /**
    * Parse each line
    *
    * Takes an array containing all the lines in the string and stores
    * the parsed information in the object properties
    *
    * @param array $lines An array of strings to be parsed
    */
    private function parseLines($lines) {
        foreach($lines as $line) {
            $parsedLine = $this->parseLine($line); //Parse the line

            if($parsedLine === false && !isset($this->shortDesc)) {
                if (isset($desc)) {
                    $this->shortDesc = implode(PHP_EOL, $desc); //Store the first line in the short description
                    $desc = array();
                }
            } elseif($parsedLine !== false) {
                $desc[] = $parsedLine; //Store the line in the long description
            }
        }
        if (isset($desc)) {
            $this->longDesc = implode(PHP_EOL, $desc);
        }
    }
    /**
    * Parse the line
    *
    * Takes a string and parses it as a PHPDoc comment
    *
    * @param string $line The line to be parsed
    * @return mixed False if the line contains no parameters or paramaters
    * that aren't valid otherwise, the line that was passed in.
    */
    private function parseLine($line) {

        //Trim the whitespace from the line
        $line = trim($line);

        if(empty($line)) return false; //Empty line

        if(strpos($line, '@') === 0) {
            $param = substr($line, 1, strpos($line, ' ') - 1); //Get the parameter name
            $value = substr($line, strlen($param) + 2); //Get the value
            $this->setParam($param, $value);
            return false; //Parse the line and return false if the parameter is valid
        }

        return $line;
    }
    /**
    * Setup the valid parameters
    *
    * @param string $type NOT USED
    */
    private function setupParsingGroup()
    {
        $config = dirname(__FILE__).'/parser_config.json';
        $parsingLvl = json_decode(file_get_contents($config));

        for ($i=0; $i < count($parsingLvl); $i++) {
            if (is_string($parsingLvl[$i])) {
                $this->parsingLvl[$parsingLvl[$i]] = 0;
            } else if (is_array($parsingLvl[$i])) {
                foreach ($parsingLvl[$i] as $parsingRule) {
                    $this->parsingLvl[$parsingRule] = 1;
                }
            }
        }
    }

    private function getLvl($group)
    {
        return $this->parsingLvl[$group];
    }

    /**
    * Parse a parameter or string to display in simple typecast display
    *
    * @param string $string The string to parse
    * @return string Formatted string wiht typecast
    */
    private function formatParam($string) {
        $restrition = array(
            'required' => 'false',
            'multiple' => 'false'
        );

        $components = explode(' ', $string, 3);
        // for Container datatype. No name needed
        if (TypeUtils::isPrimitive($components[0]) === false) {
            $components = explode(' ', $string, 2);
            $components[2] = $components[1];
            $components[1] = $components[0];
        }
        $description = array_pop($components);
        $descs = explode('.', $description, 2);
        if (count($descs) === 2) {
            $restritionDesc = $descs[0];
            $keys = array_keys($restrition);
            foreach ($keys as $key) {
                if (strstr($restritionDesc, $key) !== false) {
                    $restrition[$key] = 'true';
                }
            }
            $description = $descs[1];
        }

        $url = $this->curParams['url'];
        $type = $components[0];
        $method = $this->curParams['method'];
        $name = $components[1];

        if (strstr($url, '{'. $name .'}') === false) {
            if (TypeUtils::isPrimitive($type) === true)
            {
                if ($method === 'GET') {
                    $paramType = 'query';
                } else {
                    $paramType = 'form';
                }
            } else {
                $paramType = 'body';
                $restrition['multiple'] = 'false';
            }
        } else {
            $paramType = 'path';
            $restrition['multiple'] = 'false';
        }

        array_push($restrition, $paramType);
        array_push($restrition, $description);
        return implode(' ', array_merge($components, $restrition));
    }

    private function setupProperty($value)
    {
        @list($id, $typeAndDesc) = explode(':', $value, 2);
        if (!isset($typeAndDesc)) {
            die('The type of property ' . $id . ' is empty.');
        }
        @list($type, $description) = explode(' ', $typeAndDesc, 2);
        if (isset($description)) {
            $this->curParams['description'] = $description;
        }
        $type = trim($type);
        $this->curParams['id'] = $id;
        if (TypeUtils::isArray($type)) {
            if (TypeUtils::isPrimitive(TypeUtils::getType($type))) {
                $itemType = 'type';
            } else {
                $itemType = '$ref';
            }
            $this->curParams['itemType'] = $itemType;
            $this->curParams['items'] = TypeUtils::getType($type);
            $type = 'Array';
        } else if (TypeUtils::isRange($type)) {
            list($min, $max) = TypeUtils::getRange($type);
            $this->curParams['min'] = intval(trim($min));
            $this->curParams['max'] = intval(trim($max));
            $this->curParams['valueType'] = 'RANGE';
            $type = 'int';
        } else if (TypeUtils::isList($type)) {
            list($type, $values) = TypeUtils::getList($type);
            $this->curParams['values'] = $values;
            $this->curParams['valueType'] = "LIST";
        }
        $this->curParams['type'] = $type;
    }

    /**
    * Set a parameter
    *
    * @param string $param The parameter name to store
    * @param string $value The value to set
    * @return bool True = the parameter has been set, false = the parameter was invalid
    */
    private function setParam($param, $value) {
        if(!array_key_exists($param, $this->parsingLvl)) return false;
        /*
         * init parsing group
         */
        if (is_null($this->curParams)) {
            $this->curParams = array();
        }

        if ($this->preLvl === 1 && $this->getLvl($param) === 0){
            $this->paramsList[] = $this->curParams;
            $this->curParams = array();
        }

        if($param === 'param')
            $value = $this->formatParam($value);

        if($param === 'property')
            $this->setupProperty($value);

        if(empty($this->curParams[$param])) {
            $this->curParams[$param] = $value;
        } else {
            if (!is_array($this->curParams[$param])) {
                $this->curParams[$param] =
                    array($this->curParams[$param]);
            }
            array_push($this->curParams[$param], $value);
        }
        if ($this->getLvl($param) === 0) {
            $this->paramsList[] = $this->curParams;
            $this->curParams = array();
        }
        $this->preLvl = $this->getLvl($param);
        return true;
    }
    /**
    * Setup the initial object
    *
    * @param string $string The string we want to parse
    */
    public function __construct($string) {
        $this->string = $string;
        $this->setupParsingGroup();
    }
    /**
    * Parse the string
    */
    public function parse() {
        //Get the comment
        if(preg_match('#^/\*\*(.*)\*/#s', $this->string, $comment) === false)
            die("Error");

        $comment = trim($comment[1]);

        //Get all the lines and strip the * from the first character
        if(preg_match_all('#^\s*\*(.*)#m', $comment, $lines) === false)
            die('Error');

        $this->parseLines($lines[1]);

        if (count($this->curParams) !== 0)
            array_push($this->paramsList,$this->curParams);
    }
    /**
    * Get the short description
    *
    * @return string The short description
    */
    public function getShortDesc() {
        return $this->shortDesc;
    }
    /**
    * Get the long description
    *
    * @return string The long description
    */
    public function getDesc() {
        return $this->longDesc;
    }
    /**
    * Get the parameters
    *
    * @return array The parameters
    */
    public function getParams() {
        //var_dump($this->paramsList);
        if ($this->idx === count($this->paramsList))
            return false;
        return $this->paramsList[$this->idx++];
    }
}
?>
