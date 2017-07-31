<?php
defined('APP_ENTRY') or exit('Access denied');

/**
 * EIU PHP FRAMEWORK
 *
 * @author        成都东联智胜软件有限公司
 * @link          https://www.cneiu.com
 */

/*
 * --------------------------------------------------------------------
 * 同步配置表
 * --------------------------------------------------------------------
 */

return [
    
    // 表
    'TABLES' => [
        
        /*     表名                       服务名             */
        
        // 系统类
        'sys_user'                    => 'empty',               // 用户
        'org_person'                  => 'empty',             // 人员
        'dict_type'                   => 'empty',              // 数据字典类别
        'dict'                        => 'empty',                   // 数据字典
        
        // 任务类
        'tool_type'                   => 'empty',               // 工器具分类
        'tool'                        => 'empty',               // 工器具
        'spare_type'                  => 'empty',               // 备品备件分类
        'spare'                       => 'empty',               // 备品备件

        'task_tool'                   => 'empty',               // 任务 - 工器具
        'task_spare'                  => 'empty',               // 任务 - 备品备件
        'task_type'                   => 'empty',               // 任务类型
        'task_team'                   => 'empty',               // 任务成员
        'task_car'                    => 'empty',               // 任务用车
        'task_temp_worker'            => 'empty',               // 临时用工
        'task'                        => 'empty',               // 任务
        'task_content'                => 'empty',               // 任务工单内容
        'task_content_devices'        => 'empty',               // 任务工单内容设备
        'task_content_upload_devices' => 'empty',               // 任务工单内容上传设备
        
        // 资产类
        'as_devices'                  => 'empty',                // 设备
        'as_line'                     => 'empty',                // 线路
        'device_img'                  => 'empty',                // 设备图片
    ],

];