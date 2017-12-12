<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\auth\adapter;


use eiu\components\auth\IAuthAdapter;
use eiu\components\Component;
use eiu\components\cryptography\RSAComponent;
use eiu\core\application\Application;
use eiu\core\service\router\RequestProvider;


/**
 * JWT 适配器
 *
 * @package eiu\components\cache\adapter
 */
class Jwt extends Component implements IAuthAdapter
{
    /**
     * @var \eiu\components\cryptography\RSAComponent
     */
    private $rsa;
    
    /**
     * @var string
     */
    private $id;
    
    /**
     * JwtComponent constructor.
     *
     * @param Application                               $app
     * @param \eiu\components\cryptography\RSAComponent $rsa
     */
    public function __construct(Application $app, RSAComponent $rsa)
    {
        parent::__construct($app);
        
        $this->rsa = $rsa;
        $this->id  = '__AUTH__';
    }
    
    /**
     * 创建令牌
     *
     * @param   string $id
     * @param array    $data
     * @param int      $expiration
     *
     * @return Token
     */
    public function createToken(array $data = [], int $expiration = 3600, $clientIp = null): string
    {
        $_data = [
            'id'   => $this->id,
            'data' => json_encode($data),
            'exp'  => time() + $expiration,
            'ip'   => $clientIp,
        ];
        
        
        return base64_encode($this->rsa->encode(json_encode($_data)));
    }
    
    /**
     * 验证令牌
     *
     * @return bool
     */
    public function verifyToken(): bool
    {
        if (!$token = $this->getToken())
        {
            return false;
        }
        
        try
        {
            $_data = json_decode($this->rsa->decode(base64_decode($token)), true);
        }
        catch (\Exception $e)
        {
            return false;
        }
        
        if (!isset($_data['id']) or $_data['id'] != $this->id)
        {
            return false;
        }
        
        if (!isset($_data['exp']) or $_data['exp'] < time())
        {
            return false;
        }
        
        if (isset($_data['ip']) and $ip = $_data['ip'])
        {
            if ($ip != $_SERVER['REMOTE_ADDR'])
            {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 删除令牌
     *
     * @param string $key
     */
    public function clearToken()
    {
        //
    }
    
    /**
     * 获取数据
     *
     * @param string      $id
     * @param string|null $token
     *
     * @return array|mixed
     */
    public function getData()
    {
        if (!$token = $this->getToken())
        {
            return false;
        }
        
        try
        {
            $_data = json_decode($this->rsa->decode(base64_decode($token)), true);
        }
        catch (\Exception $e)
        {
            return [];
        }
        
        return $_data['data'] ?? [];
    }
    
    /**
     * 从头部获取令牌
     *
     * @return mixed|null
     */
    public function getToken()
    {
        if ($token = $this->app->make(RequestProvider::class)->header('Authorization'))
        {
            return str_replace('Bearer ', '', $token);
        }
        else
        {
            return null;
        }
    }
}