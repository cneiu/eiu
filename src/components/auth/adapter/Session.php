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
use eiu\components\session\SessionComponent;
use eiu\core\application\Application;


/**
 * SESSION 适配器
 */
class Session extends Component implements IAuthAdapter
{
    /**
     * @var SessionComponent
     */
    private $session;
    
    /**
     * @var string
     */
    private $id;
    
    /**
     * Session constructor.
     *
     * @param Application      $app
     * @param SessionComponent $session
     */
    public function __construct(Application $app, SessionComponent $session)
    {
        parent::__construct($app);
        
        $this->session = $session;
        $this->id      = '__AUTH__';
    }
    
    /**
     * 创建令牌
     *
     * @param array $data
     * @param int   $expiration
     *
     * @return Token
     */
    public function createToken(array $data = [], int $expiration = 3600, $clientIp = null): string
    {
        return $this->session->setTimedValue($this->id, json_encode($data))->getId();
    }
    
    /**
     * 验证令牌
     *
     * @return bool
     */
    public function verifyToken(): bool
    {
        return isset($this->session[$this->id]);
    }
    
    /**
     * 删除令牌
     */
    public function clearToken()
    {
        unset($this->session[$this->id]);
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
        return $this->session[$this->id];
    }
}