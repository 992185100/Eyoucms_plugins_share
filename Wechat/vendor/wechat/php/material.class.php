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
 * 素材管理接口实现
 */
class material extends base{

    public function __construct($config){
        parent::__construct($config);
    }

    /**
     * 新增临时素材
     */
    public function mediaUpload($filepath, $type = 'image')
    {
        /* 使用curl函数 */
        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token=" . $this->access_token . "&type=" . $type;
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

        /* 使用exec函数 */
        // $command = 'curl -F media=@'.$filepath.' "https://api.weixin.qq.com/cgi-bin/media/upload?access_token='.$this->access_token.'&type='.$type.'"';

        // /* 使用exec函数 */
        // $retval = array();
        // exec($command, $retval, $status);
        // $params = array();
        // $params = json_decode($retval[0],true);
        // if ($status != 0) {
        //     $params = array(
        //         'errcode'   => '-100',
        //         'errmsg'    => '公众号服务出错，请联系管理员',
        //     );
        // }

        /* 使用system函数 */
        // $retval = 1;
        // $last_line = system($command, $retval);
        // $params = array();
        // $params = json_decode($last_line,true);
        // if ($retval != 0) {
        //     if (isset($params['errcode'])) {
        //         $params = array(
        //             'errcode'   => '-100',
        //             'errmsg'    => '公众号服务出错，请联系管理员',
        //         );
        //     }
        // }

        return $params;
    }

    /**
     * 新增永久素材
     * 图片仅支持bmp/png/jpeg/jpg/gif格式，大小必须在2MB以下
     */
    public function addMaterial($filepath, $type = 'image')
    {
        /* 使用curl函数 */
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_material?access_token=" . $this->access_token . "&type=" . $type;
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

    /**
     * 新增永久图文素材
     */
    public function addNews($data = array())
    {
        $post_data = array(
            'articles' => $data,
        );
        $url = "https://api.weixin.qq.com/cgi-bin/material/add_news?access_token=" . $this->access_token;
        $response = $this->httpRequest($url, 'POST', json_encode($post_data,JSON_UNESCAPED_UNICODE));
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
     * 上传图文消息内的图片获取URL【订阅号与服务号认证后均可用】
     * 图片仅支持jpg/png格式，大小必须在1MB以下
     */
    public function uploadimg($filepath)
    {
        /* 使用curl函数 */
        $url = "https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token=" . $this->access_token;
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

    /**
     * 获取临时素材
     */
    public function getTmpMaterial($media_id)
    {
        /* 使用curl函数 */
        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=" . $this->access_token . "&media_id=" . $media_id;
        $response = $this->httpRequest($url, 'GET');
        $params = array();
        $params = json_decode($response,true);
        if (isset($params['errcode']))
        {
            $errmsg = isset($this->codeArr[$params['errcode']]) ? $this->codeArr[$params['errcode']] : $params['errmsg'];
            echo "<h3>error:</h3>" . $params['errcode'];
            echo "<h3>msg  :</h3>" . $errmsg;
            exit;
        }

        // $command = 'curl -I -G "https://api.weixin.qq.com/cgi-bin/media/get?access_token='.$this->access_token.'&media_id="'.$media_id;

        // /* 使用exec函数 */
        // $retval = array();
        // exec($command, $retval, $status);
        // $params = array();
        // $params = json_decode($retval[0],true);
        // if ($status != 0) {
        //     $params = array(
        //         'errcode'   => '-100',
        //         'errmsg'    => '公众号服务出错，请联系管理员',
        //     );
        // }

        return $params;
    }

    /**
     * 获取永久素材
     */
    public function getMaterial($media_id)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_material?access_token=" . $this->access_token;
        $post_data = array(
            'media_id'  => $media_id,
        );
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

    /**
     * 删除永久素材
     */
    public function delMaterial($media_id)
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/del_material?access_token=" . $this->access_token;
        $post_data = array(
            'media_id'  => $media_id,
        );
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

        return $params;
    }

    /**
     * 修改永久图文素材
     */
    public function updateNews($post_data = array())
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/update_news?access_token=" . $this->access_token;
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

        return $params;
    }

    /**
     * 获取素材总数
     */
    public function getMaterialcount($post_data = array())
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/get_materialcount?access_token=" . $this->access_token;
        $response = $this->httpRequest($url);
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
     * 获取素材列表
     */
    public function batchgetMaterial($post_data = array())
    {
        $url = "https://api.weixin.qq.com/cgi-bin/material/batchget_material?access_token=" . $this->access_token;
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

        return $params;
    }
}