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

// namespace php;

use think\Model; 
use think\Request;
/*
 * 微信公众号插件
 * 开发者模式，请先注册微信开放平台账号，然后启动开发模式
 */
class base extends Model{
    public $appid;
    public $secret;
    public $config;
    public $access_token;
    public $codeArr;

    public function __construct($config){
        $this->config = $config;
        $this->appid = $config['appid'];
        $this->secret = $config['appsecret'];
        $this->codeArr = include(WECHAT_CONFIG.'code.php');
        $this->access_token = $this->get_access_token();
    }

    public function getClassObj($className)
    {
        $class = '\\'.$className; //
        return new $class($this->config); //实例化对应的类
    }

    /**
     * 获取access_token
     */
    public function get_access_token()
    {
        //判断是否过了缓存期
        if ($this->config['web_expires'] > getTime()) {
            return $this->config['web_access_token'];
        }
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $this->appid . "&secret=" . $this->secret;
        $response = $this->httpRequest($url,'GET');
        $params = array();
        $params = json_decode($response, true);
        $web_expires = getTime() + $this->config['web_expires_in']; // 提前200秒过期
        
        if (isset($params['errcode']))
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        } else {
            $data = array(
                'web_access_token'  => $params['access_token'],
                'web_expires'       => $web_expires,
                'update_time'       => getTime(),
            );
            M('weapp_wx_config')->where(array('id'=>$this->config['id']))->update($data);
            $this->config['web_access_token'] = $params['access_token'];
            $this->config['web_expires'] = $web_expires;
        }
        
