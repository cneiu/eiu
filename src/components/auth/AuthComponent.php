<?php
/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */


namespace eiu\components\auth;


use eiu\components\Component;
use eiu\components\database\DatabaseComponent;
use eiu\components\jwt\JwtComponent;
use eiu\components\util\UtilComponent;
use eiu\core\application\Application;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\Logger;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\router\RequestProvider;
use eiu\core\service\router\RouterProvider;


/**
 * 认证授权
 *
 * @package eiu\core\service\event
 */
class AuthComponent extends Component
{
    /**
     * 登录豁免列表
     *
     * @var mixed
     */
    private $_LOGIN_EXEMPT;
    
    /**
     * 权限豁免列表
     *
     * @var mixed
     */
    private $_PERMISSION_EXEMPT;
    
    /**
     * @var Logger|LoggerProvider
     */
    private $logger;
    
    /**
     * @var RouterProvider
     */
    private $router;
    
    /**
     * @var RequestProvider
     */
    private $request;
    
    /**
     * @var JwtComponent
     */
    private $jwt;
    
    /**
     * @var SessionVerifier
     */
    private $session;
    
    /**
     * @var DatabaseComponent
     */
    private $db;
    
    /**
     * 当前登录用户标识
     *
     * @var string
     */
    private $currentLoginKey;
    
    /**
     * AuthComponent constructor.
     *
     * @param Application           $app
     * @param ConfigProvider        $config
     * @param LoggerProvider|Logger $logger
     * @param RouterProvider        $router
     * @param RequestProvider       $request
     * @param JwtVerifier           $jwt
     * @param SessionVerifier       $session
     * @param DatabaseComponent     $db
     *
     * @internal param SessionComponent $session
     */
    public function __construct(Application $app, ConfigProvider $config, LoggerProvider $logger,
                                RouterProvider $router, RequestProvider $request, JwtVerifier $jwt,
                                SessionVerifier $session, DatabaseComponent $db)
    {
        parent::__construct($app);
        
        $this->logger  = $logger;
        $this->router  = $router;
        $this->request = $request;
        $this->jwt     = $jwt;
        $this->session = $session;
        $this->db      = $db;
        
        $this->_LOGIN_EXEMPT      = $config->get('auth', 'LOGIN_EXEMPT');
        $this->_PERMISSION_EXEMPT = $config->get('auth', 'PERMISSION_EXEMPT');
        
        $app->instance(__CLASS__, $this);
        
        $logger->info(__CLASS__ . " is called");
    }
    
    /**
     * 基于配置的登录验证
     *
     * @param array $config
     *
     * @return bool
     */
    private function checkByConfig(array $config)
    {
        if (!is_array($config) or empty($config))
        {
            $this->logger->warning("The auth configuration list is empty.");
            
            return false;
        }
        
        $temp_path   = $this->request->router('pathInfo');
        $temp_config = [];
        
        foreach ($config as $v)
        {
            $temp_config[] = strtolower($v);
        }
        
        while (true)
        {
            if (!in_array($temp_path, $temp_config))
            {
                if (false === ($index = strripos($temp_path, '/')))
                {
                    return false;
                }
                
                $temp_path = substr($temp_path, 0, $index);
                continue;
            }
            
            return true;
        }
    }
    
    /**
     * 登录
     *
     * @param  string $key    身份标识
     * @param string  $way    加密方式
     * @param int     $exceed 有效时间(秒)
     *
     * @return bool
     */
    public function login(string $key, string $way = 'session', int $exceed = 0)
    {
        switch ($way)
        {
            case 'jwt':
                return $this->jwt->createToken($key, $exceed);
            
            case 'session':
                return $this->session->createToken($key, $exceed);
        }
    }
    
    /**
     * 获取当前登录用户标识
     *
     * @return string
     */
    public function getLoginKey()
    {
        return $this->currentLoginKey;
    }
    
