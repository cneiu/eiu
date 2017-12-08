<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt;


use DateTime;
use eiu\components\Component;


/**
 * JWT 组件
 *
 * @package eiu\core\service\event
 */
class JwtComponent extends Component
{
    
    /**
     * When checking nbf, iat or expiration times,
     * we want to provide some extra leeway time to
     * account for clock skew.
     */
    public static $leeway = 0;
    
    /**
     * Allow the current timestamp to be specified.
     * Useful for fixing a value within unit testing.
     *
     * Will default to PHP time() value if null.
     */
    public static $timestamp = null;
    
    public static $supported_algs = [
        'HS256' => ['hash_hmac', 'SHA256'],
        'HS512' => ['hash_hmac', 'SHA512'],
        'HS384' => ['hash_hmac', 'SHA384'],
        'RS256' => ['openssl', 'SHA256'],
    ];
    
    /**
     * 将PHP对象或数组转换为JWT字符串
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
        return static::_encode($payload, $key, $alg, $keyId, $head);
    }
    
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
    private static function _encode($payload, $key, $alg = 'HS256', $keyId = null, $head = null)
    {
        $header = ['typ' => 'JWT', 'alg' => $alg];
        if ($keyId !== null)
        {
            $header['kid'] = $keyId;
        }
        if (isset($head) and is_array($head))
        {
            $header = array_merge($head, $header);
        }
        $segments      = [];
        $segments[]    = static::urlsafeB64Encode(static::jsonEncode($header));
        $segments[]    = static::urlsafeB64Encode(static::jsonEncode($payload));
        $signing_input = implode('.', $segments);
        
        $signature  = static::sign($signing_input, $key, $alg);
        $segments[] = static::urlsafeB64Encode($signature);
        
        return implode('.', $segments);
    }
    
    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    private static function urlsafeB64Encode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }
    
    /**
     * Encode a PHP object into a JSON string.
     *
     * @param object|array $input A PHP object or array
     *
     * @return string JSON representation of the PHP object or array
     *
     * @throws DomainException Provided object could not be encoded to valid JSON
     */
    private static function jsonEncode($input)
    {
        $json = json_encode($input);
        if (function_exists('json_last_error') and $errno = json_last_error())
        {
            static::handleJsonError($errno);
        }
        else if ($json === 'null' and $input !== null)
        {
            throw new \Exception('Null result with non-null input');
        }
        
        return $json;
    }
    
    /**
     * Helper method to create a JSON error.
     *
     * @param int $errno An error number from json_last_error()
     *
     * @return void
     */
    private static function handleJsonError($errno)
    {
        $messages = [
            JSON_ERROR_DEPTH     => 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX    => 'Syntax error, malformed JSON',
        ];
        throw new \Exception(
            isset($messages[$errno])
                ? $messages[$errno]
                : 'Unknown JSON error: ' . $errno
        );
    }
    
    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string          $msg      The message to sign
     * @param string|resource $key      The secret key
     * @param string          $alg      The signing algorithm.
     *                                  Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
     *
     * @return string An encrypted message
     *
     * @throws DomainException Unsupported algorithm was specified
     */
    private static function sign($msg, $key, $alg = 'HS256')
    {
        if (empty(static::$supported_algs[$alg]))
        {
            throw new \Exception('Algorithm not supported');
        }
        list($function, $algorithm) = static::$supported_algs[$alg];
        switch ($function)
        {
            case 'hash_hmac':
                return hash_hmac($algorithm, $msg, $key, true);
            case 'openssl':
                $signature = '';
                $success   = openssl_sign($msg, $signature, $key, $algorithm);
                if (!$success)
                {
                    throw new \Exception("OpenSSL unable to sign data");
                }
                else
                {
                    return $signature;
                }
        }
    }
    
    /**
     * 将一个JWT字符串解码成一个PHP对象
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
     * @throws BeforeValidException         Provided JWT is trying to be used before it's been created as defined by
     *                                      'iat'
     * @throws ExpiredException             Provided JWT has since expired, as defined by the 'exp' claim
     *
     * @uses jsonDecode
     * @uses urlsafeB64Decode
     */
    public function decode($jwt, $key, $allowed_algs = [])
    {
        return (array)static::_decode($jwt, $key, $allowed_algs);
    }
    
