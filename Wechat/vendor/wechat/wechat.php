<?php 
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 小虎哥 <1105415366@qq.com>
 * Date: 2018-4-3
 */

// 定义公共常量
define('WECHAT_ROOT', __DIR__ . '/');
define('WECHAT_CONFIG', WECHAT_ROOT . 'config/');
define('WECHAT_PHP', WECHAT_ROOT . 'php/');
define('WECHAT_FONT', WECHAT_ROOT . 'font/');
define('FONT_PATH', ROOT_PATH . 'public/static/common/font/');
if(version_compare(PHP_VERSION,'5.5.0','<')) {
    !defined('CURLOPT_SAFE_UPLOAD') && define('CURLOPT_SAFE_UPLOAD', true);
}

//自动载入类
spl_autoload_register(function ($class_name) {
    $file = WECHAT_PHP . $class_name . '.class.php';
    if (is_file($file)) { 
        require_once $file;
    }
});