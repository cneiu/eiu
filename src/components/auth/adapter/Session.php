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
     * @param null  $clientIp
     *
     * @return string
     */
    public function create(array $data = [], int $expiration = 3600, $clientIp = null): string
    {
        $data['__exp__']       = $expiration;
        $data['__client_ip__'] = $clientIp;
        
        return $this->session->setTimedValue($this->id, json_encode($data), $expiration)->getId();
    }
    
    /**
     * 验证令牌
     *
     * @return bool
     */
    public function verify(): bool
    {
        return isset($this->session[$this->id]);
    }
    
    /**
     * 删除令牌
     */
    public function clear()
    {
        unset($this->session[$this->id]);
    }
    
    /**
     * 获取数据
     *
     * @return array|mixed
     */
    public function data()
    {
        $data = $this->session[$this->id];
        
        unset($data['__exp__']);
        unset($data['__client_ip__']);
        
        return $data;
    }
    
    /**
     * 刷新令牌
     *
     * @return mixed
     */
    public function refresh()
    {
        if (!$this->verify())
        {
            return null;
        }
        
        $data = json_decode($this->session[$this->id], true);
        $exp  = $data['__exp__'];
        $ip   = $data['__client_ip__'];
        
        unset($data['__exp__']);
        unset($data['__client_ip__']);
        
        return $this->create($data, $exp, $ip);
    }
}