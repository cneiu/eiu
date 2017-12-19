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
use eiu\core\application\Application;
use eiu\core\service\config\ConfigProvider;
use eiu\core\service\logger\LoggerProvider;
use eiu\core\service\router\RequestProvider;
use eiu\core\service\view\ViewProvider;
use Exception;


/**
 * 认证组件
 *
 * @package eiu\components\auth
 */
class AuthComponent extends Component
{
    /**
     * @var LoggerProvider
     */
    private $logger;
    
    /**
     * @var ViewProvider
     */
    private $view;
    
    /**
     * @var string
     */
    private $url;
    
    /**
     * @var string
     */
    private $controller;
    
    /**
     * @var string
     */
    private $action;
    
    /**
     * @var array
     */
    private $config;
    
    /**
     * @var IAuthAdapter
     */
    private $adapter;
    
    /**
     * SessionComponent constructor.
     *
     * @param Application     $app
     * @param RequestProvider $request
     * @param ViewProvider    $view
     * @param ConfigProvider  $config
     * @param LoggerProvider  $logger
     * @param IAuthAdapter    $adapter
     *
     * @throws Exception
     */
    public function __construct(Application $app, RequestProvider $request, ViewProvider $view, ConfigProvider $config, LoggerProvider $logger, IAuthAdapter $adapter)
    {
        parent::__construct($app);
        
        $this->url        = $request->router('pathInfo');
        $this->controller = $request->router('controller');
        $this->action     = $request->router('method');
        $this->view       = $view;
        $this->logger     = $logger;
        $this->config     = $config['auth'];
        $this->adapter    = $adapter;
        
        if (!isset($config['auth']['KEY']) or !$config['auth']['KEY'])
        {
            throw new Exception("Undefined auth key");
        }
        
        $app->instance(__CLASS__, $this);
        
        $logger->info(__CLASS__ . " is called");
    }
    
    /**
     * 设置认证状态为已登录
     *
     * @param string $userId
     * @param array  $data
     *
     * @return string
     */
    public function setLogined(string $userId, array $data = [])
    {
        $data['__USER_ID__'] = $userId;
        
        return $this->adapter->create($data, $this->config['LIFETIME']);
    }
    
    /**
     * 判断当前用户是否登录
     *
     * @return bool
     */
    public function isLogined()
    {
        return $this->adapter->verify();
    }
    
    /**
     * 刷新当前登录状态
     *
     * @return string
     */
    public function refreshLogined()
    {
        return $this->adapter->refresh();
    }
    
    /**
     * 退出登录
     */
    public function logout()
    {
        $this->adapter->clear();
    }
    
    /**
     * 检查当前访问是否已登录
     *
     * @return bool
     */
    public function checkLogin()
    {
        // 全局免登陆
        if ($this->config['LOGIN_EXEMPT'] === '*')
        {
            return true;
        }
        
        // 基于配置的登录豁免
        if (true === $this->checkByConfig($this->config['LOGIN_EXEMPT']))
        {
            return true;
        }
        
        return $this->adapter->verify();
    }
    
    /**
     * 检查当前访问是否具备权限
     *
     * @return bool
     */
    public function checkPermissible()
    {
        // 全局免登陆
        if ($this->config['PERMISSION_EXEMPT'] === '*')
        {
            return true;
        }
        
        // 无需登录即无需权限认证
        if (true === $this->checkByConfig($this->config['LOGIN_EXEMPT']))
        {
            return true;
        }
        
        if (true === $this->checkByConfig($this->config['PERMISSION_EXEMPT']))
        {
            return true;
        }
        
        return $this->checkAction();
    }
    
    /**
     * 基于配置的访问验证
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
        
        $temp_path   = $this->url;
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
        
        return false;
    }
    
    /**
     * 获取登录缓存数据
     *
     * @return array
     */
    public function data()
    {
        return $this->adapter->data();
    }
    
