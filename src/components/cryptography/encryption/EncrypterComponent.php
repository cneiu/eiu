<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\cryptography\encryption;


use eiu\components\Component;
use eiu\components\cryptography\encryption\keys\IKeyDeriver;
use eiu\components\cryptography\encryption\keys\Secret;
use eiu\core\application\Application;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;


/**
 * 哈希加密算法
 */
class EncrypterComponent extends Component
{
    
    /**
     * @var Encryption
     */
    private $encrypter = null;
    
    /**
     * @var Secret
     */
    private $secret = null;
    
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
     * 初始化加密器
     *
     * @param Secret           $secret    密钥
     * @param string           $cipher    算法
     * @param IKeyDeriver|null $keyDriver 加密驱动
     *
     * @return $this
     */
    public function init(Secret $secret, string $cipher = Ciphers::AES_256_CTR, IKeyDeriver $keyDriver = null)
    {
        if ($this->secret !== $secret)
        {
            $this->encrypter = new Encryption($secret, $cipher, $keyDriver);
            $this->secret    = $secret;
        }
        
        return $this;
    }
    
    /**
     * 解密数据
     *
     * @param string $data The data to decrypt
     *
     * @return string The decrypted data
     * @throws EncryptionException Thrown if there was an error decrypting the data
     */
    public function decrypt(string $data)
    {
        if (!$this->encrypter)
        {
            throw new EncryptionException('Encryption is uninitialized.');
        }
        
        return $this->encrypter->decrypt($data);
    }
    
    /**
     * 加密数据
     *
     * @param string $data The data to encrypt
     *
     * @return string The encrypted data
     * @throws EncryptionException Thrown if there was an error encrypting the data
     */
    public function encrypt(string $data)
    {
        if (!$this->encrypter)
        {
            throw new EncryptionException('Encryption is uninitialized.');
        }
        
        return $this->encrypter->encrypt($data);
    }
    
    /**
     * 设置密钥
     *
     * @param Secret $secret The secret to use
     *
     * @throws EncryptionException
     */
    public function setSecret(Secret $secret)
    {
        if (!$this->encrypter)
        {
            throw new EncryptionException('Encryption is uninitialized.');
        }
        $this->encrypter->setSecret($secret);
    }
}