<?php

namespace weapp\Xiongzhanghao\behavior\admin;

use think\Request;
use app\home\model\Archives;
use weapp\Xiongzhanghao\model\XiongzhanghaoModel;

/**
 * 行为扩展
 */
class XiongzhanghaoBehavior
{
    protected static $actionName;
    protected static $controllerName;
    protected static $moduleName;
    protected static $method;

    /**
     * 构造方法
     * @param Request $request Request对象
     * @access public
     */
    public function __construct()
    {
        !isset(self::$moduleName) && self::$moduleName = request()->module();
        !isset(self::$controllerName) && self::$controllerName = request()->controller();
        !isset(self::$actionName) && self::$actionName = request()->action();
        !isset(self::$method) && self::$method = request()->method();
    }

    /**
     * 模块初始化
     * @param array $params 传入参数
     * @access public
     */
    public function moduleInit(&$params)
    {

    }

    /**
     * 操作开始执行
     * @param array $params 传入参数
     * @access public
     */
    public function actionBegin(&$params)
    {

    }

    /**
     * 视图内容过滤
     * @param array $params 传入参数
     * @access public
     */
    public function viewFilter(&$params)
    {

    }

    /**
     * 应用结束
     * @param array $params 传入参数
     * @access public
     */
    public function appEnd(&$params)
    {
        /*只有相应的控制器和操作名才执行，以便提高性能*/
        $channeltype_list = config('global.channeltype_list');
        $ctlName = strtolower(self::$controllerName);
        $actArr = ['add','edit'];
        if ('POST' == self::$method && !empty($channeltype_list[$ctlName]) && in_array(self::$actionName, $actArr)) {
            // 获取文章aid
            $aid = isset($_POST['aid']) ? $_POST['aid'] : 0;
            
            // 获取文章及栏目信息
            $res = Archives::field('b.*, a.*, a.aid, b.id as typeid')
                ->alias('a')
                ->join('__ARCTYPE__ b', 'b.id = a.typeid', 'LEFT')
                ->where([
                    'a.aid' => $aid,
                    'a.arcrank'=> ['gt',-1],
                ])
                ->find();
            if(empty($res)){
                return true;
            }
            $res = $res->toArray();

            // 生成文章url
            $arcurl = get_arcurl($res, false);

            // 推送到熊掌号
            $act = self::$actionName;
            XiongzhanghaoModel::pullUlr($arcurl, $act);
        }
    }
}