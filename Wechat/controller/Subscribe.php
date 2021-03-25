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

/**
 * 被关注回复控制器
 */
class Subscribe extends Base{

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $token = I('param.token/s', '');
        $saName = I('param.sa/s', '');
        if (empty($token) && 'subscribe_text' == $saName) {
            return $this->subscribe_default();
        }
    }

    /*
     * 快捷入口
     */
    public function subscribe_default()
    {
        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        if (empty($wechat_list)) {
            $this->error('请先新增公众号！', weapp_url('Wechat/Wechat/config_add'));
        } elseif (1 == count($wechat_list)) {
            $firstRow = current($wechat_list);
            $url = weapp_url('Wechat/Subscribe/subscribe_text', array('token'=>$firstRow['token']));
            $this->redirect($url);
        }
        $assign_data['wechat_list'] = $wechat_list;

        $this->assign($assign_data);
        echo $this->fetch('subscribe_default');
        exit;
    }

    /*
     * 被关注回复回复
     */
    public function subscribe_text()
    {
        if (IS_POST) {
            $post = I('post.');
            $token = I('post.token/s', '');

            /*检测是否存在*/
            $map = array(
                'token' => $token,
            );
            $count = M('weapp_wx_subscribe')->where($map)->count('id');
            /*--end*/

            if ('TEXT' == $post['type']) {
                unset($post['media_id']);
                unset($post['litpic']);
                unset($post['wx_img_url']);
            } else if ('PIC' == $post['type']) {
                unset($post['text']);
                /*上传图片*/
                $litpic = '';
                $file = request()->file('litpic');
                if (empty($count) && empty($file)) {
                    $this->error('请上传图片');
                }
                if (!empty($file)) {
                    $logic = new \weapp\Wechat\logic\WechatLogic();
                    $pic_info = $logic->upFile('litpic');
                    if ($pic_info['state'] == 'SUCCESS') {
                        $litpic = $pic_info['url'];
                    } else {
                        $this->error($pic_info['state']);
                    }
                }
                /*--end*/
            
                /*新增永久素材*/
                if (!empty($litpic)) {
                    weapp_vendor('wechat.wechat', 'Wechat');
                    $wxconfig = M('weapp_wx_config')->where('token', $token)->find();
                    if (!empty($wxconfig)) {
                        $materialObj = new \material($wxconfig);
                        $params = $materialObj->addMaterial(ROOT_PATH.ltrim($litpic, '/'), 'image');
                        $post['media_id'] = $params['media_id'];
                        $post['wx_img_url'] = $params['url'];
                        $post['litpic'] = $litpic;
                    }
                } else {
                    unset($post['litpic']);
                }
                /*--end*/
            }

            if (empty($count)) { // 新增
                $data = array(
                    'text' => msubstr($post['text'], 0, 600, false),
                    'add_time'  => getTime(),
                    'update_time'  => getTime(),
                );
                $nowData = array_merge($post, $data);
                $row = M('weapp_wx_subscribe')->insert($nowData);
            } else { // 编辑
                $data = array(
                    'update_time'  => getTime(),
                );
                $nowData = array_merge($post, $data);
                $row = M('weapp_wx_subscribe')->where('token', $token)->update($nowData);
            }

            if ($row) {
                $this->success("操作成功", weapp_url('Wechat/Subscribe/subscribe_text', array('token'=>$token)));
            }

            $this->error("操作失败");
        }

        $token = I('param.token/s', '');
        $type = 'TEXT';
        $map = array(
            'token' => $token,
        );
        $info = M('weapp_wx_subscribe')->where($map)->order('id desc')->find();
        if (empty($info)) {
            $info = array(
                'token' => $token,
                'type'  => $type,
            );
        }
        $this->assign('field',$info);

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $this->assign('wechat_list', $wechat_list);
        $this->assign('keywordTypeList', $this->wechatLogic->get_keyword_type());

        return $this->fetch('subscribe_text');
    }
    
    /**
     * 验证公众号权限
     */
    public function checkWechat()
    {
        $token = I('param.token/s', '');
        $wxconfig = M('weapp_wx_config')->where("token = '{$token}'")->find();
        if (!in_array($wxconfig['type'], array(2,4))) {
            $this->error('必须通过微信认证，才能正常使用！');
        }
    }
}