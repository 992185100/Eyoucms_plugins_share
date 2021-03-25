<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: King超 <23939139@qq.com>
 * Date: 2018-11-13
 */

namespace weapp\AdminLog\controller;

use think\Page;
use think\Db;
use app\common\controller\Weapp;
use weapp\AdminLog\model\AdminLogModel;

/**
 * 插件的控制器
 */
class AdminLog extends Weapp
{
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
        $list = array();
        $keywords = I('keywords/s');

        $map = array();
        if (!empty($keywords)) {
            $map['log_info'] = array('LIKE', "%{$keywords}%");
        }

        $count = AdminLogModel::where($map)->count('log_id');// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = AdminLogModel::with('admin')->where($map)->order('log_id desc')->limit($pageObj->firstRow.','.$pageObj->listRows)->select();
        $pageStr = $pageObj->show(); // 分页显示输出
        $this->assign('list', $list); // 赋值数据集
        $this->assign('pageStr', $pageStr); // 赋值分页输出
        $this->assign('pageObj', $pageObj); // 赋值分页对象

        return $this->fetch('index');
    }

    /**
     * 删除文档
     */
    public function del()
    {
        $id_arr = I('del_id/a');
        $id_arr = eyIntval($id_arr);
        if(!empty($id_arr)){

            $r = AdminLogModel::where("log_id",'IN',$id_arr)->delete();
            if($r){
                adminLog('日志清除');
                $this->success("操作成功!");
            }else{
                $this->error("操作失败!");
            }
        }else{
            $this->error("参数有误!");
        }
    }
    
}