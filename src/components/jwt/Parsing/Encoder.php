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
 * Class that encodes data according with the specs of RFC-4648
 */
class Encoder
{
    /**
     * Encodes to JSON, validating the errors
     *
     * @param mixed $data
     *
     * @return string
     *
     * @throws RuntimeException When something goes wrong while encoding
     */
    public function jsonEncode($data)
    {
        $json = json_encode($data);
        
        if (json_last_error() != JSON_ERROR_NONE)
        {
            throw new RuntimeException('Error while encoding to JSON: ' . json_last_error_msg());
        }
        
        return $json;
    }
    
    /**
     * Encodes to base64url
     *
     * @param string $data
     *
     * @return string
     */
    public function base64UrlEncode($data)
    {
        return str_replace('=', '', strtr(base64_encode($data), '+/', '-_'));
    }
}
