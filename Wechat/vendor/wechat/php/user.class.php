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
 * 用户管理接口实现
 */
class user extends base{

    public function __construct($config){
        parent::__construct($config);
    }
    
    public function get_user_info($openid)
    {
        $user_info_url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid={$openid}&lang=zh_CN";

        $response = $this->httpRequest($user_info_url);
        $params = array();
        $params = json_decode($response,true);
        if (isset($params['errcode']))
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        // 返回用户信息，字段说明  https://mp.weixin.qq.com/wiki?t=resource/res_main&id=mp1421140839
        $user_info = array(
            'subscribe'     => isset($params['subscribe']) ? $params['subscribe'] : 0, // 用户是否订阅该公众号标识，值为0时，代表此用户没有关注该公众号，拉取不到其余信息。
            'openid'        => isset($params['openid']) ? $params['openid'] : '', // 用户的标识，对当前公众号唯一
            'nickname'        => isset($params['nickname']) ? $params['nickname'] : '', // 用户的昵称
            'sex'        => $this->sexName($params['sex']), // 用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
            'country'        => isset($params['country']) ? $params['country'] : '', // 用户所在国家
            'province'        => isset($params['province']) ? $params['province'] : '', // 用户所在省份
            'city'        => isset($params['city']) ? $params['city'] : '', // 用户所在城市
            'language'        => isset($params['language']) ? $params['language'] : 'zh_CN', // 用户的语言，简体中文为zh_CN
            'headimgurl'        => isset($params['headimgurl']) ? $params['headimgurl'] : '', // 用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像），用户没有头像时该项为空。若用户更换头像，原有头像URL将失效。
            'subscribe_time'        => isset($params['subscribe_time']) ? $params['subscribe_time'] : 0, // 用户关注时间，为时间戳。如果用户曾多次关注，则取最后关注时间
            'unionid'        => isset($params['unionid']) ? $params['unionid'] : '', // 只有在用户将公众号绑定到微信开放平台帐号后，才会出现该字段。
            'remark'        => isset($params['remark']) ? $params['remark'] : '', // 公众号运营者对粉丝的备注，公众号运营者可在微信公众平台用户管理界面对粉丝添加备注
            'groupid'        => isset($params['groupid']) ? $params['groupid'] : 0, // 用户所在的分组ID（兼容旧的用户分组接口）
            'tagid_list'        => isset($params['tagid_list']) ? $params['tagid_list'] : array(), // 用户被打上的标签ID列表
        );

        return $user_info;
    }

    /**
     * sex_id 用户的性别，值为1时是男性，值为2时是女性，值为0时是未知
     */
    public function sexName($sex_id)
    {
        if ($sex_id == 1) {
            return '男';
        } else if ($sex_id == 2) {
            return '女';   
        }
        return '未知';
    }
}