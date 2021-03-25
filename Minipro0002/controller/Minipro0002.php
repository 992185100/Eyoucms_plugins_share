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

namespace weapp\Minipro0002\controller;

use think\Db;
use app\common\controller\Weapp;
use weapp\Minipro0002\model\Minipro0002Model;
use weapp\Minipro0002\logic\Minipro0002Logic;

/**
 * Minipro0002-插件的控制器
 */
class Minipro0002 extends Weapp{

    /**
     * 实例化DB对象
     */
    private $db;

    /**
     * 实例化模型对象
     */
    private $model;

    /**
     * 实例化业务逻辑对象
     */
    private $logic;

    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 模板nid，每套模板唯一
     */
    private $nid = 'Minipro0002';

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $this->logic = new Minipro0002Logic;
        $this->model = new Minipro0002Model;
        $this->db = M($this->model->name);

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo',$this->weappInfo);
        /*--end*/
    }

    /**
     * 安装的前置操作
     */
    public function beforeInstall()
    {
        $row = Db::name('weapp')->field('code,name')->where([
                'code'      => ['LIKE', 'Minipro%'],
                'status'    => 1,
            ])->find();
        if (!empty($row) && preg_match('/^minipro(\d+)$/i', $row['code'])) {
            $this->error("请先卸载插件【{$row['name']}】，小程序插件只能安装一个！", url('Weapp/index'));
        }
    }

    /**
     * 插件使用说明
     */
    public function doc(){
        return $this->fetch('doc');
    }

    /**
     * 插件第一入口
     */
    public function index()
    {
        return $this->setting();
    }
    
    /**
     * 小程序配置
     */
    public function setting()
    {
        if (IS_POST) {
            $post = I('post.');
            if (empty($post['nid'])) {
                $this->error('小程序模板nid不存在');
            }
            $post['domain'] = trim($post['domain'], '/');
            $post['domain'] = preg_replace('/^([^\:]+):\/\//i', '', $post['domain']);

            // 设置业务域名，这里存放根域名
            if (!empty($post['domain'])) {
                $post['webviewdomain'] = preg_replace('/^([^\/]*)(.*)$/i', '${1}', $post['domain']);
            }

            // 小程序标题，用于提交审核
            $navtitle = tpCache('web.web_name');
            $post['navTitle'] = !empty($post['navTitle']) ? $post['navTitle'] : msubstr($navtitle, 0, 32);

            /*同步数据到服务器*/
            $response = httpRequest($this->logic->get_api_url("/index.php?m=api&c=MiniproClient&a=minipro"), "POST", $post);
            $params = array();
            $params = json_decode($response, true);
            /*--end*/

            if (!empty($params)) {
                if ($params['errcode'] == 0) {
                    /*保存数据*/
                    $newData = array(
                        'type' => $this->model->miniproType,
                        'value' => json_encode($post),
                    );
                    $row = $this->model->getRow($this->model->miniproType);
                    if (empty($row)) { // 新增
                        $newData['add_time'] = getTime();
                        $r = $this->model->insert($newData);
                    } else {
                        $newData['update_time'] = getTime();
                        $r = $this->model->where('type','eq',$this->model->miniproType)->update($newData);
                    }
                    if (false !== $r) {
                        header('Location: '.weapp_url('Minipro0002/Minipro0002/createMinipro'));
                        exit;
                        // $this->success('操作成功', weapp_url('Minipro0002/Minipro0002/setting'));
                    }
                    /*--end*/
                } else {
                    $this->error($params['errmsg']);
                }
            }
            $this->error('操作失败');
        }

        $assign_data = array();

        $row = $this->logic->getSetting();
        if (empty($row)) {
            $row['domain'] = $this->request->host().ROOT_DIR;
        }
        $assign_data['row'] = $row;

        /*模板类型*/
        $template_list = array();
        $response = httpRequest($this->logic->get_api_url("/index.php?m=api&c=MiniproClient&a=get_minipro_list"), "GET");
        $params = json_decode($response,true);
        if (!empty($params) && $params['errcode'] == 0) {
            $template_list = $params['errmsg'];
        } else {
            $this->error('小程序模板不存在');
        }
        $miniproNum = preg_replace('/([a-z])/i', '', $template_list[$this->nid]['nid']);
        $assign_data['version'] = 'v'.intval($miniproNum).'.0';
        $assign_data['template_list'] = $template_list;
        /*--end*/

        $assign_data['nid'] = $this->nid; // 模板nid，每套模板唯一
        $assign_data['type'] = $this->model->miniproType; // 小程序配置信息的type值

        $this->assign($assign_data);

        return $this->fetch('setting');
    }

    /**
     * 生成小程序
     */
    public function createMinipro()
    {
        $inc = $this->logic->getSetting();
        if (empty($inc)) {
            $this->error('先填写小程序配置');
        }

        if ($inc['authorizerStatus'] == 0) {
            $gourl = urlencode(weapp_url('Minipro0002/Minipro0002/createMinipro', '', true, request()->domain()));
            $authorization_url = $this->logic->get_api_url("/index.php?m=api&c=Minipro&a=client_authoriza&authorizer_appid=".$inc['appId']."&gourl={$gourl}");
            header('Location: '.$authorization_url);
            exit;
        }

        $post_data = array(
            'appid' => $inc['appId'],
        );
        $response = httpRequest($this->logic->get_api_url("/index.php?m=api&c=Minipro&a=createMinipro"), "POST", $post_data);
        $params = array();
        $params = json_decode($response,true);
        if ($params) {
            if ($params['errcode'] === 0) {
                $this->success('正在生成小程序中……', weapp_url('Minipro0002/Minipro0002/setting'));
            } else {
                $this->error($params['errmsg']);
            }
        }
    }

    /**
     * 获取体验二维码
     */
    public function getQrcode()
    {
        $inc = $this->logic->getSetting();
        if (empty($inc)) {
            $this->error('先填写小程序配置');
        }

        $post_data = array(
            'appid' => $inc['appId'],
        );
        $response = httpRequest($this->logic->get_api_url("/index.php?m=api&c=Minipro&a=getQrcode"), "POST", $post_data);
        $params = array();
        $params = json_decode($response,true);
        if ($params) {
            if ($params['errcode'] === 0 || $params['errcode'] == 85004) {
                $imgcode = base64_decode($params['errmsg']);
                $filename = md5($inc['appId'].time().mt_rand(1000,9999)).".jpg";
                $bannerurl = UPLOAD_PATH.'minipro/'.date('Ymd');
                tp_mkdir($bannerurl);
                $bannerurl = $bannerurl."/".$filename;
                $imgurl = '';
                if (file_put_contents($bannerurl, $imgcode)){
                    $imgurl = request()->domain()."/{$bannerurl}";
                }

                $params['msg'] = $imgurl;
                $this->success('操作成功', null, $params);
            } else {
                $this->error($params['errmsg'], null, $params);
            }
        }

        $this->error('获取体验二维码失败，请多重试几次！');
    }

    /**
     * 提交小程序审核
     */
    public function submitAudit()
    {
        $inc = $this->logic->getSetting();
        if (empty($inc)) {
            $this->error('先填写小程序配置');
        }

        if (2 == $inc['auditstatus']) {
            $estimateTime = date('Y-m-d H:i:s', $inc['estimateTime']);
            $this->success("审核中……预计{$estimateTime}之前完成", weapp_url('Minipro0002/Minipro0002/setting'), '', 3);
        }

        $post_data = array(
            'appid' => $inc['appId'],
        );
        $response = httpRequest($this->logic->get_api_url("/index.php?m=api&c=Minipro&a=submitAudit"), "POST", $post_data);
        $params = array();
        $params = json_decode($response,true);
        if ($params) {
            if ($params['errcode'] === 0) {
                $this->success("进入审核中……", weapp_url('Minipro0002/Minipro0002/setting'));
            } else {
                $this->error($params['errmsg']);
            }
        }

        $this->error('接口调用失败，请重新尝试');
    }

    /**
     * 查询审核状态
     */
    public function getAuditstatus()
    {
        $inc = $this->logic->getSetting();
        if (empty($inc)) {
            $this->error('先填写小程序配置');
        }

        $post_data = array(
            'appid' => $inc['appId'],
        );
        $response = httpRequest($this->logic->get_api_url("/index.php?m=api&c=Minipro&a=getAuditstatus"), "POST", $post_data);
        $params = array();
        $params = json_decode($response,true);
        if ($params) {
            echo json_encode($params);
            exit;
        }

        echo json_encode(array('errcode'=>-1, 'errmsg'=>'查询审核状态出错！'));
        exit;
    }

    /**
     * 发布小程序
     */
    public function release()
    {
        $inc = $this->logic->getSetting();
        if (empty($inc)) {
            $this->error('先填写小程序配置');
        }

        if ($inc['auditstatus'] == 2) {
            $estimateTime = date('Y-m-d H:i:s', $inc['estimateTime']);
            $this->success("审核中……预计{$estimateTime}之前完成", weapp_url('Minipro0002/Minipro0002/setting'), '', 3);
        } else if ($inc['auditstatus'] == 1) {
            $this->error('审核失败，原因：'.$inc['reason'], weapp_url('Minipro0002/Minipro0002/setting'), '', 5);
        }

        $post_data = array(
            'appid' => $inc['appId'],
        );
        $response = httpRequest($this->logic->get_api_url("/index.php?m=api&c=Minipro&a=release"), "POST", $post_data);
        $params = array();
        $params = json_decode($response,true);
        if ($params) {
            if ($params['errcode'] === 0) {
                try {
                    $this->getWxaCodeunlimit($inc);
                } catch (\Exception $e) {}
                $this->success("发布成功", weapp_url('Minipro0002/Minipro0002/setting'));
            } else {
                $this->error($params['errmsg'].'(代码'.$params['errcode'].')', weapp_url('Minipro0002/Minipro0002/setting'), '', 3);
            }
        }

        $this->error('接口调用失败，请重新尝试');
    }

    /**
     * 配置业务域名
     */
    public function webviewdomain()
    {
        return $this->fetch();
    }

    /**
     * 获取小程序码
     */
    public function getWxaCodeunlimit($inc = [])
    {
        empty($inc) && $inc = $this->logic->getSetting();
        $post_data = array(
            'appid' => $inc['appId'],
        );
        $response = httpRequest($this->logic->get_api_url("/index.php?m=api&c=Minipro&a=getWxaCodeunlimit"), "POST", $post_data);
        $params = array();
        $params = json_decode($response,true);
        if ($params) {
            if ($params['errcode'] === 0) {
                $imgcode = base64_decode($params['errmsg']);
                $filename = md5($inc['appId']).".jpg";
                $bannerurl = UPLOAD_PATH.'minipro/other';
                tp_mkdir($bannerurl);
                $bannerurl = $bannerurl."/".$filename;
                $imgurl = '';
                @file_put_contents($bannerurl, $imgcode);
            }
        }
    }

    /**
     * 下载小程序码
     */
    public function downqrcode()
    {
        $inc = $this->logic->getSetting();
        if (empty($inc)) {
            $this->error('先填写小程序配置');
        }

        $bannerurl = UPLOAD_PATH.'minipro/other/'.md5($inc['appId']).".jpg";
        if (file_exists('./'.$bannerurl)) {
            $imgurl = request()->domain().ROOT_DIR."/{$bannerurl}";
            $filename = md5($inc['appId'].time().mt_rand(1000,9999)).".jpg";
            // header("Cache-control: private");
            header("Content-Type:application/force-download"); //设置要下载的文件类型
            header("Content-Disposition: attachment; filename={$filename}"); //设置要下载文件的文件名
            readfile($imgurl);
            exit();
        }

        $this->error('下载失败，请到小程序官方后台下载！');
    }
}