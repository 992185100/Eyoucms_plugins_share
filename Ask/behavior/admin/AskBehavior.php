<?php

namespace weapp\Ask\behavior\admin;

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
        $sm = input('param.sm/s');
        if ('Ask' == $sm) {
            $key = 'd2ViLndlYl9pc19hdXRob3J0b2tlbg==';
            $value = tpCache(base64_decode($key));
            $domain = request()->host();
            $server_ip = gethostbyname($_SERVER["SERVER_NAME"]);
            if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i', $domain) || 'localhost' == $domain || '127.0.0.1' == $server_ip || -1 != $value) {

            } else {
                $str = '6Z2e5o6I5p2D572R56uZ56aB5q2i5L2/55So6Zeu562U5o+S5Lu277yMPGEgaHJlZj0iaHR0cDovL3d3dy5leW91Y21zLmNvbS9idXkvIj7or7fngrnlh7vov5nph4w8L2E+77yM6LSt5Lmw5ZWG5Lia5o6I5p2D77yB';
                echo (base64_decode($str));
                // exit;
            }
        }
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

    }
}