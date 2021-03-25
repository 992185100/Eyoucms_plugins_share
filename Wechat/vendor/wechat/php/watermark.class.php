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
 * 水印
 */
class watermark extends base{

    public function __construct($config){
        parent::__construct($config);
    }

    /**
     * 图像添加图片水印
     *
     * @param  string  $im  图象资源
     * @param  string  $src_path   图片水印的路径名
     * @param  int  $src_w   宽度
     * @param  int  $src_h   高度
     * @param  int  $dst_x   横坐标
     * @param  int  $dst_y   纵坐标
     * @param  int  $pct 透明度
     *
     * @return $im
     */
    public function image($im, $src_path, $src_w, $src_h, $dst_x, $dst_y, $pct)
    {
        // 从字符串中的图像流新建一图像(水印)
        if ($this->is_remote_url($src_path)) {
            $img_sy = imagecreatefromstring($this->httpRequest($src_path));
        } else {
            $img_sy = imagecreatefromstring(file_get_contents($src_path));
        }
        // 临时保存远程图片水印到服务器本地(本地图片水印效率快很多)
        $file_tmp_sy = ROOT_PATH.'public/upload/tmp/'.date('Y/m/d/').md5(time().uniqid(mt_rand(), TRUE)).'.jpg';
        $this->fun_mkdir(dirname($file_tmp_sy));
        @imagejpeg($img_sy, $file_tmp_sy);
        if (!is_file($file_tmp_sy)) {
            return false;
        }
        $src_path = $file_tmp_sy;
        // 取得水印图像大小
        list($sy_width, $sy_hight, $sy_type) = @getimagesize($src_path);
        $src_w = $src_w > 0 ? $src_w : $sy_width;
        $src_h = $src_h > 0 ? $src_h : $sy_hight;
        // 删除临时图片水印文件
        @unlink($src_path);
        // 拷贝并合并图像的一部分
        imagecopymerge($im, $img_sy, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);

        // 销毁图像
        imagedestroy($img_sy);

        return $im;
    }

    /**
     * 添加水印
     *
     * @param  string  $src_path 水印图片路径
     * @param  int     $locate 水印位置
     * @param  int     $alpha  透明度
     * @return $this
     */
    public function water($im, $src_path, $src_w, $src_h, $locate, $alpha = 100, $info_bg = array())
    {
        // 从字符串中的图像流新建一图像(水印)
        if ($this->is_remote_url($src_path)) {
            $img_sy = imagecreatefromstring($this->httpRequest($src_path));
        } else {
            if (!is_file($src_path)) {
                return false;
            }
            $img_sy = imagecreatefromstring(file_get_contents($src_path));
        }
        // 临时保存远程图片水印到服务器本地(本地图片水印效率快很多)
        $file_tmp_sy = ROOT_PATH.'public/upload/tmp/'.date('Y/m/d/').md5(time().uniqid(mt_rand(), TRUE)).'.jpg';
        $this->fun_mkdir(dirname($file_tmp_sy));
        @imagejpeg($img_sy, $file_tmp_sy);
        if (!is_file($file_tmp_sy)) {
            return false;
        }
        $src_path = $file_tmp_sy;
        // 取得水印图像大小
        list($sy_width, $sy_hight, $sy_type) = @getimagesize($src_path);
        $src_w = $src_w > 0 ? $src_w : $sy_width;
        $src_h = $src_h > 0 ? $src_h : $sy_hight;
        // 删除临时图片水印文件
        @unlink($src_path);

        //设定水印图像的混色模式
        imagealphablending($img_sy, true);
        /* 设定水印位置 */
        switch ($locate) {
            /* 右下角水印 */
            case 1:
                $x = $info_bg['width'] - $src_w;
                $y = $info_bg['height'] - $src_h;
                break;
            /* 左下角水印 */
            case 2:
                $x = 0;
                $y = $info_bg['height'] - $src_h;
                break;
            /* 左上角水印 */
            case 3:
                $x = $y = 0;
                break;
            /* 右上角水印 */
            case 4:
                $x = $info_bg['width'] - $src_w;
                $y = 0;
                break;
            /* 居中水印 */
            case 5:
                $x = ($info_bg['width'] - $src_w) / 2;
                $y = ($info_bg['height'] - $src_h) / 2;
                break;
            /* 下居中水印 */
            case 6:
                $x = ($info_bg['width'] - $src_w) / 2;
                $y = $info_bg['height'] - $src_h;
                break;
            /* 右居中水印 */
            case 7:
                $x = $info_bg['width'] - $src_w;
                $y = ($info_bg['height'] - $src_h) / 2;
                break;
            /* 上居中水印 */
            case 8:
                $x = ($info_bg['width'] - $src_w) / 2;
                $y = 0;
                break;
            /* 左居中水印 */
            case 9:
                $x = 0;
                $y = ($info_bg['height'] - $src_h) / 2;
                break;
            default:
                /* 自定义水印坐标 */
                if (is_array($locate)) {
                    list($x, $y) = $locate;
                } else {
                    return false;
                }
        }
        //添加水印
        $src = imagecreatetruecolor($src_w, $src_h);
        // 调整默认颜色
        $color = imagecolorallocate($src, 255, 255, 255);
        imagefill($src, 0, 0, $color);
        imagecopy($src, $im, 0, 0, $x, $y, $src_w, $src_h);
        imagecopy($src, $img_sy, 0, 0, 0, 0, $src_w, $src_h);
        imagecopymerge($im, $src, $x, $y, 0, 0, $src_w, $src_h, $alpha);
        //销毁零时图片资源
        imagedestroy($src);
        //销毁水印资源
        imagedestroy($img_sy);

        return $im;
    }

