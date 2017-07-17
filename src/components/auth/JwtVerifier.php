<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\auth;


use eiu\components\jwt\JwtComponent;
use eiu\core\application\Application;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\router\RequestProvider;


class JwtVerifier extends Verifier implements IVerifier
{
    /**
     * @var JwtComponent
     */
    private $jwt;
    
    /**
     * @var string
     */
    private $_JWT_KEY;
    
    /**
     * @var RequestProvider
     */
    private $request;
    
    /**
     * constructor
     *
     * @param Application     $app
     * @param JwtComponent    $jwt
     * @param ConfigProvider  $config
     * @param RequestProvider $request
     */
    public function __construct(Application $app, JwtComponent $jwt, ConfigProvider $config, RequestProvider $request)
    {
        parent::__construct($app);
        
        $this->jwt      = $jwt;
        $this->request = $request;
        $this->_JWT_KEY = $config->get('auth', 'JWT_KEY');
    }
    
    /**
     * 创建令牌
     *
     * @param string $key
     * @param int    $exceed
     *
     * @return string
     */
    public function createToken(string $key, int $exceed = 0)
    {
        $data = ['__LOGIN_KEY__' => $key];
        
        return $this->jwt->encode($data, $this->_JWT_KEY);
    }
    
    /**
     * 获取令牌
     *
     * @return string
     */
    public function getToken()
    {
        if ($token = $this->request->header('Authorization'))
        {
            if ($token = str_replace('Bearer ', '', $token))
            {
                $data = $this->jwt->decode($token, $this->_JWT_KEY, ['HS256']);
    
                return $data['__LOGIN_KEY__'] ?? null;
            }
        }
        
        return null;
    }
}