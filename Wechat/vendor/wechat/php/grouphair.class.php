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
 * 群发消息接口实现
 */
class grouphair extends base{

    public function __construct($config){
        parent::__construct($config);
    }

    /**
     * 上传图文消息素材【订阅号与服务号认证后均可用】
     */
    public function uploadnews($post_data = array())
    {
        $url = "https://api.weixin.qq.com/cgi-bin/media/uploadnews?access_token=" . $this->access_token;
        $response = $this->httpRequest($url, 'POST', json_encode(array('articles'=>$post_data),JSON_UNESCAPED_UNICODE));
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

    /**
     * 根据标签进行群发【订阅号与服务号认证后均可用】
     */
    public function sendall($data = '', $msgtype, $is_to_all = true, $tag_id = 1, $send_ignore_reprint = 1)
    {
        $filter['is_to_all'] = $is_to_all;
        if (false == $is_to_all) $filter['tag_id'] = $tag_id;

        switch ($msgtype) {
            case 'mpnews':
            case 'voice':
            case 'image':
            case 'mpvideo':
                $msgtypeValue = array('media_id' => $data);
                break;
            case 'text':
                $msgtypeValue = array('content' => $data);
                break;

            case 'wxcard':
                $msgtypeValue = array('card_id' => $data);
                break;
        }

        $post_data = array(
            'filter'    => $filter,
            $msgtype    => $msgtypeValue,
            'msgtype'   => $msgtype,
            'send_ignore_reprint'   => $send_ignore_reprint,
            'clientmsgid'   => md5(getTime().uniqid(mt_rand(), TRUE)),
        );

        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token=" . $this->access_token;
        $response = $this->httpRequest($url, 'POST', json_encode($post_data,JSON_UNESCAPED_UNICODE));
        $params = array();
        $params = json_decode($response,true);
        if (is_array($params) && $params['errcode'] != 0)
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        return $params;
    }

    /**
     * 预览接口【订阅号与服务号认证后均可用】
     */
    public function preview($data = '', $msgtype, $openid)
    {
        switch ($msgtype) {
            case 'mpnews':
            case 'voice':
            case 'image':
            case 'mpvideo':
                $msgtypeValue = array('media_id' => $data);
                break;
            case 'text':
                $msgtypeValue = array('content' => $data);
                break;

            case 'wxcard':
                $msgtypeValue = array('card_id' => $data);
                break;
        }

        $post_data = array(
            'touser'    => $openid,
            $msgtype    => $msgtypeValue,
            'msgtype'   => $msgtype,
        );

        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/preview?access_token=" . $this->access_token;
        $response = $this->httpRequest($url, 'POST', json_encode($post_data,JSON_UNESCAPED_UNICODE));
        $params = array();
        $params = json_decode($response,true);
        if (is_array($params) && $params['errcode'] != 0)
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        return $params;
    }

    /**
     * 删除群发【订阅号与服务号认证后均可用】
     */
    public function delete($msg_id = '', $article_idx = 0)
    {
        $post_data = array(
            'msg_id'    => $msg_id,
            'article_idx'   => $article_idx,
        );

        $url = "https://api.weixin.qq.com/cgi-bin/message/mass/delete?access_token=" . $this->access_token;
        $response = $this->httpRequest($url, 'POST', json_encode($post_data,JSON_UNESCAPED_UNICODE));
        $params = array();
        $params = json_decode($response,true);
        if (is_array($params) && $params['errcode'] != 0)
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        return $params;
    }
}