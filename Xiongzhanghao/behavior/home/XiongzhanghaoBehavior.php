<?php

namespace weapp\Xiongzhanghao\behavior\home;

use think\Db;
use think\Request;
use app\common\model\Weapp;
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
        !isset(self::$method) && self::$method = strtoupper(request()->method());
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
        /*只有相应的内容详情页的控制器和操作名才执行，以便提高性能*/
        $ctlActArr = array(
            'Index@index',//article_content表
            'Article@view',//article_content表
            'Product@view',//product_content表
            'Images@view',//images_content表
            'Download@view',//download_content表
            'View@index',//article_content表product_content表images_content表download_content表
            'Article@lists',//article_content表
            'Product@lists',//product_content表
            'Images@lists',//images_content表
            'Download@lists',//download_content表
            'Single@lists',//single_content表
            'Lists@index',//single_content表
            'Buildhtml@*',// 支持生成静态HTML
        );
        $ctlActStr = self::$controllerName.'@'.self::$actionName;
        $ctlAllStr = self::$controllerName.'@*';
        if (in_array($ctlActStr, $ctlActArr) || in_array($ctlAllStr, $ctlActArr)) {

            // 获取插件信息
            $weapp = Weapp::get(array('code' => 'Xiongzhanghao'));
            if (empty($weapp)) {
                return true;
            }
            // 获取插件配置信息
            $weappConfig = json_decode($weapp->data, true);

            // 判断插件是否启用
            if (1 !== intval($weapp->status) || empty($weappConfig['appid']) || empty($weappConfig['token'])) {
                return true;
            }

            // 
            $request = Request::instance();
            $tid = $request->param('tid');
            $aid = $request->param('aid');
            if(!empty($aid)){ // 针对文档
                //获取文章信息
                $row = Db::name('archives')->find($aid);
                if (empty($row)) {
                    return true;
                }
                $arctypeRow = Db::name('arctype')->field('*')->where('id', $row['typeid'])->find();
                $row['title'] = set_arcseotitle($row['title'], $row['seo_title'], $arctypeRow['typename']);
                $row = array_merge($arctypeRow, $row);
            } else if (!empty($tid)) { // 针对栏目
                if (strval(intval($tid)) != strval($tid)) {
                    $map = array('dirname'=>$tid);
                } else {
                    $map = array('id'=>$tid);
                }
                $row = Db::name('arctype')->where($map)->find();
                if (empty($row)) {
                  return true;
                }
                $row['title'] = set_typeseotitle($row['typename'], $row['seo_title']);
            } else if ('Index@index' == $ctlActStr || 'Buildhtml@*' == $ctlAllStr) { // 首页
                $row = [];
                $row['litpic'] = get_default_pic(tpCache('web.web_logo'), $request->domain());
                $row['title'] = tpCache('web.web_title');
                $row['update_time'] = time();
            } else {
                return true;
            }

            $url = '';
            static $seo_pseudo = null;
            null === $seo_pseudo && $seo_pseudo = config('ey_config.seo_pseudo');
            if (2 == $seo_pseudo) {
                if(!empty($aid)){
                    $url = arcurl("home/View/index", $row, true, true, 2);
                } else if (!empty($tid)) {
                    $url = typeurl("home/Lists/index", $row, true, true, 2);
                } else if ('Index@index' == $ctlActStr || 'Buildhtml@*' == $ctlAllStr) {
                    $url = $request->domain().ROOT_DIR;
                }
            } else {
                $url = $request->url(true);
            }

            $template = '
              <link rel="canonical" href="'.$url.'"/>
              <script src="//msite.baidu.com/sdk/c.js?appid='.$weappConfig['appid'].'"></script>
              <script type="application/ld+json">
                  {
                      "@context": "https://ziyuan.baidu.com/contexts/cambrian.jsonld",
                      "@id": "'.$url.'",
                      "appid": "'.$weappConfig['appid'].'",
                      "title": "'.$row['title'].'",
                      "images": [
                          "'.get_default_pic($row['litpic'], true).'"
                      ],
                      "pubDate": "'.date('Y-m-d\TH:i:s', $row['update_time']).'"
                  }
              </script>
              </head>';
            //替换内容详情
            $params = str_ireplace('</head>', $template, $params);
        }
    }

    /**
     * 应用结束
     * @param array $params 传入参数
     * @access public
     */
    public function appEnd(&$params)
    {

    }
}