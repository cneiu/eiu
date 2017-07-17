<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\yaml;


use eiu\components\Component;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;


/**
 * JWT
 *
 * @package eiu\core\service\event
 */
class YamlComponent extends Component
{
    /**
     * 解析YAML字符串
     *
     *  Usage:
     *  <code>
     *   $array = Yaml::parse(file_get_contents('config.yml'));
     *   print_r($array);
     *  </code>
     *
     * @param string $input A string containing YAML
     * @param int    $flags A bit field of PARSE_* constants to customize the YAML parser behavior
     *
     * @return mixed The YAML converted to a PHP value
     *
     * @throws ParseException If the YAML is not valid
     */
    public static function parse($input, $flags = 0)
    {
        $yaml = new Parser();
        
        return $yaml->parse($input, $flags);
    }
    
    /**
     * 生成YAML字符串
     *
     * The dump method, when supplied with an array, will do its best
     * to convert the array into friendly YAML.
     *
     * @param mixed $input  The PHP value
     * @param int   $inline The level where you switch to inline YAML
     * @param int   $indent The amount of spaces to use for indentation of nested nodes
     * @param int   $flags  A bit field of DUMP_* constants to customize the dumped YAML string
     *
     * @return string A YAML string representing the original PHP value
     */
    public static function dump($input, $inline = 2, $indent = 4, $flags = 0)
    {
        $yaml = new Dumper($indent);
        
        return $yaml->dump($input, $inline, 0, $flags);
    }
}