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

namespace weapp\Minipro0002\model;

use think\Model;

/**
 * 模型
 */
class Minipro0002Model extends Model
{
    /**
     * 数据表名，不带前缀
     */
    public $name = 'weapp_minipro0002';

    /**
     * 小程序配置信息的type值
     */
    public $miniproType = 'minipro';

    //初始化
    protected function initialize()
    {
        // 需要调用`Model`的`initialize`方法
        parent::initialize();
    }

    /**
     * 获取配置记录
     * @param string $type 类型
     * @author 小虎哥 by 2018-8-18
     */
    public function getRow($type)
    {
        $map = array(
            'type'  => $type,
        );
        $row = $this->where($map)->find();
        if (!empty($row)) {
            $row['value'] = (array)json_decode($row['value'], true);
        }

        return $row;
    }

    /**
     * 获取配置值
     * @param string $type 类型
     * @author 小虎哥 by 2018-8-18
     */
    public function getValue($type)
    {
        $row = $this->getRow($type);
        $value = array();
        if (!empty($row)) {
            $value = $row['value'];
        }

        return $value;
    }

    /**
     * 设置配置值
     * @param string $type 类型
     * @author 小虎哥 by 2018-8-18
     */
    public function setValue($type, $value)
    {
        $row = $this->getRow($type);
        if (empty($row)) {
            $data = array(
                'type' => $type,
                'value' => json_encode($value),
                'add_time' => getTime(),
            );
            $r = $this->insert($data);
        } else {
            $data = array(
                'type' => $type,
                'value' => json_encode($value),
                'update_time' => getTime(),
            );
            $r = $this->where('type','eq',$type)->update($data);
        }
        if (false !== $r) {
            return true;
        }

        return false;
    }
}