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
 * 客服消息接口实现
 */
class messages_service extends base{
    
    public function __construct($config){
        parent::__construct($config);
    }

    /**
     * 将消息转发到客服系统
     */
    public function transmitService($object)
    {
        $xmlTpl =   "<xml>
                        <ToUserName><![CDATA[%s]]></ToUserName>
                        <FromUserName><![CDATA[%s]]></FromUserName>
                        <CreateTime>%s</CreateTime>
                        <MsgType><![CDATA[transfer_customer_service]]></MsgType>
                    </xml>";
        $result = sprintf($xmlTpl, $object->FromUserName, $object->ToUserName, time());
        return $result;
    }

    /**
     * 发送消息-客服消息
     */
    public function sendMessages($post_data = array())
    {
        $url = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=" . $this->access_token;
        $this->httpRequest($url, 'POST', json_encode($post_data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 发送文本消息
     */
    public function sendServiceText($object, $content)
    {
        /* 获得openId值 */
        $openid = (string)$object->FromUserName;
        $post_data = array(
            'touser'    => $openid,
            'msgtype'   => 'text',
            'text'      => array(
                            'content'   => $content
                        )
        );
        $this->sendMessages($post_data);
    }

    /**
     * 发送图片消息
     */
    public function sendServiceImage($object, $media_id)
    {
        /* 获得openId值 */
        $openid = (string)$object->FromUserName;
        $post_data = array(
            'touser'    => $openid,
            'msgtype'   => 'image',
            'image'     => array(
                            'media_id'   => $media_id
                        )
        );
        $this->sendMessages($post_data);
    }

    /**
     * 发送语音消息
     */
    public function sendServiceVoice($object, $media_id)
    {
        /* 获得openId值 */
        $openid = (string)$object->FromUserName;
        $post_data = array(
            'touser'    => $openid,
            'msgtype'   => 'voice',
            'voice'     => array(
                            'media_id'   => $media_id
                        )
        );
        $this->sendMessages($post_data);
    }

    /**
     * 发送视频消息
     */
    public function sendServiceVideo($object, $media_id, $title = '', $description = '')
    {
        /* 获得openId值 */
        $openid = (string)$object->FromUserName;
        $post_data = array(
            'touser'    => $openid,
            'msgtype'   => 'video',
            'video'     => array(
                            'media_id'   => $media_id,
                            'thumb_media_id'   => $media_id,
                            'title'   => $title,
                            'description'   => $description
                        )
        );
        $this->sendMessages($post_data);
    }

    /**
     * 发送音乐消息
     */
    public function sendServiceMusic($object, $title = '', $description = '', $musicurl = '', $hqmusicurl = '', $thumb_media_id = '')
    {
        /* 获得openId值 */
        $openid = (string)$object->FromUserName;
        $post_data = array(
            'touser'    => $openid,
            'msgtype'   => 'music',
            'music'     => array(
                            'title'   => $title,
                            'description'   => $description,
                            'musicurl'   => $musicurl,
                            'hqmusicurl'   => $hqmusicurl,
                            'thumb_media_id' => $thumb_media_id
                        )
        );
        $this->sendMessages($post_data);
    }

    /**
     * 发送图文消息（点击跳转到外链） 图文消息条数限制在8条以内，注意，如果图文数超过8，则将会无响应。
     */
    public function sendServiceNews($object, $content = array())
    {
        /* 获得openId值 */
        $openid = (string)$object->FromUserName;
        $articles = array();
        foreach ($content as $key => $val) {
            $articles[] = array(
                'title'     => $val['title'],
                'description'     => $val['description'],
                'url'     => $val['url'],
                'picurl'     => $val['picurl']
            );
        }
        $post_data = array(
            'touser'    => $openid,
            'msgtype'   => 'news',
            'news'      => $articles
        );
        $this->sendMessages($post_data);
    }

    /**
     * 发送图文消息（点击跳转到外链） 图文消息条数限制在8条以内，注意，如果图文数超过8，则将会无响应。
     */
    public function sendServiceMpnews($object, $media_id)
    {
        /* 获得openId值 */
        $openid = (string)$object->FromUserName;
        $post_data = array(
            'touser'    => $openid,
            'msgtype'   => 'mpnews',
            'mpnews'      => array(
                            'media_id'  => $media_id
                        )
        );
        $this->sendMessages($post_data);
    }

    /**
     * 发送卡券
     */
    public function sendServiceWxcard($object, $card_id)
    {
        /* 获得openId值 */
        $openid = (string)$object->FromUserName;
        $post_data = array(
            'touser'    => $openid,
            'msgtype'   => 'wxcard',
            'wxcard'      => array(
                            'card_id'  => $card_id
                        )
        );
        $this->sendMessages($post_data);
    }

    /**
     * 设置客服帐号的头像
     */
    public function uploadheadimg($filepath, $kf_account)
    {
        /* 使用curl函数 */
        $url = "http://api.weixin.qq.com/customservice/kfaccount/uploadheadimg?access_token=" . $this->access_token . "&kf_account={$kf_account}@".$this->config['weixin'];
        $post_data = array(
            'media' => '@'.$filepath,
        );
        $response = $this->httpRequest($url, 'POST', $post_data);
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