    /**
     * Decodes a JWT string into a PHP object.
     *
     * @param string       $jwt             The JWT
     * @param string|array $key             The key, or map of keys.
     * @param array        $allowed_algs    List of supported verification algorithms
     *                                      Supported algorithms are 'HS256', 'HS384', 'HS512' and 'RS256'
     *
     * @return object The JWT's payload as a PHP object
     *
     * @throws \Exception
     * @uses jsonDecode
     * @uses urlsafeB64Decode
     */
    private static function _decode($jwt, $key, $allowed_algs = [])
    {
        $timestamp = is_null(static::$timestamp) ? time() : static::$timestamp;
        
        if (empty($key))
        {
            throw new \Exception('Key may not be empty');
        }
        if (!is_array($allowed_algs))
        {
            throw new \Exception('Algorithm not allowed');
        }
        $tks = explode('.', $jwt);
        if (count($tks) != 3)
        {
            throw new \Exception('Wrong number of segments');
        }
        list($headb64, $bodyb64, $cryptob64) = $tks;
        if (null === ($header = static::jsonDecode(static::urlsafeB64Decode($headb64))))
        {
            throw new \Exception('Invalid header encoding');
        }
        if (null === $payload = static::jsonDecode(static::urlsafeB64Decode($bodyb64)))
        {
            throw new \Exception('Invalid claims encoding');
        }
        $sig = static::urlsafeB64Decode($cryptob64);
        
        if (empty($header->alg))
        {
            throw new \Exception('Empty algorithm');
        }
        if (empty(static::$supported_algs[$header->alg]))
        {
            throw new \Exception('Algorithm not supported');
        }
        if (!in_array($header->alg, $allowed_algs))
        {
            throw new \Exception('Algorithm not allowed');
        }
        if (is_array($key) or $key instanceof \ArrayAccess)
        {
            if (isset($header->kid))
            {
                $key = $key[$header->kid];
            }
            else
            {
                throw new \Exception('"kid" empty, unable to lookup correct key');
            }
        }
        
        // Check the signature
        if (!static::verify("$headb64.$bodyb64", $sig, $key, $header->alg))
        {
            throw new \Exception('Signature verification failed');
        }
        
        // Check if the nbf if it is defined. This is the time that the
        // token can actually be used. If it's not yet that time, abort.
        if (isset($payload->nbf) and $payload->nbf > ($timestamp + static::$leeway))
        {
            throw new \Exception(
                'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->nbf)
            );
        }
        
        // Check that this token has been created before 'now'. This prevents
        // using tokens that have been created for later use (and haven't
        // correctly used the nbf claim).
        if (isset($payload->iat) and $payload->iat > ($timestamp + static::$leeway))
        {
            throw new \Exception(
                'Cannot handle token prior to ' . date(DateTime::ISO8601, $payload->iat)
            );
        }
        
        // Check if this token has expired.
        if (isset($payload->exp) and ($timestamp - static::$leeway) >= $payload->exp)
        {
            throw new \Exception('Expired token');
        }
        
        return $payload;
    }
    
    /**
     * Decode a JSON string into a PHP object.
     *
     * @param string $input JSON string
     *
     * @return object Object representation of JSON string
     *
     * @throws DomainException Provided string was invalid JSON
     */
    private static function jsonDecode($input)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '>=') and !(defined('JSON_C_VERSION') and PHP_INT_SIZE > 4))
        {
            /** In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
             * to specify that large ints (like Steam Transaction IDs) should be treated as
             * strings, rather than the PHP default behaviour of converting them to floats.
             */
            $obj = json_decode($input, false, 512, JSON_BIGINT_AS_STRING);
        }
        else
        {
            /** Not all servers will support that, however, so for older versions we must
             * manually detect large ints in the JSON string and quote them (thus converting
             *them to strings) before decoding, hence the preg_replace() call.
             */
            $max_int_length       = strlen((string)PHP_INT_MAX) - 1;
            $json_without_bigints = preg_replace('/:\s*(-?\d{' . $max_int_length . ',})/', ': "$1"', $input);
            $obj                  = json_decode($json_without_bigints);
        }
        
        if (function_exists('json_last_error') and $errno = json_last_error())
        {
            static::handleJsonError($errno);
        }
        else if ($obj === null and $input !== 'null')
        {
            throw new \Exception('Null result with non-null input');
        }
        
        return $obj;
    }
    
    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    private static function urlsafeB64Decode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder)
        {
            $padlen = 4 - $remainder;
            $input  .= str_repeat('=', $padlen);
        }
        
        return base64_decode(strtr($input, '-_', '+/'));
    }
    
    /**
     * Verify a signature with the message, key and method. Not all methods
     * are symmetric, so we must have a separate verify and sign method.
     *
     * @param string          $msg       The original message (header and body)
     * @param string          $signature The original signature
     * @param string|resource $key       For HS*, a string key works. for RS*, must be a resource of an openssl public
     *                                   key
     * @param string          $alg       The algorithm
     *
     * @return bool
     *
     * @throws DomainException Invalid Algorithm or OpenSSL failure
     */
    private static function verify($msg, $signature, $key, $alg)
    {
        if (empty(static::$supported_algs[$alg]))
        {
            throw new \Exception('Algorithm not supported');
        }
        
        list($function, $algorithm) = static::$supported_algs[$alg];
        switch ($function)
        {
            case 'openssl':
                $success = openssl_verify($msg, $signature, $key, $algorithm);
                if (!$success)
                {
                    throw new \Exception("OpenSSL unable to verify data: " . openssl_error_string());
                }
                else
                {
                    return $signature;
                }
            case 'hash_hmac':
            default:
                $hash = hash_hmac($algorithm, $msg, $key, true);
                if (function_exists('hash_equals'))
                {
                    return hash_equals($signature, $hash);
                }
                $len = min(static::safeStrlen($signature), static::safeStrlen($hash));
                
                $status = 0;
                for ($i = 0; $i < $len; $i++)
                {
                    $status |= (ord($signature[$i]) ^ ord($hash[$i]));
                }
                $status |= (static::safeStrlen($signature) ^ static::safeStrlen($hash));
                
                return ($status === 0);
        }
    }
    
    /**
     * Get the number of bytes in cryptographic strings.
     *
     * @param string
     *
     * @return int
     */
    private static function safeStrlen($str)
    {
        if (function_exists('mb_strlen'))
        {
            return mb_strlen($str, '8bit');
        }
        
        return strlen($str);
    }
    
    /**
     * 设置有效时间（秒）
     *
     * @param $seconds
     */
    public function setLeeway($seconds)
    {
        static::$leeway = $seconds;
    }
}