    /**
     * 获取当前登录用户ID
     *
     * @return mixed|null
     */
    public function getUserId()
    {
        return ($data = $this->data() and isset($data['__USER_ID__']) and $data['__USER_ID__']) ? $data['__USER_ID__'] : null;
    }
    
    /**
     * 检查控制器动作权限
     *
     * @param string|null $controller
     * @param string|null $action
     * @param string|null $userId
     *
     * @return bool
     */
    public function checkAction(string $controller = null, string $action = null, string $userId = null)
    {
        /** @var RequestProvider $request */
        $request    = $this->app->make(RequestProvider::class);
        $controller = $controller ?: $request->router('controller');
        $action     = $action ?: $request->router('method');
        $userId     = $userId ?: $this->getUserId();
        $sql        = "SELECT `sys_permissions`.`perm_id` FROM `sys_permissions`, `sys_role_perm` WHERE `sys_permissions`.`perm_id`=`sys_role_perm`.`rp_perm_id`";
        $sql        .= " AND `sys_role_perm`.`rp_role_id` IN (SELECT `sys_user_role`.`ur_role_id` FROM `sys_user_role` WHERE `sys_user_role`.`ur_user_id`='$userId')";
        $sql        .= " AND `sys_permissions`.`perm_controller` = '$controller' AND `sys_permissions`.`perm_action`='$action'";
        
        return !empty($this->app->make(DatabaseComponent::class)->query($sql));
    }
    
    /**
     * 检查控制器权限
     *
     * @param string|null $controller
     * @param string|null $userId
     *
     * @return bool
     */
    public function checkController(string $controller = null, string $userId = null)
    {
        /** @var RequestProvider $request */
        $request    = $this->app->make(RequestProvider::class);
        $controller = $controller ?: $request->router('controller');
        $userId     = $userId ?: $this->getUserId();
        $sql        = "SELECT `sys_permissions`.`perm_id` FROM `sys_permissions`, `sys_role_perm` WHERE `sys_permissions`.`perm_id`=`sys_role_perm`.`rp_perm_id`";
        $sql        .= " AND `sys_role_perm`.`rp_role_id` IN (SELECT `sys_user_role`.`ur_role_id` FROM `sys_user_role` WHERE `sys_user_role`.`ur_user_id`='$userId')";
        $sql        .= " AND `sys_permissions`.`perm_controller` = '$controller'";
        
        return !empty($this->app->make(DatabaseComponent::class)->query($sql));
    }
    
    /**
     * 获取权限列表
     *
     * @param string|null $userId
     *
     * @return bool
     */
    public function getPermissionsByUserId(string $userId = null)
    {
        $userId = $userId ?: $this->getUserId();
        $sql    = "SELECT * FROM `sys_permissions`, `sys_role_perm` ";
        $sql    .= "WHERE `sys_permissions`.`perm_id`=`sys_role_perm`.`rp_perm_id` AND `sys_role_perm`.`rp_role_id` IN";
        $sql    .= " (SELECT `sys_user_role`.`ur_role_id` FROM `sys_user_role` WHERE `sys_user_role`.`ur_user_id`='$userId')";
        
        return $this->app->make(DatabaseComponent::class)->query($sql);
    }
    
    /**
     * 获取权限ID列表
     *
     * @param string $roleId
     *
     * @return array
     */
    public function getPermissionsIdByRoleId(string $roleId)
    {
        $sql    = "SELECT `sys_role_perm`.`rp_perm_id` FROM `sys_role_perm` WHERE `sys_role_perm`.`rp_role_id`='$roleId'";
        $rows   = $this->app->make(DatabaseComponent::class)->query($sql);
        $result = [];
        
        if ($rows)
        {
            foreach ($rows as $row)
            {
                $result[] = $row['rp_perm_id'];
            }
        }
        
        return $result;
    }
    
