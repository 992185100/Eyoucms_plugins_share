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
 * Date: 2018-06-28
 */

namespace weapp\Sysdatareplace\controller;

use think\Config;
use think\Db;
use app\common\controller\Weapp;
use think\Request;
use weapp\Sysdatareplace\model\SysdatareplaceModel;

/**
 * 插件的控制器
 */
class Sysdatareplace extends Weapp
{
    /**
     * 实例化模型
     */
    private $model;

    /**
     * 实例化对象
     */
    private $db;

    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $this->model = new SysdatareplaceModel;
        $this->db = db('WeappSysdatareplace');

        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    /**
     * 插件使用指南
     */
    public function doc(){
        return $this->fetch('doc');
    }


    /**
     * 插件后台管理 - 列表
     */
    public function index()
    {
        $keywords = I('keywords/s');

        $map = array();
        if (!empty($keywords)) {
            $map['title'] = array('LIKE', "%{$keywords}%");
        }
        //获取数据表
        $dbtables = Db::query('SHOW TABLE STATUS');
        $list = array();
        foreach ($dbtables as $k => $v) {
            if (preg_match('/^'.PREFIX.'/i', $v['Name'])) {
                $list[$k] = $v;
            }
        }
        $tables = get_arr_column($list, 'Name');

        $this->assign('tables',$tables);
        return $this->fetch('index');
    }

    /**
     * 根据表名获取字段列表
     */
    public function getTableField()
    {
        $name = Request::instance()->param('table_name');
        $fieldArr = Db::getTableFields($name);
        if($fieldArr)
        {
            $this->success("操作成功!",'',['targetTable'=>$name,'fields'=>$fieldArr]);
        }
    }

    /**
     *内容替换主方法
     */
    public function th()
    {
        $data = Request::instance()->param();
        //字段安全过滤
        $field = $this->filter($data['rpfield']);
        switch($data['rptype'])
        {
            case "replace":
                $this->replace($data['tables'],$field,$data['rpstring'],$data['tostring'],$data['condition']);
                break;
            case "regex":
                $this->regex();
                break;
        }
    }

    /**
     * 普通替换
     */
    public function replace($table,$field,$rpstring,$tostring,$condition){
        if($condition)
        {
            $sql = "update {$table} set {$field}=REPLACE({$field},'{$rpstring}','{$tostring}') where $condition;";

        }else{
            $sql = "update {$table} set {$field}=REPLACE({$field},'{$rpstring}','{$tostring}');";
        }
        $res = Db::execute($sql);
        if ($res)
        {
            $this->success("普通替换成功,{$res}行受到影响");
        }else{
            $this->error("替换未成功,没有受到任何影响");
        }
    }

    /**
     * 正则替换
     */
    public function regex(){
        $this->success("正则替换");
    }

    /**
     * 过滤掉重要字段
     */
    public function filter($field)
    {
        $ban = ['id'];
        for($i=0; $i<count($ban); $i++){
            if(in_array($field,$ban)){
                $this->error("存在非法字段,不可替换");
            }
        }
        return $field;
    }

    
    /**
     * 插件配置
     */
    public function conf()
    {
        if (IS_POST) {
            $post = I('post.');
            if(!empty($post['code'])){
                $data = array(
                    'tag_weapp' => $post['tag_weapp'],
                    'update_time' => getTime(),
                );
                $r = M('weapp')->where('code','eq',$post['code'])->update($data);
                if ($r) {
                    \think\Cache::clear('hooks');
                    adminLog('编辑'.$this->weappInfo['name'].'：插件配置'); // 写入操作日志
                    $this->success("操作成功!", weapp_url('Sysdatareplace/Sysdatareplace/conf'));
                }
            }
            $this->error("操作失败!");
        }

        $row = M('weapp')->where('code','eq','Sysdatareplace')->find();
        $this->assign('row', $row);

        return $this->fetch('conf');
    }
}