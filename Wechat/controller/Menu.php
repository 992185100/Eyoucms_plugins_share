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

/**
 * 菜单控制器
 */
class Menu extends Base{

    public $wxconfig = array();

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
    }

    /*
     * 设置菜单
     */
    public function menu_index()
    {
        $token = I('param.token/s', '');
        if (empty($token)) {
            $this->error('参数有误');
            exit;
        }
        $wechat = M('weapp_wx_config')->where("token = '{$token}'")->find();
        if(empty($wechat)){
            $this->error('请先在公众号配置添加公众号，才能进行微信菜单管理');
        }
        if (!in_array($wechat['type'], array(2,4))) {
            $this->error('必须通过微信认证，才能正常使用！');
        }
        if(IS_POST){
            $this->menu_save($wechat);
        }

        //获取最大ID
        $max_id = DB::query("SHOW TABLE STATUS WHERE NAME = '__PREFIX__weapp_wx_menu'");
        $max_id = $max_id[0]['Auto_increment'] ? $max_id[0]['Auto_increment'] : $max_id[0]['Auto_increment'];
        //获取父级菜单
        $p_menus = M('weapp_wx_menu')->where(array('token'=>$wechat['token'],'pid'=>0))->order('sort_order asc, id asc')->select();
        $p_menus = convert_arr_key($p_menus,'id');
        //获取二级菜单
        $c_menus = M('weapp_wx_menu')->where(array('token'=>$wechat['token'],'pid'=>array('gt',0)))->order('sort_order asc, id desc')->select();
        $c_menus = convert_arr_key($c_menus,'id');
        $this->assign('p_lists',$p_menus);
        $this->assign('c_lists',$c_menus);
        $this->assign('max_id',$max_id ? $max_id-1 : 0);

        return $this->fetch('menu_index');
    }

    /*
     * 保存菜单
     */
    public function menu_save($wechat = array()){
        $post_menu = I('post.menu/a');
        $submit_type = I('post.submit_type/d');
        //查询数据库是否存在
        $menu_list = M('weapp_wx_menu')->where(array('token'=>$wechat['token']))->getField('id',true);
        foreach($post_menu as $k=>$v){
            $v['token'] = $wechat['token'];
            $v['update_time'] = getTime();
           if(in_array($k,$menu_list)){
                //更新
                M('weapp_wx_menu')->where(array('id'=>$k))->save($v);
           }else{
                $v['add_time'] = getTime();
                //插入
                M('weapp_wx_menu')->where(array('id'=>$k))->add($v);
           }
        }

        if ($submit_type == 0) {
            $this->success('保存成功', weapp_url('Wechat/Menu/menu_index', array('token'=>$wechat['token'])));
        } else {
            $this->menu_pub($wechat['token']);
        }
        exit;
    }

    /*
     * 删除菜单
     */
    public function menu_del(){
        $id = I('param.id/d', '');
        if(empty($id)){
            exit('fail');
        }
        $row = M('weapp_wx_menu')->where(array('id'=>$id))->delete();
        $row && M('weapp_wx_menu')->where(array('pid'=>$id))->delete(); //删除子类
        if($row){
            exit('success');
        }else{
            exit('fail');
        }
    }

    /*
     * 生成微信菜单
     */
    public function menu_pub($token){
        //获取菜单
        $wechat = M('weapp_wx_config')->where("token = '{$token}'")->find();
        //获取父级菜单
        $p_menus = M('weapp_wx_menu')->where(array('token'=>$wechat['token'],'pid'=>0))->order('sort_order ASC, id asc')->select();
        $p_menus = convert_arr_key($p_menus,'id');
        // http post请求
        if(empty($p_menus)){
            $this->error('没有菜单可发布', weapp_url('Wechat/Menu/menu_index', array('token'=>$token)));
            exit;
        }
        // 菜单转换
        $menuArr = $this->menu_convert($p_menus,$wechat['token']);
        /*自定义菜单创建*/
        weapp_vendor('wechat.wechat', 'Wechat');
        $menuObj = new \menu($wechat);
        $params = $menuObj->createMenu($menuArr);
        /*--end*/
        if($params['errcode'] == 0){
            $this->success('菜单已成功发布', weapp_url('Wechat/Menu/menu_index', array('token'=>$token)));
        }else{
            $this->error("错误代码[".$params['errcode']."]：".$params['errmsg'], weapp_url('Wechat/Menu/menu_index', array('token'=>$token)));
            exit;
        }
    }

    //菜单转换
    private function menu_convert($p_menus,$token){
        $key_map = array(
            'scancode_waitmsg'=>'rselfmenu_0_0',
            'scancode_push'=>'rselfmenu_0_1',
            'pic_sysphoto'=>'rselfmenu_1_0',
            'pic_photo_or_album'=>'rselfmenu_1_1',
            'pic_weixin'=>'rselfmenu_1_2',
            'location_select'=>'rselfmenu_2_0',
        );
        $new_arr = array();
        $count = 0;
        foreach($p_menus as $k => $v){
            $new_arr[$count]['name'] = $v['name'];

            //获取子菜单
            $c_menus = M('weapp_wx_menu')->where(array('token'=>$token,'pid'=>$k))->order('sort_order desc, id asc')->select();

            if($c_menus){
                foreach($c_menus as $kk=>$vv){
                    $add = array();
                    $add['name'] = $vv['name'];
                    $add['type'] = $vv['type'];
                    // click类型
                    if($add['type'] == 'click'){
                        // $add['key'] = 'rselfmenu_'.$count.'_'.$kk;
                        $add['key'] = $vv['value'];
                    }elseif($add['type'] == 'view'){
                        $add['url'] = $vv['value'];
                    }else{
                        //$add['key'] = $key_map[$add['type']];
                        $add['key'] = $vv['value'];
                    }
                    $add['sub_button'] = array();
                    if($add['name']){
                        $new_arr[$count]['sub_button'][] = $add;
                    }
                }
            }else{
                $new_arr[$count]['type'] = $v['type'];
                // click类型
                if($new_arr[$count]['type'] == 'click'){
                    // $new_arr[$count]['key'] = 'rselfmenu_'.$count.'_0';
                    $new_arr[$count]['key'] = $v['value'];
                }elseif($new_arr[$count]['type'] == 'view'){
                    //跳转URL类型
                    $new_arr[$count]['url'] = $v['value'];
                }else{
                    //其他事件类型
                    //$new_arr[$count]['key'] = $key_map[$v['type']];
                    $new_arr[$count]['key'] = $v['value']; 
                }
            }
            $count++;
        }

        return $new_arr;
    }
}