    /**
     * 获取角色列表
     *
     * @param $userId
     *
     * @return mixed
     */
    public function getRolesByUserId(string $userId = null)
    {
        $userId = $userId ?: $this->getUserId();
        $sql    = "SELECT * FROM `sys_roles`, `sys_user_role` ";
        $sql    .= "WHERE `sys_roles`.`role_id`=`sys_user_role`.`ur_role_id` AND `sys_user_role`.`ur_user_id`='$userId'";
        
        return $this->app->make(DatabaseComponent::class)->query($sql);
    }
    
    /**
     * 获取角色权限列表
     *
     * @return mixed
     */
    public function getRolesPermissions()
    {
        $sql = "SELECT `sys_role_perm`.`rp_role_id`, `sys_role_perm`.`rp_perm_id` FROM `sys_role_perm` ORDER BY `sys_role_perm`.`rp_role_id`";
        
        return $this->app->make(DatabaseComponent::class)->query($sql);
    }
    
    /**
     * 获取用户角色列表
     *
     * @return mixed
     */
    public function getUsersRoles()
    {
        $sql = "SELECT `sys_user_role`.`ur_user_id`, `sys_user_role`.`ur_role_id` FROM `sys_user_role` ORDER BY `sys_user_role`.`ur_user_id`";
        
        return $this->app->make(DatabaseComponent::class)->query($sql);
    }
    
    /**
     * 为角色附加权限
     *
     * @param string $roleId
     * @param string $permissionId
     *
     * @return bool
     */
    public function attachPermission(string $roleId, string $permissionId)
    {
        if ($this->checkRoleByPermissionId($roleId, $permissionId))
        {
            return true;
        }
        
        $sql = "INSERT INTO `sys_role_perm` (`sys_role_perm`.`rp_role_id`, `sys_role_perm`.`rp_perm_id`) VALUES ('$roleId', '$permissionId')";
        
        return !!$this->app->make(DatabaseComponent::class)->exec($sql);
    }
    
    /**
     * 移除角色附加的权限
     *
     * @param string      $roleId
     * @param string|null $permissionId
     *
     * @return bool
     */
    public function unsetPermission(string $roleId, string $permissionId = null)
    {
        if (!$roleId)
        {
            return false;
        }
        
        if ($permissionId)
        {
            $permissionId = "AND `sys_role_perm`.`rp_perm_id`='$permissionId'";
        }
        
        $sql = "DELETE FROM `sys_role_perm` WHERE `sys_role_perm`.`rp_role_id`='$roleId' $permissionId";
        
        return !!$this->app->make(DatabaseComponent::class)->exec($sql);
    }
    
    /**
     * 检查角色
     *
     * @param $roleId
     * @param $permissionId
     *
     * @return bool
     */
    public function checkRoleByPermissionId(string $roleId = null, string $permissionId = null)
    {
        if (!$roleId or !$permissionId)
        {
            return false;
        }
        
        $sql = "SELECT `sys_role_perm`.`rp_id` FROM `sys_role_perm` WHERE `sys_role_perm`.`rp_role_id`='$roleId' AND `sys_role_perm`.`rp_perm_id`='$permissionId'";
        
        return !empty($this->app->make(DatabaseComponent::class)->query($sql));
    }
    
    /**
     * 为用户附加角色
     *
     * @param string $userId
     * @param string $roleId
     *
     * @return bool
     */
    public function attachRole(string $userId, string $roleId)
    {
        if (!$userId or !$roleId)
        {
            return false;
        }
        
        if ($this->checkUserByRoleId($userId, $roleId))
        {
            return true;
        }
        
        $sql = "INSERT INTO `sys_user_role` (`sys_user_role`.`ur_user_id`, `sys_user_role`.`ur_role_id`)VALUES('$userId', '$roleId')";
        
        return !!$this->app->make(DatabaseComponent::class)->exec($sql);
    }
    
