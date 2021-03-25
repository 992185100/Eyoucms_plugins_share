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
 * Date: 2018-06-28
 */

namespace weapp\Wechat\controller;

use think\Db;
use think\Page;
use app\common\controller\Weapp;
use weapp\Wechat\logic\WechatLogic;

/**
 * Wechat插件的基类控制器
 */
class Base extends Weapp{

    /**
     * 实例化模型
     */
    public $model;

    /**
     * 插件基本信息
     */
    public $weappInfo;

    /**
     * 插件业务逻辑
     */
    public $wechatLogic;

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $this->wechatLogic = new WechatLogic;

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo',$this->weappInfo);
        /*--end*/
    }
}