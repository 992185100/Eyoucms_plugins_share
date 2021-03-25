<?php

namespace weapp\TimingTask\behavior\home;

/**
 * 行为扩展
 */
class TimingTaskBehavior
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
        !isset(self::$method) && self::$method = strtoupper(request()->method());
    }


    /**
     * 视图内容过滤
     * @param array $params 传入参数
     * @access public
     */
    public function viewFilter(&$params)
    {
        $this->timeToSendArticle($params);//定时文章发布
    }

    /**
     * 文章定时发布
     */
    private function timeToSendArticle(&$params)
    {
        $root_dir = ROOT_DIR;
        $JsHtml = <<<EOF
<script type="text/javascript">
    function task_1575886775()
    {
        //步骤一:创建异步对象
        var ajax = new XMLHttpRequest();
        //步骤二:设置请求的url参数,参数一是请求的类型,参数二是请求的url,可以带参数,动态的传递参数starName到服务端
        ajax.open("post", "{$root_dir}/index.php?m=plugins&c=TimingTask&a=time_to_send_article", true);
        // 给头部添加ajax信息
        ajax.setRequestHeader("X-Requested-With","XMLHttpRequest");
        // 如果需要像 HTML 表单那样 POST 数据，请使用 setRequestHeader() 来添加 HTTP 头。然后在 send() 方法中规定您希望发送的数据：
        ajax.setRequestHeader("Content-type","application/x-www-form-urlencoded");
        //步骤三:发送请求+数据
        ajax.send('_ajax=1');
        //步骤四:注册事件 onreadystatechange 状态改变就会调用
        ajax.onreadystatechange = function () {
            //步骤五 如果能够进到这个判断 说明 数据 完美的回来了,并且请求的页面是存在的
            if (ajax.readyState==4 && ajax.status==200) {
            }
        } 
    }
    task_1575886775();
</script>
EOF;
        // 追加替换JS
        $params = str_ireplace('</body>', $JsHtml . "\n</body>", $params);
    }




}