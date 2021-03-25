<?php

namespace weapp\Clicap\behavior\admin;

use weapp\Clicap\model\ClicapModel;

class ClicapBehavior
{
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
        if (self::$method == 'GET')
        {
            if ('admin' == self::$moduleName && 'Admin' == self::$controllerName && 'login' == self::$actionName) {
                $clicapModel = new ClicapModel;
                $clicap = $clicapModel->getWeappData();

                $is_clicap = 0; // 默认关闭文字验证码
                if (!empty($clicap['data']) && $clicap['data']['captcha']['admin_login']['is_on'] == 1) {
                    $is_clicap = 1; // 符合开启的条件
                }

                if (1 == $is_clicap) {
                    $configData = json_decode($clicap['config'], true);
                    $version = $configData['version'];

                    $input = <<<EOF
<div  class="form-control clicap-center" id="click-clicap-create">
    <i class="icon iconfont" >&#xe62c;</i>&nbsp;点击验证
</div>
<input type="hidden" id="clicaptcha-submit-info" name="clicaptcha-submit-info">
EOF;
                    $params = str_ireplace('<!-- weapp_clicap_input -->', $input, $params);

                    $root_dir = ROOT_DIR;
                    $css = <<<EOF
<link rel="stylesheet" type="text/css" href="{$root_dir}/weapp/Clicap/template/skin/css/captcha.css?v={$version}" />
<link rel="stylesheet" type="text/css" href="{$root_dir}/weapp/Clicap/template/skin/css/iconfont.css?v={$version}" />
<script type="text/javascript">var __root_dir__ = '{$root_dir}';</script>
<script type="text/javascript" src="{$root_dir}/weapp/Clicap/template/skin/js/clicaptcha.js?v={$version}"></script>
<script type="text/javascript" src="{$root_dir}/weapp/Clicap/template/skin/js/clicap.js?v={$version}"></script>
</head>
EOF;
                    $params = str_ireplace('</head>', $css, $params);

                }
            }
        }
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