    /**
     * 移除用户附加的角色
     *
     * @param string      $userId
     * @param string|null $roleId
     *
     * @return bool
     */
    public function unsetRole(string $userId, string $roleId = null)
    {
        if (!$userId)
        {
            return false;
        }
        
        if ($roleId)
        {
            $roleId = "AND `sys_user_role`.`ur_role_id`='$roleId'";
        }
        
        $sql = "DELETE FROM `sys_user_role` WHERE `sys_user_role`.`ur_user_id`='$userId' $roleId";
        
        return !!$this->app->make(DatabaseComponent::class)->exec($sql);
    }
    
    /**
     * 检查用户
     *
     * @param string $userId
     * @param string $roleId
     *
     * @return bool
     */
    public function checkUserByRoleId(string $userId, string $roleId)
    {
        if (!$userId or !$roleId)
        {
            return false;
        }
        
        $sql = "SELECT `sys_user_role`.`ur_id` FROM	`sys_user_role` WHERE `sys_user_role`.`ur_user_id`='$userId' AND `sys_user_role`.`ur_role_id`='$roleId'";
        
        return !empty($this->app->make(DatabaseComponent::class)->query($sql));
    }
    
    /**
     * 查询权限
     *
     * @param array $search
     * @param array $sort
     *
     * @return mixed
     */
    public function selectPermissions(array $search = [], array $sort = [])
    {
        $where = [];
        
        foreach ($search as $field => $value)
        {
            if (in_array($field, ['perm_name', 'perm_controller', 'perm_action']))
            {
                $where[] = "`sys_permissions`.`{$field}` LIKE '%{$value}%'";
            }
            if ($field == 'perm_id')
            {
                $where[] = "`sys_permissions`.`{$field}`='$value'";
            }
        }
        
        if ($where)
        {
            $where = 'WHERE ' . implode(' AND ', $where);
        }
        
        $order = [];
        
        foreach ($sort as $field => $dir)
        {
            if (in_array($field, ['perm_id', 'perm_name', 'perm_controller', 'perm_action']))
            {
                $order[] = "`sys_permissions`.`{$field}` " . $dir == 'DESC' ? 'DESC' : 'ASC';
            }
        }
        
        if ($order)
        {
            $order = 'ORDER BY ' . implode(', ', $order);
        }
        
        $where = $where ?: '';
        $order = $order ?: '';
        
        $sql = "SELECT * FROM `sys_permissions` {$where} {$order}";
        
        return $this->app->make(DatabaseComponent::class)->query($sql);
    }
    
    /**
     * 查找权限
     *
     * @param int $id
     *
     * @return bool
     */
    public function getPermissionById(int $id)
    {
        $sql = "SELECT * FROM `sys_permissions` WHERE `sys_permissions`.`perm_id`='{$id}'";
        
        if ($row = $this->app->make(DatabaseComponent::class)->query($sql))
        {
            return $row[0];
        }
        else
        {
            return null;
        }
    }
    
    /**
     * 插入权限
     *
     * @param string $name
     * @param string $controller
     * @param string $action
     *
     * @return bool
     */
    public function insertPermissions(string $name, string $controller, string $action)
    {
        /** @var DatabaseComponent $db */
        $db = $this->app->make(DatabaseComponent::class);
        
        $sql = "SELECT `sys_permissions`.`perm_id` FROM `sys_permissions` WHERE `sys_permissions`.`perm_name`='{$name}'";
        $sql .= " AND `sys_permissions`.`perm_controller`='{$controller}' AND `sys_permissions`.`perm_action`='{$action}'";
        
        if ($db->query($sql))
        {
            return false;
        }
        
        $sql = "INSERT INTO `sys_permissions` (`sys_permissions`.`perm_name`, `sys_permissions`.`perm_controller`, `sys_permissions`.`perm_action`)VALUES('{$name}', '{$controller}', '{$action}')";
        
        return $db->exec($sql);
    }
    
    /**
     * 更新权限
     *
     * @param int    $id
     * @param string $name
     * @param string $controller
     * @param string $action
     *
     * @return bool
     */
    public function updatePermissions(int $id, string $name, string $controller, string $action)
    {
        $sql = "UPDATE `sys_permissions` SET `sys_permissions`.`perm_name`='{$name}', `sys_permissions`.`perm_controller`='{$controller}', ";
        $sql .= "`sys_permissions`.`perm_action`='{$action}' WHERE `sys_permissions`.`perm_id`='{$id}'";
        
        return !!$this->app->make(DatabaseComponent::class)->exec($sql);
    }
    
