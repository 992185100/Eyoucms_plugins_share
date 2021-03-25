<?php

namespace weapp\Ask\behavior\plugins;

use think\Db;

load_trait('controller/Jump');
class AskBehavior
{
    use \traits\controller\Jump;
    protected static $actionName;
    protected static $controllerName;
    protected static $moduleName;
    protected static $method;

    /**
     * 构造方法
     *
     * @param Request $request Request对象
     *
     * @access public
     */
    public function __construct()
    {
        ! isset(self::$moduleName) && self::$moduleName = request()->module();
        ! isset(self::$controllerName) && self::$controllerName = request()->controller();
        ! isset(self::$actionName) && self::$actionName = request()->action();
        ! isset(self::$method) && self::$method = strtoupper(request()->method());
    }

    /**
     * 模块初始化
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function moduleInit(&$params)
    {
        if (isMobile() && file_exists('./template/plugins/ask/mobile/')) {
            !defined('THEME_STYLE') && define('THEME_STYLE', 'mobile'); // mobile端模板
        } else {
            !defined('THEME_STYLE') && define('THEME_STYLE', 'pc'); // pc端模板
        }
    }

    /**
     * 操作开始执行
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function actionBegin(&$params)
    {
        
    }

    /**
     * 视图内容过滤
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function viewFilter(&$params)
    {

    }

    /**
     * 应用结束
     *
     * @param array $params 传入参数
     *
     * @access public
     */
    public function appEnd(&$params)
    {
        $this->clearHtmlCache();
    }

    /**
     * 数据变动之后，清理页面和数据缓存
     */
    private function clearHtmlCache()
    {
        /*在以下相应的控制器和操作名里执行，以便提高性能*/
        if ('POST' == self::$method) {
            try {
                $actArr = ['add_ask','edit_ask','DelAsk','ajax_best_answer','ajax_add_answer','ajax_del_answer','DelAsk','ajax_click_like','ajax_review_ask','ajax_review_comment'];
                foreach ($actArr as $key => $val) {
                    if (in_array(self::$actionName, $actArr)) {
                        foreach ([HTML_ROOT,CACHE_PATH] as $k2 => $v2) {
                            delFile($v2);
                        }
                        break;
                    }
                }
            } catch (\Exception $e) {}
        }
    }
}