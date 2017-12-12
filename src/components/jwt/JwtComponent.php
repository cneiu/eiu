<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\jwt;


use eiu\components\Component;
use eiu\components\jwt\Signer\Hmac\Sha256;
use eiu\core\service\router\RequestProvider;


/**
 * JWT 组件
 *
 * @package eiu\core\service\event
 */
class JwtComponent extends Component
{
    /**
     * 创建令牌
     *
     * @param   string $id
     * @param array    $data
     * @param int      $expiration
     *
     * @return Token
     */
    public function createToken(string $id, array $data = [], int $expiration = 3600, string $sign = null)
    {
        return (new Builder())
            ->setIssuer($_SERVER['SERVER_NAME'])
            ->setAudience($_SERVER['REMOTE_ADDR'])
            ->setId($id, true)
            ->setIssuedAt(time())
            ->setNotBefore(time())
            ->setExpiration(time() + $expiration)
            ->set('data', json_encode($data))
            ->sign(new Sha256(), $sign ?: $id)
            ->getToken();
    }
    
    /**
     * 验证令牌
     *
     * @param null $token
     *
     * @return bool
     */
    public function verifyToken(string $id, string $token = null, string $sign = null): bool
    {
        if (!$token = $this->_getToken($token))
        {
            return false;
        }
    
        try
        {
            $data  = $this->_createValidationData($id);
            $token = (new Parser())->parse($token);
        }
        catch (\Exception $e)
        {
            return false;
        }
        echo $token;
        var_dump($token->verify(new Sha256(), $sign ?: $id));
        var_dump($token->validate($data));
        
        return $token->verify(new Sha256(), $sign ?: $id) and $token->validate($data);
    }
    
    /**
     * 获取数据
     *
     * @param string      $id
     * @param string|null $token
     *
     * @return array|mixed
     */
    public function getData(string $id, string $token = null, string $sign = null)
    {
        if (!$this->verifyToken($id, $token, $sign))
        {
            return [];
        }
    
        try
        {
            $token = (new Parser())->parse($token);
        }
        catch (\Exception $e)
        {
            return false;
        }
        
        return json_decode($token->getClaim('data'), true);
    }
    
    /**
     * 获取有效令牌
     *
     * @param null $token
     *
     * @return mixed|null
     */
    private function _getToken(string $token = null)
    {
        // 从头部获取
        if (is_null($token))
        {
            if ($token = $this->app->make(RequestProvider::class)->header('Authorization'))
            {
                $token = str_replace('Bearer ', '', $token);
            }
        }
        
        return $token;
    }
    
    /**
     * 创建验证器
     *
     * @param $id
     *
     * @return ValidationData
     */
    private function _createValidationData(string $id)
    {
        $data = new ValidationData();
        $data->setIssuer($_SERVER['SERVER_NAME']);
        $data->setAudience($_SERVER['REMOTE_ADDR']);
        $data->setId($id);
        
        return $data;
    }
}