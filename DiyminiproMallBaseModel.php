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

namespace weapp\DiyminiproMall\model;

use think\Db;
use think\Model;
use think\Request;

/**
 * 小程序基类模型
 */
class DiyminiproMallBaseModel extends Model
{
    /**
     * 当前Request对象实例
     * @var null
     */
    public static $request = null; // 当前Request对象实例

    /**
     * 小程序风格ID
     * @var null
     */
    public static $mini_id = null;

    /**
     * 语言标识
     */
    public static $lang = 'cn';

    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
        self::$lang = get_current_lang();
        self::$mini_id = input('param.mini_id/d');
        if (empty(self::$mini_id)) {
            // 不传mini_id，默认显示最新的模板
            self::$mini_id = Db::name('weapp_diyminipro_mall')->where([
                'is_del'        => 0,
                'lang'          => self::$lang
            ])->order('mini_id desc')->getField('mini_id');
            // 数据被清空
            if (empty(self::$mini_id)) {
                if (!stristr(request()->domain(), 'service.eyysz.cn')) {
                    self::$mini_id = $this->init_theme_add();
                }
            }
        }

        null === self::$request && self::$request = Request::instance();
    }

    /**
     * 没有模板数据时，自动补充一套模板
     */
    private function init_theme_add()
    {
        $config = [];
        // --存储数据
        $data = [
            'categoryid'    => 1,
            'name'  => '默认商城风格',
            'litpic'    => 'https://service.eyysz.cn/uploads/allimg/20200514/1-200514103G4249.png',
            'component'   => '8d9bBlFRVQYIVgkJAg5VAAcEAVNWAVdWWVNSUwZUVQwLVEAaFggLUlcRFVhATAwFDlcVQF0BBglNFwQEEFVcTgteRl8CBElYWRB7WEAUFw8BWm1TTBFPFQQWFwwBUxgACVBcXU0GEF9cAxVeXVcBFQ',
            'mchid' => '',
            'apikey' => '',
            'cloud_id'  => 2,
            'config'    => '',
            'lang'  => self::$lang,
            'add_time'    => getTime(),
            'update_time'    => getTime(),
        ];
        $mini_id = Db::name('weapp_diyminipro_mall')->insertGetId($data);
        if (false !== $mini_id) {
            try {
                $this->after_theme_add($mini_id);
            } catch (\Exception $e) {
                Db::name('weapp_diyminipro_mall')->where(['mini_id'=>$mini_id])->delete();
            }
            \think\Cache::clear('weapp_diyminipro_mall');
        }

        return intval($mini_id);
    }
}