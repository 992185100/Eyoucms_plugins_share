<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: King超 <23939139@qq.com>
 * Date: 2018-11-13
 */

namespace weapp\Systemdoctor\model;

use think\Model;

/**
 * 模型
 */
class AdminModel extends Model
{
    protected $pk = 'admin_id';

    // 数据表名，不带前缀
    public $name = 'admin';

}