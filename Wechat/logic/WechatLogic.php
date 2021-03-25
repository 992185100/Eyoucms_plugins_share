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

namespace weapp\Wechat\logic;

use common\util\File;
use app\admin\controller\Base;

/**
 * 逻辑定义
 * Class WechatLogic
 */
class WechatLogic
{
    /**
     * 上传文件
     */
    public function upFile($fieldName)
    {
        $bWechatLogic = new BWechatLogic();
        return $bWechatLogic->upFile($fieldName);
    }

    /**
     * 获取公众号类型
     */
    public function get_wechat_type($type = '')
    {
        $list = array(
            // 1 => '订阅号',
            2 => '认证订阅号',
            // 3 => '服务号',
            4 => '认证服务号',
        );
        if (!empty($type)) {
            if (isset($list[$type])) {
                $list = $list[$type];
            } else {
                $list = '';
            }
        }

        return $list;
    }

    /**
     * 关键字类型
     */
    public function get_keyword_type($type = '')
    {
        $list = array(
            'TEXT' => '文本',
            'PIC' => '图片',
            'IMG' => '单图文',
            'NEWS' => '组合图文',
        );
        if (!empty($type)) {
            if (isset($list[$type])) {
                $list = $list[$type];
            } else {
                $list = '';
            }
        }

        return $list;
    }

    /**
     * 抓取远程图片
     */
    public function save_remote($fieldName, $maxSize = 5242880){
        $imgUrl = htmlspecialchars($fieldName);
        $imgUrl = str_replace("&amp;","&",$imgUrl);

        //http开头验证
        if(strpos($imgUrl,"http") !== 0){
            $data=array(
                'state' => '链接不是http链接',
            );
            return json_encode($data);
        }
        //获取请求头并检测死链
        $heads = get_headers($imgUrl);
        if(!(stristr($heads[0],"200") && stristr($heads[0],"OK"))){
            $data=array(
                'state' => '链接不可用',
            );
            return json_encode($data);
        }
        //格式验证(扩展名验证和Content-Type验证)
        if(preg_match("/^http(s?):\/\/mmbiz.qpic.cn\/(.*)/", $imgUrl) != 1){
            $allowFiles = [".png", ".jpg", ".jpeg", ".gif", ".bmp", ".ico", ".webp"];
            $fileType = strtolower(strrchr($imgUrl,'.'));
            if(!in_array($fileType, $allowFiles) || (isset($heads['Content-Type']) && stristr($heads['Content-Type'],"image"))){
                $data=array(
                    'state' => '链接contentType不正确',
                );
                return json_encode($data);
            }
        }

        //打开输出缓冲区并获取远程图片
        ob_start();
        $context = stream_context_create(
            array('http' => array(
                'follow_location' => false // don't follow redirects
            ))
        );
        readfile($imgUrl,false,$context);
        $img = ob_get_contents();
        ob_end_clean();
        preg_match("/[\/]([^\/]*)[\.]?[^\.\/]*$/",$imgUrl,$m);

        $dirname = './'.UPLOAD_PATH.'remote/'.date('Y/m/d').'/';
        $file['oriName'] = $m ? $m[1] : "";
        $file['filesize'] = strlen($img);
        $file['ext'] = strtolower(strrchr('remote.jpg','.'));
        $file['name'] = uniqid().$file['ext'];
        $file['fullName'] = $dirname.$file['name'];
        $fullName = $file['fullName'];

        //检查文件大小是否超出限制
        if($file['filesize'] >= $maxSize){
            $data=array(
                'state' => '文件大小超出网站限制',
            );
            return json_encode($data);
        }

        //创建目录失败
        if(!file_exists($dirname) && !mkdir($dirname,0777,true)){
            $data=array(
                'state' => '目录创建失败',
            );
            return json_encode($data);
        }else if(!is_writeable($dirname)){
            $data=array(
                'state' => '目录没有写权限',
            );
            return json_encode($data);
        }

        //移动文件
        if(!(file_put_contents($fullName, $img) && file_exists($fullName))){ //移动失败
            $data=array(
                'state' => '写入文件内容错误',
            );
            return json_encode($data);
        }else{ //移动成功
            $data=array(
                'state' => 'SUCCESS',
                'url' => substr($file['fullName'],1),
                'title' => $file['name'],
                'original' => $file['oriName'],
                'type' => $file['ext'],
                'size' => $file['filesize'],
            );
        }

        return json_encode($data);
    }
}

/**
 * 逻辑定义
 * Class WechatLogic
 */
class BWechatLogic extends Base
{
    private $savePath = 'weapp/';

    //上传文件
    public function upFile($fieldName, $fileSize = 2097152)
    {
        $file = request()->file($fieldName);
        if(empty($file)){
            return ['state' =>'请上传图片'];
        }
        $error = $file->getError();
        if(!empty($error)){
            return ['state' =>$error];
        }
        $formatBytesStr = format_bytes($fileSize);
        $result = $this->validate(
            ['file2' => $file], 
            ['file2'=>'image','file2'=>'fileSize:'.$fileSize],
            ['file2.image' => '上传文件必须为图片','file2.fileSize' => '上传文件超过了官方规定的'.$formatBytesStr]                
        );
        if (true !== $result || empty($file)) {
            $state = $result;
            return ['state' =>$state];
        }
        // 移动到框架应用根目录/uploads/ 目录下
        $this->savePath = $this->savePath.date('Ymd/');
        // 使用自定义的文件保存规则
        $info = $file->rule(function ($file) {
            return  md5(mt_rand());
        })->move(UPLOAD_PATH.$this->savePath);
        
        if($info){
            $data = array(
                'state' => 'SUCCESS',
                'url' => '/'.UPLOAD_PATH.$this->savePath.$info->getSaveName(),
                'title' => $info->getSaveName(),
                'original' => $info->getSaveName(),
                'type' => '.' . $info->getExtension(),
                'size' => $info->getSize(),
            );
        }else{
            $data = array('state' => $info->getError());
        }
        return $data;
    }
}