    /**
     * 检查登录状态
     *
     * @return bool
     *
     */
    public function checkLogin()
    {
        // 全局免登陆
        if ($this->_LOGIN_EXEMPT === '*')
        {
            return true;
        }
        
        // 基于配置的登录豁免
        if (true === $this->checkByConfig($this->_LOGIN_EXEMPT))
        {
            return true;
        }
        
        // 尝试基于SESSION的认证
        if ($this->currentLoginKey = $this->session->getToken())
        {
            return true;
        }
        
        // 尝试基于JWT的认证
        if ($this->currentLoginKey = $this->jwt->getToken())
        {
            return true;
        }
        
        return false;
    }
    
    /**
     * 检查访问权限
     *
     * @return bool
     */
    public function checkPermissible()
    {
        // 全局免登陆
        if ($this->_PERMISSION_EXEMPT === '*')
        {
            return true;
        }
        
        // 无需登录即无需权限认证
        if (true === $this->checkByConfig($this->_LOGIN_EXEMPT))
        {
            return true;
        }
        
        if (true === $this->checkByConfig($this->_PERMISSION_EXEMPT))
        {
            return true;
        }
        
        if ($this->currentLoginKey)
        {
            if ($this->checkActionByUserId($this->currentLoginKey, $this->request->router('controller'), $this->request->router('method')))
            {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 通过角色获取权限
     *
     * @param $roleId
     *
     * @return mixed
     */
    public function getPermissionByRoleId($roleId)
    {
        if (empty($roleId))
            return false;
        
        $roleId = is_array($roleId) ? "'" . implode('\', \'', $roleId) . "'" : "'$roleId'";
        
        $sql = "
				SELECT
				`map_id`, `map_name`, `map_controller`, `map_action`
                FROM
                `sys_map`, `sys_role_map`
                WHERE
                `map_id`=`rm_map_id`
                AND
                `rm_role_id` IN ($roleId)";
        
        return $this->db->query($sql);
    }
    
    /**
     * get permission by user id
     *
     * @param $userId
     *
     * @return mixed
     */
    public function getPermissionByUserId($userId)
    {
        if (empty($userId))
            return false;
        
        $sql = "
			SELECT
			`map_id`, `map_name`, `map_controller`, `map_action`
            FROM
            `sys_map`, `sys_role_map`
            WHERE
            `map_id`=`rm_map_id`
            AND
            `rm_role_id` IN (SELECT `ur_role_id` FROM `sys_user_role` WHERE `ur_user_id`='$userId')
            ";
        
        return $this->db->query($sql);
    }
    
    /**
     * get role by user id
     *
     * @param $userId
     *
     * @return mixed
     */
    public function getRoleByUserId($userId)
    {
        if (empty($userId))
            return false;
        
        $sql = "
				SELECT
				`role_id`, `role_name`
                FROM
                `sys_role`, `sys_user_role`
                WHERE
                `role_id`=`ur_role_id`
                AND
                `ur_user_id`='$userId'";
        
        return $this->db->query($sql);
    }
    
    /**
     * check permission action by user id
     *
     * @param $userId
     * @param $controller
     * @param $action
     *
     * @return mixed
     */
    public function checkActionByUserId($userId, $controller, $action)
    {
        if (empty($userId) or empty($controller) or empty($action))
            return false;
        
        $sql = "
			SELECT
			`map_id`
            FROM
            `sys_map`, `sys_role_map`
            WHERE
            `map_id`=`rm_map_id`
            AND
            `rm_role_id` IN (SELECT `ur_role_id` FROM `sys_user_role` WHERE `ur_user_id`='$userId')
            AND
            `map_controller` = '$controller'
            AND
            `map_action`='$action'
            ";
        
        return !empty($this->db->query($sql));
    }
    
    /**
     * check permission controller by user id
     *
     * @param $userId
     * @param $controller
     *
     * @return mixed
     */
    public function checkControllerByUserId($userId, $controller)
    {
        if (empty($userId) or empty($controller))
            return false;
        
        $sql = "
			SELECT
			`map_id`
            FROM
            `sys_map`, `sys_role_map`
            WHERE
            `map_id`=`rm_map_id`
            AND
            `rm_role_id` IN (SELECT `ur_role_id` FROM `sys_user_role` WHERE `ur_user_id`='$userId')
            AND
            `map_controller` = '$controller'
            ";
        
        return !empty($this->db->query($sql));
    }
    
    /**
     * get permission by role
     *
     * @return mixed
     */
    public function getPermissionByRoles()
    {
        $sql = "
				SELECT
				`rm_role_id`, `rm_map_id`
                FROM
                `sys_role_map`
                ORDER BY `rm_role_id`
                ";
        
        return $this->db->query($sql);
    }
    
    /**
     * get role by user
     *
     * @return mixed
     */
    public function getRoleByUsers()
    {
        $sql = "
				SELECT
				`ur_user_id`, `ur_role_id`
                FROM
                `sys_user_role`
                ORDER BY `ur_user_id`
                ";
        
        return $this->db->query($sql);
    }
    
    /**
     * check role permission by their id
     *
     * @param $roleId
     * @param $permissionId
     *
     * @return bool
     */
    public function checkRolePermissionById($roleId, $permissionId)
    {
        if (empty($roleId) or empty($permissionId))
            return false;
        
        $sql = "
			SELECT
			`rm_id`
			FROM
			`sys_role_map`
			WHERE
			`rm_role_id`='$roleId'
			AND
			`rm_map_id`='$permissionId'
		";
        
        return !!$this->db->query($sql);
    }
    
    /**
     * check user role by their id
     *
     * @param $userId
     * @param $roleId
     *
     * @return bool
     */
    public function checkUserRoleById($userId, $roleId)
    {
        if (empty($userId) or empty($roleId))
            return false;
        
        $sql = "
			SELECT
			`ur_id`
			FROM
			`sys_user_role`
			WHERE
			`ur_user_id`='$userId'
			AND
			`ur_role_id`='$roleId'
		";
        
        return !!$this->db->query($sql);
    }
    
    /**
     * endow permission
     *
     * @param $roleId
     * @param $permissionId
     *
     * @return bool
     */
    public function endowPermission($roleId, $permissionId)
    {
        if ($this->checkRolePermissionById($roleId, $permissionId))
            return true;
        
        $uuid = UtilComponent::uuid();
        
        $sql = "
			INSERT INTO
			`sys_role_map`
			(`rm_id`, `rm_role_id`, `rm_map_id`)
			VALUES
			('$uuid', '$roleId', '$permissionId')";
        
        return !!$this->db->exec($sql);
    }
    
    /**
     * clean permission
     *
     * @param      $roleId
     * @param null $permissionId
     *
     * @return bool
     */
    public function cleanPermission($roleId, $permissionId = null)
    {
        if (empty($roleId))
            return false;
        
        if (!empty($permissionId))
            $permissionId = "AND `rm_map_id`='$permissionId'";
        
        $sql = "
			DELETE FROM
			`sys_role_map`
			WHERE
			`rm_role_id`='$roleId'
			$permissionId";
        
        if (false === $this->db->exec($sql))
            return false;
        
        return true;
    }
    
    /**
     * endow role
     *
     * @param $userId
     * @param $roleId
     *
     * @return bool
     */
    public function endowRole($userId, $roleId)
    {
        if (empty($userId) or empty($roleId))
            return false;
        
        if ($this->checkUserRoleById($userId, $roleId))
            return true;
        
        $sql = "
			INSERT INTO
			`sys_user_role`
			(`ur_user_id`, `ur_role_id`)
			VALUES
			($userId, $roleId)";
        
        return !!$this->db->exec($sql);
    }
    
    /**
     * unset role to user
     *
     * @param $userId
     * @param $roleId
     *
     * @return bool
     */
    public function cleanRole($userId, $roleId = null)
    {
        if (empty($userId))
            return false;
        
        if (!empty($roleId))
            $roleId = "AND `ur_role_id`='$roleId'";
        
        $sql = "
			DELETE FROM
			`sys_user_role`
			WHERE
			`ur_user_id`='$userId'
			$roleId";
        
        if (false === $this->db->exec($sql))
            return false;
        
        return true;
    }
}