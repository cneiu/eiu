<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cryptography\hashing;


use eiu\components\Component;
use eiu\core\application\Application;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;


/**
 * 哈希加密算法
 */
class BcryptHasherComponent extends Component
{
    
    /**
     * @var BcryptHasherComponent
     */
    private $bcryptHasher = null;
    
    /**
     * constructor.
     *
     * @param Application           $app
     * @param LoggerProvider|Logger $logger
     */
    public function __construct(Application $app, LoggerProvider $logger)
    {
        parent::__construct($app);
        
        $app->instance(__CLASS__, $this);
        
        $logger->info(__CLASS__ . " is called");
    }
    
    /**
     * 验证哈希值
     *
     * @param string $hashedValue   The hashed value to verify against
     * @param string $unhashedValue The unhashed value to verify
     * @param string $pepper        The optional pepper to append prior to verifying the value
     *
     * @return bool True if the unhashed value matches the hashed value
     */
    public function verify(string $hashedValue, string $unhashedValue, string $pepper = ''): bool
    {
        return BcryptHashing::verify($hashedValue, $unhashedValue, $pepper);
    }
    
    /**
     * 生成哈希值
     *
     * @param string $unhashedValue 需要加密的字符串
     * @param array  $options       具体算法选项
     * @param string $pepper        密钥
     *
     * @return string The hashed value
     *
     * @throws HashingException Thrown if the hashing failed
     */
    public function hash(string $unhashedValue, array $options = [], string $pepper = ''): string
    {
        if (!$this->bcryptHasher)
        {
            $this->init();
        }
        
        return $this->bcryptHasher->hash($unhashedValue, $options, $pepper);
    }
    
    /**
     * 初始化加密器
     */
    public function init()
    {
        $this->bcryptHasher = new BcryptHashing();
    }
    
    /**
     * 密码是否需要重新加密
     *
     * @param string $hashedValue 哈希值
     * @param array  $options     具体算法选项
     *
     * @return bool True if the hash needs to be rehashed, otherwise false
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        if (!$this->bcryptHasher)
        {
            $this->init();
        }
        
        return $this->bcryptHasher->needsRehash($hashedValue, $options);
    }
}