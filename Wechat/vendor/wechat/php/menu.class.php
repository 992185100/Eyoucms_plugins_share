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

use think\Model; 
use think\Request;

/*
 * 自定义菜单管理接口实现
 */
class menu extends base{

    public function __construct($config){
        parent::__construct($config);
    }

    /**
     * 自定义菜单创建
     */
    public function createMenu($post_data = array())
    {
        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=" . $this->access_token;
        $response = $this->httpRequest($url, 'POST', json_encode(array('button'=>$post_data),JSON_UNESCAPED_UNICODE));
        $params = array();
        $params = json_decode($response,true);
        if ($params['errcode'] != 0)
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        return $params;
    }

    /**
     * 自定义菜单删除
     */
    public function deleteMenu()
    {
        
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=" . $this->access_token;
        $response = $this->httpRequest($url);
        $params = array();
        $params = json_decode($response,true);
        if ($params['errcode'] != 0)
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        return 'success';
    }

    /**
     * 获取自定义菜单配置接口
     */
    public function get_current_selfmenu_info()
    {
        
        $url = "https://api.weixin.qq.com/cgi-bin/get_current_selfmenu_info?access_token=" . $this->access_token;
        $response = $this->httpRequest($url);
        $params = array();
        $params = json_decode($response,true);
        if ($params['errcode'] != 0)
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        return $params;
    }

    /**
     * 创建个性化菜单
     */
    public function addconditional($post_data = array())
    {
        
        $url = "https://api.weixin.qq.com/cgi-bin/menu/addconditional?access_token=" . $this->access_token;
        $response = $this->httpRequest($url, 'POST', json_encode($post_data, JSON_UNESCAPED_UNICODE));
        $params = array();
        $params = json_decode($response,true);
        if (isset($params['errcode']))
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        return $params['menuid'];
    }

    /**
     * 删除个性化菜单
     */
    public function delconditional($menuid)
    {
        $post_data = array(
            'menuid'    => $menuid,
        );
        
        $url = "https://api.weixin.qq.com/cgi-bin/menu/delconditional?access_token=" . $this->access_token;
        $response = $this->httpRequest($url, 'POST', json_encode($post_data, JSON_UNESCAPED_UNICODE));
        $params = array();
        $params = json_decode($response,true);
        if ($params['errcode'] != 0)
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        return 'success';
    }

    /**
     * 测试个性化菜单匹配结果
     * @param string user_id 可以是粉丝的OpenID，也可以是粉丝的微信号
     */
    public function trymatch($user_id)
    {
        $post_data = array(
            'user_id'    => $user_id,
        );
        
        $url = "https://api.weixin.qq.com/cgi-bin/menu/trymatch?access_token=" . $this->access_token;
        $response = $this->httpRequest($url, 'POST', json_encode($post_data, JSON_UNESCAPED_UNICODE));
        $params = array();
        $params = json_decode($response,true);
        if (isset($params['errcode']))
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        return $params;
    }
}