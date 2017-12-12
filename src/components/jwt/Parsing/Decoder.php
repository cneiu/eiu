<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt\Parsing;


use RuntimeException;


/**
 * Class that decodes data according with the specs of RFC-4648
 */
class Decoder
{
    /**
     * Decodes from JSON, validating the errors (will return an associative array
     * instead of objects)
     *
     * @param string $json
     *
     * @return mixed
     *
     * @throws RuntimeException When something goes wrong while decoding
     */
    public function jsonDecode($json)
    {
        $data = json_decode($json);
        
        if (json_last_error() != JSON_ERROR_NONE)
        {
            throw new RuntimeException('Error while decoding to JSON: ' . json_last_error_msg());
        }
        
        return $data;
    }
    
    /**
     * Decodes from base64url
     *
     * @param string $data
     *
     * @return string
     */
    public function base64UrlDecode($data)
    {
        if ($remainder = strlen($data) % 4)
        {
            $data .= str_repeat('=', 4 - $remainder);
        }
        
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
