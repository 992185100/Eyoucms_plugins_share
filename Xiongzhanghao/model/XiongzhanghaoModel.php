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

namespace weapp\Xiongzhanghao\model;

use think\Model;
use app\common\model\Weapp;

/**
 * 模型
 */
class XiongzhanghaoModel extends Model
{
    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }

    /**
     * 资源提交
     * @param  string|array  $urls   链接地址可为数组
     * @param  string        $action 提交方式[add|edit]
     * @return json
     */
    public static function pullUlr($url, $action = 'add')
    {   
        // 判断新增还是更新
        switch ($action) {
            case 'add':
                $type = 'realtime';
                break;
            case 'edit':
                $type = 'batch';
                break;
            
            default:
                $type = 'realtime';
                break;
        }

        // url类型判断，可为数组批量提交
        if(is_array($url)){
            $urls = $url;
        }else{
            $urls[] = $url;
        }

        // 获取插件信息
        $weapp = Weapp::get(array('code' => 'Xiongzhanghao'));
        $weappConfig = json_decode($weapp->getData('data'), true);

        // 判断插件是否启用
        if (1 !== intval($weapp->status)) {
            return true;
        }

        $sitemap_zzbaidutoken = !empty($weappConfig['sitemap_zzbaidutoken']) ? $weappConfig['sitemap_zzbaidutoken'] : config('tpcache.sitemap_zzbaidutoken');
        $sitemap_zzbaidutoken = trim($sitemap_zzbaidutoken);
        if (!empty($sitemap_zzbaidutoken)) {
            if (is_http_url($sitemap_zzbaidutoken)) {
                $api = $sitemap_zzbaidutoken;
            } else {
                $api = "http://data.zz.baidu.com/urls?site=".request()->domain()."/&token={$sitemap_zzbaidutoken}&type=daily";
            }
        } else if (!empty($weappConfig['appid']) && !empty($weappConfig['token'])) {
            // 推送到熊掌号
            $api = 'http://data.zz.baidu.com/urls?appid='.$weappConfig['appid'].'&token='.$weappConfig['token'].'&type='.$type;
        } else {
            return true;
        }

        $ch = curl_init();
        $options =  array(
            CURLOPT_URL => $api,
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => implode("\n", $urls),
            CURLOPT_HTTPHEADER => array('Content-Type: text/plain'),
        );
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);

        $arr = json_decode($result, true);
        if(isset($arr['error'])){
            adminLog('【快速收录】推送失败：'.$arr['message'].'，具体URL：'.join(',', $urls)); // 写入操作日志
        }else{
            adminLog('【快速收录】推送成功，具体URL：'.join(',', $urls)); // 写入操作日志
        }

        return $result;
    }

}