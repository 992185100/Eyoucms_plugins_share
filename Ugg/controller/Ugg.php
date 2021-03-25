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

namespace weapp\Ugg\controller;

use think\Db;
use app\common\controller\Weapp;
use weapp\Ugg\model\UggModel;

/**
 * 插件的控制器
 */
class Ugg extends Weapp
{
    /**
     * 实例化模型
     */
    private $model;

    /**
     * 插件基本信息
     */
    private $weappInfo;
    private $scan_url = '';
    /**
     * 构造方法
     */
    public function __construct()
	{
        parent::__construct();
        $this->model = new UggModel;

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }


    /**
     * 系统内置钩子show方法（没用到这个方法，建议删掉）
     * 用于在前台模板显示片段的html代码，比如：QQ客服、对联广告等
     *
     * @param  mixed  $params 传入的参数
     */
    public function show($params = null){
        $ugg = $this->model->getWeappData();
        $this->assign('ugg', $ugg);
        echo $this->fetch('show');
    }

    /**
     * 插件后台管理 - 列表
     */
    public function index()
    {
        $ugg = $this->model->getWeappData();

        /*同时拥有本地上传与远程URL的逻辑处理*/
		 if (isset($ugg['data']['wechat_logo']) && is_http_url($ugg['data']['wechat_logo'])) {
            $ugg['data']['is_remote'] = 1;
            $ugg['data']['wechat_logo_remote'] = !empty($ugg['data']['wechat_logo']) ? $ugg['data']['wechat_logo'] : '';
        } else {
            $ugg['data']['is_remote'] = 0;
            $ugg['data']['wechat_logo_local'] = !empty($ugg['data']['wechat_logo']) ? $ugg['data']['wechat_logo'] : '/weapp/Ugg/template/style/images/tu1.png';
        }
		/*--end*/
		/*同时拥有本地上传与远程URL的逻辑处理*/
        if (isset($ugg['data']['public_logo']) && is_http_url($ugg['data']['public_logo'])) {
            $ugg['data']['is_remot'] = 1;
            $ugg['data']['public_logo_remote'] = !empty($ugg['data']['public_logo']) ? $ugg['data']['public_logo'] : '';
        } else {
            $ugg['data']['is_remot'] = 0;
            $ugg['data']['public_logo_local'] = !empty($ugg['data']['public_logo']) ? $ugg['data']['public_logo'] : '/weapp/Ugg/template/style/images/tu2.png';
        }
        /*--end*/
		/*同时拥有本地上传与远程URL的逻辑处理*/
        if (isset($ugg['data']['publicyi_logo']) && is_http_url($ugg['data']['publicyi_logo'])) {
            $ugg['data']['is_remotyi'] = 1;
            $ugg['data']['publicyi_logo_remote'] = !empty($ugg['data']['publicyi_logo']) ? $ugg['data']['publicyi_logo'] : '';
        } else {
            $ugg['data']['is_remotyi'] = 0;
            $ugg['data']['publicyi_logo_local'] = !empty($ugg['data']['publicyi_logo']) ? $ugg['data']['publicyi_logo'] : '/weapp/Ugg/template/style/images/dk1.png';
        }
        /*--end*/
		/*同时拥有本地上传与远程URL的逻辑处理*/
        if (isset($ugg['data']['publicyq_logo']) && is_http_url($ugg['data']['publicyq_logo'])) {
            $ugg['data']['is_remotyq'] = 1;
            $ugg['data']['publicyq_logo_remote'] = !empty($ugg['data']['publicyq_logo']) ? $ugg['data']['publicyq_logo'] : '';
        } else {
            $ugg['data']['is_remotyq'] = 0;
            $ugg['data']['publicyq_logo_local'] = !empty($ugg['data']['publicyq_logo']) ? $ugg['data']['publicyq_logo'] : '/weapp/Ugg/template/style/images/dk1.png';
        }
        /*--end*/
		/*同时拥有本地上传与远程URL的逻辑处理*/
        if (isset($ugg['data']['publicyw_logo']) && is_http_url($ugg['data']['publicyw_logo'])) {
            $ugg['data']['is_remotyw'] = 1;
            $ugg['data']['publicyw_logo_remote'] = !empty($ugg['data']['publicyw_logo']) ? $ugg['data']['publicyw_logo'] : '';
        } else {
            $ugg['data']['is_remotyw'] = 0;
            $ugg['data']['publicyw_logo_local'] = !empty($ugg['data']['publicyw_logo']) ? $ugg['data']['publicyw_logo'] : '/weapp/Ugg/template/style/images/da1.png';
        }
        /*--end*/
		/*同时拥有本地上传与远程URL的逻辑处理*/
        if (isset($ugg['data']['publicywe_logo']) && is_http_url($ugg['data']['publicywe_logo'])) {
            $ugg['data']['is_remotywe'] = 1;
            $ugg['data']['publicywe_logo_remote'] = !empty($ugg['data']['publicywe_logo']) ? $ugg['data']['publicywe_logo'] : '';
        } else {
            $ugg['data']['is_remotywe'] = 0;
            $ugg['data']['publicywe_logo_local'] = !empty($ugg['data']['publicywe_logo']) ? $ugg['data']['publicywe_logo'] : '/weapp/Ugg/template/style/images/ls.jpg';
        }
        /*--end*/
		/*同时拥有本地上传与远程URL的逻辑处理*/
        if (isset($ugg['data']['publicyweq_logo']) && is_http_url($ugg['data']['publicyweq_logo'])) {
            $ugg['data']['is_remotyweq'] = 1;
            $ugg['data']['publicyweq_logo_remote'] = !empty($ugg['data']['publicyweq_logo']) ? $ugg['data']['publicyweq_logo'] : '';
        } else {
            $ugg['data']['is_remotyweq'] = 0;
            $ugg['data']['publicyweq_logo_local'] = !empty($ugg['data']['publicyweq_logo']) ? $ugg['data']['publicyweq_logo'] : '/weapp/Ugg/template/style/images/ss.png';
        }
        /*--end*/
		/*同时拥有本地上传与远程URL的逻辑处理*/
        if (isset($ugg['data']['publicywett_logo']) && is_http_url($ugg['data']['publicywett_logo'])) {
            $ugg['data']['is_remotywett'] = 1;
            $ugg['data']['publicywe_logo_remote'] = !empty($ugg['data']['publicywett_logo']) ? $ugg['data']['publicywett_logo'] : '';
        } else {
            $ugg['data']['is_remotywett'] = 0;
            $ugg['data']['publicywett_logo_local'] = !empty($ugg['data']['publicywett_logo']) ? $ugg['data']['publicywett_logo'] : '/weapp/Ugg/template/style/images/kejian.jpg';
        }
        /*--end*/
		$this->assign('ugg', $ugg);
        return $this->fetch('index');
    }
    /**
     * 插件后台管理 - 编辑
     */
	public function save()
	{
	 if (IS_POST) {
            $data = I('post.');
            if (!empty($data['code'])) {

                /*处理LOGO的本地上传与远程*/
                $is_remote = !empty($data['is_remote']) ? $data['is_remote'] : 0;
                $wechat_logo = '';
                if ($is_remote == 1) {
                    $wechat_logo = $data['wechat_logo_remote']; // 远程链接
                } else {
                    $wechat_logo = $data['wechat_logo_local']; // 本地上传链接
                }
                $data['wechat_logo'] = $wechat_logo;
                /*--end*/
				/*处理LOGO的本地上传与远程*/
                $is_remot = !empty($data['is_remot']) ? $data['is_remot'] : 0;
                $public_logo = '';
                if ($is_remot == 1) {
                    $public_logo = $data['public_logo_remote']; // 远程链接
                } else {
                    $public_logo = $data['public_logo_local']; // 本地上传链接
                }
                $data['public_logo'] = $public_logo;
                /*--end*/
				/*处理LOGO的本地上传与远程*/
                $is_remotyi = !empty($data['is_remotyi']) ? $data['is_remotyi'] : 0;
                $publicyi_logo = '';
                if ($is_remotyi == 1) {
                    $publicyi_logo = $data['publicyi_logo_remote']; // 远程链接
                } else {
                    $publicyi_logo = $data['publicyi_logo_local']; // 本地上传链接
                }
                $data['publicyi_logo'] = $publicyi_logo;
                /*--end*/
				/*处理LOGO的本地上传与远程*/
                $is_remotyq = !empty($data['is_remotyq']) ? $data['is_remotyq'] : 0;
                $publicyq_logo = '';
                if ($is_remotyq == 1) {
                    $publicyq_logo = $data['publicyq_logo_remote']; // 远程链接
                } else {
                    $publicyq_logo = $data['publicyq_logo_local']; // 本地上传链接
                }
                $data['publicyq_logo'] = $publicyq_logo;
                /*--end*/
				/*处理LOGO的本地上传与远程*/
                $is_remotyw = !empty($data['is_remotyw']) ? $data['is_remotyw'] : 0;
                $publicyw_logo = '';
                if ($is_remotyw == 1) {
                    $publicyw_logo = $data['publicyw_logo_remote']; // 远程链接
                } else {
                    $publicyw_logo = $data['publicyw_logo_local']; // 本地上传链接
                }
                $data['publicyw_logo'] = $publicyw_logo;
                /*--end*/
				/*处理LOGO的本地上传与远程*/
                $is_remotywe = !empty($data['is_remotywe']) ? $data['is_remotywe'] : 0;
                $publicywe_logo = '';
                if ($is_remotywe == 1) {
                    $publicywe_logo = $data['publicywe_logo_remote']; // 远程链接
                } else {
                    $publicywe_logo = $data['publicywe_logo_local']; // 本地上传链接
                }
                $data['publicywe_logo'] = $publicywe_logo;
                /*--end*/
				/*处理LOGO的本地上传与远程*/
                $is_remotyweq = !empty($data['is_remotyweq']) ? $data['is_remotyweq'] : 0;
                $publicyweq_logo = '';
                if ($is_remotyweq == 1) {
                    $publicyweq_logo = $data['publicyweq_logo_remote']; // 远程链接
                } else {
                    $publicyweq_logo = $data['publicyweq_logo_local']; // 本地上传链接
                }
                $data['publicyweq_logo'] = $publicyweq_logo;
                /*--end*/
				/*处理LOGO的本地上传与远程*/
                $is_remotywett = !empty($data['is_remotywett']) ? $data['is_remotywett'] : 0;
                $publicywett_logo = '';
                if ($is_remotywett == 1) {
                    $publicywett_logo = $data['publicywett_logo_remote']; // 远程链接
                } else {
                    $publicywett_logo = $data['publicywett_logo_local']; // 本地上传链接
                }
                $data['publicywett_logo'] = $publicywett_logo;
                /*--end*/
                $saveData = array(
                    'data' => serialize($data),
                    'tag_weapp' => $data['tag_weapp'],
                    'update_time' => getTime(),
                );
                $r = Db::name('weapp')->where(array('code' => $data['code']))->update($saveData);
                if ($r) {
                    adminLog('编辑' . $this->weappInfo['name'] . '成功'); // 写入操作日志
                    $this->success("操作成功!", weapp_url('Ugg/Ugg/index'));
                }
            }
        }
        $this->error("操作失败");
    }
}