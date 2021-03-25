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

namespace weapp\DiyminiproMall\logic;

use think\Model;
use think\Db;
use think\Request;

/**
 * 业务逻辑
 */
class DiyminiproMallLogic extends Model
{
    private $request = null; // 当前Request对象实例
    private $current_lang = 'cn'; // 当前多语言标识

    /**
     * 析构函数
     */
    function  __construct() {
        null === $this->request && $this->request = Request::instance();
        $this->current_lang = get_admin_lang();
    }
    
    /**
     * 接口转化
     */
    public function get_api_url($query_str)
    {
        $apiUrl = 'aHR0cHM6Ly9zZXJ2aWNlLmV5eXN6LmNu';
        return base64_decode($apiUrl).$query_str;
    }

    /**
     * 获取远程最新的小程序参数配置
     */
    public function synRemoteSetting()
    {
        $diyminiproMallSettingModel = new \weapp\DiyminiproMall\model\DiyminiproMallSettingModel;
        $data = $diyminiproMallSettingModel->getSettingValue('setting');
        if (!empty($data)) {
            $vaules = [];
            $vaules['appId'] = $data['appId'];
            $query_str = http_build_query($vaules);
            $url = "/index.php?m=api&c=MiniproClient&a=minipro&".$query_str;
            $response = httpRequest($this->get_api_url($url));
            $params = array();
            $params = json_decode($response, true);
            if (!empty($params) && $params['errcode'] == 0) {
                $params['errmsg'] = array_merge($data, $params['errmsg']);
                $bool = $diyminiproMallSettingModel->setSettingValue('setting', $params['errmsg']);
                if ($bool) {
                    $data = $diyminiproMallSettingModel->getSettingValue('setting');
                } else {
                    $data = $params['errmsg'];
                }
                // 同步远程上线模板ID的状态到本地模板
                Db::name('weapp_diyminipro_mall')->where([
                    'mini_id'=>intval($data['online_mini_id']),
                ])->update([
                    'status'    => 5,
                    'update_time'   => getTime(),
                ]);
            }
        
            if (empty($data['authorizerStatus'])) {
                session('show_qrcode_total_1589417597', 0);
            }

            if (isset($data['miniproStatus']) && 4 <= $data['miniproStatus']) {
                // 清除没用的模板
                $max_mini_id = Db::name('weapp_diyminipro_mall')->where(['status'=>['egt', 4]])->max('mini_id');
                Db::name('weapp_diyminipro_mall')->where('mini_id','lt',$max_mini_id)->where('mini_id','neq',intval($data['online_mini_id']))->delete();
            }
        } else {
            // 清除没用的模板
            $max_mini_id = Db::name('weapp_diyminipro_mall')->where(['status'=>['egt', 4]])->max('mini_id');
            Db::name('weapp_diyminipro_mall')->where(['mini_id'=>['lt', $max_mini_id]])->delete();
        }

        return $data;
    }
    
    /**
     * 【新的默认图片】 图片不存在，显示默认无图封面
     * @param string $pic_url 图片路径
     * @param string|boolean $domain 完整路径的域名
     */
    public function get_default_pic($pic_url = '', $domain = false, $tcp = '')
    {
        $pic_url = get_default_pic($pic_url, $domain);
        $pic_url = str_replace('/public/static/common/images/not_adv.jpg', '/weapp/DiyminiproMall/template/skin/img/default_litpic.png', $pic_url);

        if (!empty($tcp) && preg_match('/^\/\/.*$/', $pic_url)) {
            $pic_url = $tcp . ':' . $pic_url;
        }

        return $pic_url;
    }

    // 验证微信商户配置的正确性
    public function GetWechatAppletsPay($appid = '', $mch_id = '', $apikey = '')
    {
        // 当前时间戳
        $time = time();

        // 当前时间戳 + OpenID 经 MD5加密
        $nonceStr = $out_trade_no = md5($time);

        // 调用支付接口参数
        $params = [
            'appid'            => $appid,
            'attach'           => "微信小程序支付",
            'body'             => "商品支付",
            'mch_id'           => $mch_id,
            'nonce_str'        => $nonceStr,
            'notify_url'       => url('plugins/DiyminiproMall/wxpay_notify', [], true, true, 1, 2),
            'out_trade_no'     => $out_trade_no,
            'spbill_create_ip' => $this->clientIP(),
            'total_fee'        => 1,
            'trade_type'       => 'JSAPI'
        ];

        // 生成参数签名
        $params['sign'] = $this->ParamsSign($params, $apikey);

        // 生成参数XML格式
        $ParamsXml = $this->ParamsXml($params);

        // 调用接口返回数据
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $result = httpRequest($url, 'POST', $ParamsXml);

        // 解析XML格式
        $ResultData = $this->ResultXml($result);

        // 数据返回
        if ($ResultData['return_code'] == 'SUCCESS' && $ResultData['return_msg'] == 'OK') {
            return ['code'=>1, 'msg'=>'验证通过'];
        } else if ($ResultData['return_code'] == 'FAIL') {
            return ['code'=>0, 'msg'=>'支付商户号或支付密钥不正确！'];
        }
    }

    /**
     * 客户端IP
     */
    private function clientIP()
    {
        $ip = request()->ip();
        if (preg_match('/^((?:(?:25[0-5]|2[0-4]\d|((1\d{2})|([1-9]?\d)))\.){3}(?:25[0-5]|2[0-4]\d|((1\d{2})|([1 -9]?\d))))$/', $ip))
            return $ip;
        else
            return '';
    }

    private function ParamsSign($values, $apikey)
    {
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = $this->ParamsUrl($values);
        //签名步骤二：在string后加入KEY
        $string = $string . '&key=' . $apikey;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    private function ParamsUrl($values)
    {
        $Url = '';
        foreach ($values as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $Url .= $k . '=' . $v . '&';
            }
        }
        return trim($Url, '&');
    }

    private function ParamsXml($values)
    {
        if (!is_array($values)
            || count($values) <= 0
        ) {
            return false;
        }

        $xml = "<xml>";
        foreach ($values as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    private function ResultXml($xml)
    {
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}