        return $params['access_token'];
    }

    /**
     * CURL请求
     * @param $url 请求url地址
     * @param $method 请求方法 get post
     * @param null $postfields post数据数组
     * @param array $headers 请求header信息
     * @param bool|false $debug  调试开启 默认false
     * @return mixed
     */
    public function httpRequest($url, $method="GET", $postfields = null, $headers = array(), $debug = false) {
        $method = strtoupper($method);
        $ci = curl_init();
        /* Curl settings */
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
        curl_setopt($ci, CURLOPT_TIMEOUT, 30); /* 设置cURL允许执行的最长秒数 */
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        switch ($method) {
            case "POST":
                curl_setopt($ci, CURLOPT_POST, true);
                if (!empty($postfields)) {
                    $hadFile = false;
                    if (is_array($postfields) && isset($postfields['media'])) {
                        /* 支持文件上传 */
                        if (class_exists('\CURLFile')) {
                            curl_setopt($ci, CURLOPT_SAFE_UPLOAD, true);
                            foreach ($postfields as $key => $value) {
                                if ($this->isPostHasFile($value)) {
                                    $postfields[$key] = new \CURLFile(realpath(ltrim($value, '@')));
                                    $hadFile = true;
                                }
                            }
                        } elseif (defined('CURLOPT_SAFE_UPLOAD')) {
                            if ($this->isPostHasFile($postfields['media'])) {
                                curl_setopt($ci, CURLOPT_SAFE_UPLOAD, false);
                                $hadFile = true;
                            }
                        }
                    }
                    $tmpdatastr = (!$hadFile && is_array($postfields)) ? http_build_query($postfields) : $postfields;
                    curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
                }
                break;
            default:
                curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
                break;
        }
        $ssl = preg_match('/^https:\/\//i',$url) ? TRUE : FALSE;
        curl_setopt($ci, CURLOPT_URL, $url);
        if($ssl){
            curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
            curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
        }
        //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
        if (ini_get('open_basedir') == '' && ini_get('safe_mode' == 'Off')) {
            curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
        }
        curl_setopt($ci, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/
        curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        /*curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
        $response = curl_exec($ci);
        $requestinfo = curl_getinfo($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        if ($debug) {
            echo "=====post data======\r\n";
            var_dump($postfields);
            echo "=====info===== \r\n";
            print_r($requestinfo);
            echo "=====response=====\r\n";
            print_r($response);
        }
        curl_close($ci);

        //-------请求为空
        if(empty($response)){
            return json_encode(array('errcode'=>'-10001', 'errmsg'=>'http请求超时'));
            exit("50001");
        }

        return $response;
        //return array($http_code, $response,$requestinfo);
    }

    public function isPostHasFile($value)
    {
        if (is_string($value) && strpos($value, '@') === 0 && is_file(realpath(ltrim($value, '@')))) {
            return true;
        }
        return false;
    }

    /**
     * http请求方式: 默认GET
     */
    public function curlHttp($url, $method="GET"){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);
        if ("POST" == $method) {
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        $response =  curl_exec($ch);
        curl_close($ch);

        //-------请求为空
        if(empty($response)){
            exit("50001");
        }

        return $response;
    }

    /**
     * 递归创建目录 
     */  
    public function fun_mkdir($path)
    {
        if (!is_dir($path)) {
            $this->fun_mkdir(dirname($path));
            if (!mkdir($path, 0777)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 给图片添加文字水印以及图片水印
     * @param array $path_sy
            $image_sy = array(
                array(
                    'src_path'=>$headimgurl,
                    'src_w'=>0,
                    'src_h'=>0,
                    'locate'=>array(415, 137),
                    'alpha'=>100,
                    'info_bg'=>array(),
                ),
                array(
                    'src_path'=>$headimgurl,
                    'src_w'=>0,
                    'src_h'=>0,
                    'locate'=>array(415, 300),
                    'alpha'=>100,
                    'info_bg'=>array(),
                ),
            );
     * @param array $text_sy
            $text_sy = array(
                array(
                    'text'=>'文本姓名文本',//$user_info['nickname'], // 文案
                    'fontfile'=>'./hgzb.ttf', // 字体文件 
                    'size'=>18, // 字体大小
                    'color'=>'#ffffffff', // 字体颜色
                    'locate'=>10, // 文字写入位置
                    'offset'=>array(0, 0), // 文字相对当前位置的偏移量
                    'angle'=>0, // 文字倾斜角度
                    'info_bg'=>array('width'=>415, 'height'=>137),
                ),
                array(
                    'text'=>'文本姓名', // 文案
                    'fontfile'=>'./hgzb.ttf', // 字体文件 
                    'size'=>18, // 字体大小
                    'color'=>'#ffffffff', // 字体颜色
                    'locate'=>10, // 文字写入位置
                    'offset'=>array(0, 50), // 文字相对当前位置的偏移量
                    'angle'=>0, // 文字倾斜角度
                    'info_bg'=>array('width'=>415, 'height'=>137),
                ),
                array(
                    'text'=>'13976202972', // 文案
                    'fontfile'=>'./hgzb.ttf', // 字体文件 
                    'size'=>18, // 字体大小
                    'color'=>'#ffffffff', // 字体颜色
                    'locate'=>10, // 文字写入位置
                    'offset'=>array(0, 100), // 文字相对当前位置的偏移量
                    'angle'=>0, // 文字倾斜角度
                    'info_bg'=>array('width'=>415, 'height'=>137),
                ),
            );
     */
    public function addWatermark($path_bg, $image_sy = array(), $text_sy = array())
    {
        $watermarkObj = $this->getClassObj('watermark');

        // 取得背景图像大小
        list($bg_width, $bg_height, $bg_type) = @getimagesize($path_bg);
        $info_bg = array(
            'width'=>$bg_width,
            'height'=>$bg_height,
        );
        // 取得背景图片的扩展名
        $ext_bg = image_type_to_extension($bg_type, false);
        if ($ext_bg == 'gif') { // 目前暂不支持gif
            return false;
        }
        // 从字符串中的图像流新建一图像(背景)
        $fun = 'imagecreatefrom'.$ext_bg;
        if ($this->is_remote_url($path_bg)) {
            $img_bg = imagecreatefromstring($this->httpRequest($path_bg));
        } else {
            $img_bg = @$fun($path_bg);
        }

        // 图片水印
        if (!empty($image_sy)) {
            foreach ($image_sy as $key => $val) {
                // $img_bg = $watermarkObj->image($img_bg, $val['src_path'], $val['src_w'], $val['src_h'], $val['dst_x'], $val['dst_y'], $val['pct']);
                if (empty($val['info_bg'])) {
                    $val['info_bg'] = $info_bg;
                }
                $img_bg = $watermarkObj->water($img_bg, $val['src_path'], $val['src_w'], $val['src_h'], $val['locate'], $val['alpha'], $val['info_bg']);
            }
        }

        // 文字水印
        if (!empty($text_sy)) {
            foreach ($text_sy as $key => $val) {
                if (empty($val['info_bg'])) {
                    $val['info_bg'] = $info_bg;
                }
                $img_bg = $watermarkObj->text($img_bg, $val['info_bg'], $val['text'], $val['fontfile'], $val['size'], $val['color'], $val['locate'], $val['offset'], $val['angle']);
            }
        }

        // 保存新图片
        $filename = ROOT_PATH.'public/upload/tmp/'.date('Y/m/d/').md5(time().uniqid(mt_rand(), TRUE)).'.'.$ext_bg;
        $this->fun_mkdir(dirname($filename));
        $img_bg = $watermarkObj->save($img_bg, $filename);

        // 销毁图像
        imagedestroy($img_bg);

        return $filename;
    }

    /**
     * 日志记录
     */
    public function logger($log_content)
    {
        if (isset($_SERVER['HTTP_APPNAME'])) {   //SAE
            sae_set_display_errors(false);
            sae_debug($log_content);
            sae_set_display_errors(true);
        } else if ($_SERVER['REMOTE_ADDR'] != "127.0.0.1") { //LOCAL
            $max_size = 1000000;
            $log_filename = "messages.log.xml";
            if (file_exists($log_filename) and (abs(filesize($log_filename)) > $max_size)) {
                unlink($log_filename);
            }
            file_put_contents($log_filename, date('Y-m-d H:i:s')." ".$log_content."\r\n", FILE_APPEND);
        }
    }

    /**
     * 判断url是否远程链接
     */
    public function is_remote_url($url)
    {
        $t = preg_match('/(http:\/\/)|(https:\/\/)/i', $url);
        if ($t == 1) {
            return true;
        } else {
            return false;
        }
    }
}