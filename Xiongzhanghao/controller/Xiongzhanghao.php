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

namespace weapp\Xiongzhanghao\controller;

use think\Page;
use app\common\controller\Weapp;
use app\common\model\Weapp as WeappModel;
use weapp\Xiongzhanghao\model\XiongzhanghaoModel;

/**
 * 插件的控制器
 */
class Xiongzhanghao extends Weapp
{
    /**
     * 实例化模型
     */
    private $model;

    /**
     * 实例化对象
     */
    private $db;

    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    /**
     * 插件后台管理 - 列表
     */
    public function index()
    {
        // 获取插件数据
        $row = WeappModel::get(array('code' => $this->weappInfo['code']));

        if ($this->request->isPost()) {
            // 获取post参数
            $param = $this->request->only('sitemap_zzbaidutoken');
            // 转json赋值
            $row->data = json_encode($param);
            // 更新数据
            $r = $row->save();

            if ($r !== false) {
                delFile(HTML_ROOT); //清除页面缓存
                adminLog('编辑'.$this->weappInfo['name'].'：插件配置'); // 写入操作日志
                $this->success("操作成功!", weapp_url('Xiongzhanghao/Xiongzhanghao/index'));
            }
            $this->error("操作失败!");
        }

        // 获取配置JSON信息转数组
        $row = json_decode($row->data, true);
        $row['sitemap_zzbaidutoken'] = !empty($row['sitemap_zzbaidutoken']) ? $row['sitemap_zzbaidutoken'] : tpCache('sitemap.sitemap_zzbaidutoken');
        $this->assign('row', $row);

        return $this->fetch('index');
    }

    /**
     * 插件使用指南
     */
    public function doc(){
        return $this->fetch('doc');
    }

}