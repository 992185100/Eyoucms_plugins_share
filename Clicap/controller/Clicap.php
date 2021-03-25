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

namespace weapp\Clicap\controller;

use think\Db;
use app\common\controller\Weapp;
use weapp\Clicap\model\ClicapModel;


/**
 * 插件的控制器
 */
class Clicap extends Weapp
{
    /**
     * 实例化模型
     */
    private $model;


    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = new ClicapModel;

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    public function afterInstall()
    {
        //判断另外一个验证码插件是否存在且启用,启用则禁用,否则冲突
        if (is_dir('./weapp/VertifyManage/')) {
            $data = Db::name('weapp')->where('code', 'VertifyManage')->getField('data');
            $data = json_decode($data, true);
            $is_on = $data['captcha']['admin_login']['is_on'];
            if ($is_on == 1) {
                $data['captcha']['admin_login']['is_on'] = 0;
                $data = json_encode($data, true);
                $r = Db::name('weapp')->where('code', 'VertifyManage')->setField('data', $data);
                if ($r) {
                    adminLog('开启点选文字验证码,禁用验证码成功'); // 写入操作日志
                    return true;
                }
            }
        }
    }

    /**
     * 点选文字验证后台登录管理
     */
    public function index()
    {
        if (IS_POST) {
            $data = input('post.');
            //先把data取出来
            $clicap = $this->model->getWeappData();
            //然后替换data里更改的值
            $clicap['data']['captcha']['admin_login'] = $data;
            $saveData = array(
                'data' => serialize($clicap['data']),
                'tag_weapp' => '',
                'update_time' => getTime(),
            );
            $r = Db::name('weapp')->where(array('code' => $data['code']))->update($saveData);
            if ($r) {
                adminLog('编辑' . $this->weappInfo['name'] . '成功'); // 写入操作日志
                $this->success("操作成功!", weapp_url('Clicap/Clicap/index'));
            }
        }
        $clicap = $this->model->getWeappData();
        $this->assign('clicap', $clicap);

        return $this->fetch('index');
    }

    /**
     * 点选文字验证前台注册管理
     */
    public function users_reg()
    {
        if (IS_POST) {
            $data = input('post.');
            //先把data取出来
            $clicap = $this->model->getWeappData();
            //然后替换data里更改的值
            $clicap['data']['captcha']['users_reg'] = $data;
            $saveData = array(
                'data' => serialize($clicap['data']),
                'tag_weapp' => '',
                'update_time' => getTime(),
            );
            $r = Db::name('weapp')->where(array('code' => $data['code']))->update($saveData);
            if ($r) {
                adminLog('编辑' . $this->weappInfo['name'] . '成功'); // 写入操作日志
                $this->success("操作成功!", weapp_url('Clicap/Clicap/users_reg'));
            }
        }
        $clicap = $this->model->getWeappData();
        $this->assign('clicap', $clicap);

        return $this->fetch('users_reg');
    }

    /**
     * 点选文字验证前台登录管理
     */
    public function users_login()
    {
        if (IS_POST) {
            $data = input('post.');
            //先把data取出来
            $clicap = $this->model->getWeappData();
            //然后替换data里更改的值
            $clicap['data']['captcha']['users_login'] = $data;
            $saveData = array(
                'data' => serialize($clicap['data']),
                'tag_weapp' => '',
                'update_time' => getTime(),
            );
            $r = Db::name('weapp')->where(array('code' => $data['code']))->update($saveData);
            if ($r) {
                adminLog('编辑' . $this->weappInfo['name'] . '成功'); // 写入操作日志
                $this->success("操作成功!", weapp_url('Clicap/Clicap/users_login'));
            }
        }
        $clicap = $this->model->getWeappData();
        $this->assign('clicap', $clicap);

        return $this->fetch('users_login');
    }

    /**
     * 点选文字验证前台找回密码管理
     */
    public function users_retrieve_password()
    {
        if (IS_POST) {
            $data = input('post.');
            //先把data取出来
            $clicap = $this->model->getWeappData();
            //然后替换data里更改的值
            $clicap['data']['captcha']['users_retrieve_password'] = $data;
            $saveData = array(
                'data' => serialize($clicap['data']),
                'tag_weapp' => '',
                'update_time' => getTime(),
            );
            $r = Db::name('weapp')->where(array('code' => $data['code']))->update($saveData);
            if ($r) {
                adminLog('编辑' . $this->weappInfo['name'] . '成功'); // 写入操作日志
                $this->success("操作成功!", weapp_url('Clicap/Clicap/users_retrieve_password'));
            }
        }
        $clicap = $this->model->getWeappData();
        $this->assign('clicap', $clicap);

        return $this->fetch('users_retrieve_password');
    }

    /**
     * 点选文字验证留言模型管理
     */
    public function guestbook()
    {
        if (IS_POST) {
            $data = input('post.');
            //先把data取出来
            $clicap = $this->model->getWeappData();
            //然后替换data里更改的值
            $clicap['data']['captcha']['guestbook'] = $data;
            $saveData = array(
                'data' => serialize($clicap['data']),
                'tag_weapp' => '',
                'update_time' => getTime(),
            );
            $r = Db::name('weapp')->where(array('code' => $data['code']))->update($saveData);
            if ($r) {
                adminLog('编辑' . $this->weappInfo['name'] . '成功'); // 写入操作日志
                $this->success("操作成功!", weapp_url('Clicap/Clicap/guestbook'));
            }
        }
        $clicap = $this->model->getWeappData();
        $this->assign('clicap', $clicap);

        return $this->fetch('guestbook');
    }

}