<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\auth;


use eiu\components\session\SessionComponent;
use eiu\core\application\Application;


class SessionVerifier extends Verifier implements IVerifier
{
    /**
     * @var SessionComponent
     */
    private $session;
    
    /**
     * constructor
     *
     * @param Application      $app
     * @param SessionComponent $session
     */
    public function __construct(Application $app, SessionComponent $session)
    {
        parent::__construct($app);
        
        $this->session = $session;
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
        if (!$exceed)
        {
            $this->session->setTimedValue('__LOGIN_KEY__', $key);
        }
        else
        {
            $this->session['__LOGIN_KEY__'] = $key;
        }
    }
    
    /**
     * 获取令牌
     *
     * @return string
     */
    public function getToken()
    {
        return $this->session['__LOGIN_KEY__'];
    }
}