<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt;


use eiu\components\Component;
use Firebase\JWT\JWT;


/**
 * JWT
 *
 * @package eiu\core\service\event
 */
class JwtComponent extends Component
{
    
    /**
     * Converts and signs a PHP object or array into a JWT string.
     *
     * @param object|array $payload     PHP object or array
     * @param string       $key         The secret key.
     *                                  If the algorithm used is asymmetric, this is the private key
     * @param string       $alg         The signing algorithm.
     *                                  Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
     * @param mixed        $keyId
     * @param array        $head        An array with header elements to attach
     *
     * @return string A signed JWT
     *
     * @uses jsonEncode
     * @uses urlsafeB64Encode
     */
    public function encode($payload, $key, $alg = 'HS256', $keyId = null, $head = null)
    {
        return JWT::encode($payload, $key, $alg, $keyId, $head);
    }
    
    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string       $jwt             The JWT
     * @param string|array $key             The key, or map of keys.
     *                                      If the algorithm used is asymmetric, this is the public key
     * @param array        $allowed_algs    List of supported verification algorithms
     *                                      Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
     *
     * @return array
     *
     * @throws UnexpectedValueException     Provided JWT was invalid
     * @throws SignatureInvalidException    Provided JWT was invalid because the signature verification failed
     * @throws BeforeValidException         Provided JWT is trying to be used before it's eligible as defined by 'nbf'
     * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by 'iat'
     * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
     *
     * @uses jsonDecode
     * @uses urlsafeB64Decode
     */
    public function decode($jwt, $key, $allowed_algs = [])
    {
        return (array)JWT::decode($jwt, $key, $allowed_algs);
    }
    
    /**
     * set leeway in seconds
     *
     * @param $seconds
     */
    public function setLeeway($seconds)
    {
        JWT::$leeway = $seconds;
    }
}