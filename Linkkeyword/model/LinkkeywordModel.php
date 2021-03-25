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

namespace weapp\Linkkeyword\model;

use think\Model;

/**
 * 模型
 */
class LinkkeywordModel extends Model
{

    /**
     * 数据表名，不带前缀
     */
    public $name = 'weapp_linkkeyword';

    const STATUS_ENABLE = 1;//关键字启用
    const STAUTS_DISABLE = 0;//关键字禁用
    const WEAPP_STATUS_ENABLE = 1;//本插件启用
    const WEAPP_STATUS_DISABLE = 0;//本插件禁用
    const WEAPP_CODE = 'Linkkeyword';//插件标识，对应weapp表里的code字段
    const TABEL_NAME = 'weapp_linkkeyword';//表名

    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }

    /**
     * 获取关键字和链接列表
     */
    public function getList()
    {
        /*有页面缓存，页面缓存清除时才会走这里的查询，为了节省添加、编辑、删除等操作时清除查询缓存的操作，这个查询就不做缓存处理了*/
        /*->->cache(true, EYOUCMS_CACHE_TIME, self::TABEL_NAME)*/
        $list = self::field('title,target,url')->where('status', self::STATUS_ENABLE)->order('id', 'asc')->select();

        return $list;
    }
}