    /**
     * 删除权限
     *
     * @param int $id
     *
     * @return bool
     */
    public function deletePermissions(int $id)
    {
        $sql = "DELETE FROM `sys_permissions` WHERE `sys_permissions`.`perm_id`='{$id}'";
        
        return !!$this->app->make(DatabaseComponent::class)->exec($sql);
    }
    
    /**
     * 查询角色
     *
     * @param array $search
     * @param array $sort
     *
     * @return mixed
     */
    public function selectRoles(array $search = [], array $sort = [])
    {
        $where = [];
        
        foreach ($search as $field => $value)
        {
            if ($field == 'role_name')
            {
                $where[] = "`sys_roles`.`{$field}` LIKE '%{$value}%'";
            }
            if (in_array($field, ['role_name', 'role_enabled']))
            {
                $where[] = "`sys_roles`.`{$field}`='$value'";
            }
        }
        
        if ($where)
        {
            $where = 'WHERE ' . implode(' AND ', $where);
        }
        
        $order = [];
        
        foreach ($sort as $field => $dir)
        {
            if (in_array($field, ['role_id', 'role_name', 'role_enabled']))
            {
                $order[] = "`sys_roles`.`{$field}` " . $dir == 'DESC' ? 'DESC' : 'ASC';
            }
        }
        
        if ($order)
        {
            $order = 'ORDER BY ' . implode(', ', $order);
        }
        
        $where = $where ?: '';
        $order = $order ?: '';
        
        $sql = "SELECT * FROM `sys_roles` {$where} {$order}";
        
        return $this->app->make(DatabaseComponent::class)->query($sql);
    }
    
    /**
     * 查找角色
     *
     * @param int $id
     *
     * @return bool
     */
    public function getRoleById(int $id)
    {
        $sql = "SELECT * FROM `sys_roles` WHERE `sys_roles`.`role_id`='{$id}'";
        
        if ($row = $this->app->make(DatabaseComponent::class)->query($sql))
        {
            return $row[0];
        }
        else
        {
            return null;
        }
    }
    
    /**
     * 插入角色
     *
     * @param string $name
     * @param bool   $enabled
     *
     * @return bool
     */
    public function insertRoles(string $name, bool $enabled = false)
    {
        /** @var DatabaseComponent $db */
        $db      = $this->app->make(DatabaseComponent::class);
        $sql     = "SELECT `sys_roles`.`role_id` FROM `sys_roles` WHERE `sys_roles`.`role_name`='{$name}'";
        $enabled = (int)$enabled;
        
        if ($db->query($sql))
        {
            return false;
        }
        
        $sql = "INSERT INTO `sys_roles` (`sys_roles`.`role_name`, `sys_roles`.`role_enabled`)VALUES('{$name}', {$enabled})";
        
        return $db->exec($sql);
    }
    
    /**
     * 更新角色
     *
     * @param int    $id
     * @param string $name
     * @param bool   $enabled
     *
     * @return bool
     */
    public function updateRoles(int $id, string $name, bool $enabled)
    {
        $enabled = (int)$enabled;
        $sql     = "UPDATE `sys_roles` SET `sys_roles`.`role_name`='{$name}', `sys_roles`.`role_enabled`={$enabled} WHERE `sys_roles`.`role_id`='{$id}'";
        
        return !!$this->app->make(DatabaseComponent::class)->exec($sql);
    }
    
    /**
     * 删除权限
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteRoles(int $id)
    {
        $sql = "DELETE FROM `sys_roles` WHERE `sys_roles`.`role_id`='{$id}'";
        
        return !!$this->app->make(DatabaseComponent::class)->exec($sql);
    }
}