    /**
     * 图像添加文字水印
     *
     * @param  string  $im  图象资源
     * @param  array   $info_bg   背景图像的信息(宽度、高度)
     * @param  string  $text   添加的文字
     * @param  string  $font   字体路径
     * @param  integer $size   字号
     * @param  string  $color  文字颜色
     * @param  int     $locate 文字写入位置
     * @param  integer $offset 文字相对当前位置的偏移量
     * @param  integer $angle  文字倾斜角度
     *
     * @return $im
     */
    public function text($im, $info_bg, $text, $font, $size, $color = '#00000000', $locate = 9, $offset = 0, $angle = 0)
    {
        $font = FONT_PATH.$font;
        if (empty($font) || !is_file($font)) {
            $font = FONT_PATH.'hgzb.ttf';
        }
        //获取文字信息
        $info = imagettfbbox($size, $angle, $font, $text); // 取得使用 TrueType 字体的文本的范围
        $minx = min($info[0], $info[2], $info[4], $info[6]); // 找出最小横坐标(即文本左边框所距离的宽度)
        $maxx = max($info[0], $info[2], $info[4], $info[6]); // 找出最大横坐标(即文本右边框所距离的宽度)
        $miny = min($info[1], $info[3], $info[5], $info[7]); // 找出最小纵坐标(即文本上边框所距离的高度)
        $maxy = max($info[1], $info[3], $info[5], $info[7]); // 找出最大纵坐标(即文本上边框所距离的高度)
        /* 计算文字初始坐标和尺寸 */
        $x = $minx; // 文本边框所在的最初横坐标
        $y = abs($miny); // 文本边框所在的最初的纵坐标
        $w = $maxx - $minx; // 文本的宽度
        $h = $maxy - $miny; // 文本的高度
        /* 设定文字位置 */
        switch ($locate) {
            /* 右下角文字 */
            case 1:
                $x += $info_bg['width'] - $w;
                $y += $info_bg['height'] - $h;
                break;
            /* 左下角文字 */
            case 2:
                $y += $info_bg['height'] - $h;
                break;
            /* 左上角文字 */
            case 3:
                // 起始坐标即为左上角坐标，无需调整
                break;
            /* 右上角文字 */
            case 4:
                $x += $info_bg['width'] - $w;
                break;
            /* 居中文字 */
            case 5:
                $x += ($info_bg['width'] - $w) / 2;
                $y += ($info_bg['height'] - $h) / 2;
                break;
            /* 下居中文字 */
            case 6:
                $x += ($info_bg['width'] - $w) / 2;
                $y += $info_bg['height'] - $h;
                break;
            /* 右居中文字 */
            case 7:
                $x += $info_bg['width'] - $w;
                $y += ($info_bg['height'] - $h) / 2;
                break;
            /* 上居中文字 */
            case 8:
                $x += ($info_bg['width'] - $w) / 2;
                break;
            /* 左居中文字 */
            case 9:
                $y += ($info_bg['height'] - $h) / 2;
                break;
            /* 私用的博鳌海报 */
            case 10:
                $x += ($info_bg['width'] - $w) / 2;
                $y += $info_bg['height'];
                break;
            default:
                /* 自定义文字坐标 */
                if (is_array($locate)) {
                    list($posx, $posy) = $locate;
                    $x += $posx;
                    $y += $posy;
                }
        }
        /* 设置偏移量 */
        if (is_array($offset)) {
            $offset        = array_map('intval', $offset);
            list($ox, $oy) = $offset;
        } else {
            $offset = intval($offset);
            $ox     = $oy     = $offset;
        }
        /* 设置颜色 */
        if (is_string($color) && 0 === strpos($color, '#')) {
            $color = str_split(substr($color, 1), 2);
            $color = array_map('hexdec', $color);
            if (empty($color[3]) || $color[3] > 127) {
                $color[3] = 0;
            }
        } else if (!is_array($color)) {
            return '错误的颜色值';
        }

        /* 写入文字 */
        $col = imagecolorallocatealpha($im, $color[0], $color[1], $color[2], $color[3]);
        imagettftext($im, $size, $angle, $x + $ox, $y + $oy, $col, $font, $text);
        
        return $im;
    }

    /**
     * 保存图像
     * @param string      $im  图象资源
     * @param string      $pathname  图像保存路径名称
     * @param int         $quality   图像质量
     * @param bool        $interlace 是否对JPEG类型图像设置隔行扫描
     * @return $im 图象资源
     */
    public function save($im = '', $pathname = '', $quality = 80, $interlace = true)
    {
        //获取图像类型
        $result = array();
        $pattern = '/\.([a-zA-Z]+)$/i';
        preg_match($pattern, $pathname, $result);
        if(empty($result)){
            return false;
        } else{
            $type = strtolower($result[1]);
        }
        //保存图像
        if ('jpeg' == $type || 'jpg' == $type) {
            $type = 'jpeg';
            //JPEG图像设置隔行扫描
            imageinterlace($im, $interlace);
            imagejpeg($im, $pathname, $quality);
        } elseif ('gif' == $type) {
            return false;
        } elseif ('png' == $type) {
            //设定保存完整的 alpha 通道信息
            imagesavealpha($im, true);
            //ImagePNG生成图像的质量范围从0到9的
            imagepng($im, $pathname, min((int) ($quality / 10), 9));
        } else {
            $fun = 'image' . $type;
            @$fun($im, $pathname);
        }

        return $im;
    }
}