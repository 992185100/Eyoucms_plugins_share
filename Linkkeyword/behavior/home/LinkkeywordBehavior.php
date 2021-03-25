<?php

namespace weapp\Linkkeyword\behavior\home;

use weapp\Linkkeyword\model\LinkkeywordModel;
use think\Db;

class LinkkeywordBehavior
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
        if (!stristr($params, '</head>')) {
            return true;
        }

        /*只有相应的内容详情页的控制器和操作名才执行，以便提高性能*/
        $ctlActArr = array(
            'Article@view',//article_content表
            'Product@view',//product_content表
            'Images@view',//images_content表
            'Download@view',//download_content表
            'Single@lists',//single_content表
            'Lists@index',//single_content表
            'View@index',//article_content表product_content表images_content表download_content表
            'Buildhtml@*',// 支持生成静态HTML
        );
        $ctlActStr = self::$controllerName.'@'.self::$actionName;
        $ctlAllStr = self::$controllerName.'@*';
        if (in_array($ctlActStr, $ctlActArr) || in_array($ctlAllStr, $ctlActArr)) {

            //判断插件是否启用
            $weappOpen = Db::name('weapp')->where(array(
                'code'   => LinkkeywordModel::WEAPP_CODE,
                'status' => LinkkeywordModel::WEAPP_STATUS_ENABLE,
            ))->value('id');
            if ($weappOpen > 0) {
                $aid = request()->param('aid', 0);
                $tid = request()->param('tid', 0);
                $content = '';
                $title = '';
                try {
                    if (0 < $aid) {
                        //有aid需要判断内容频道类型，获取内容的频道类型
                        $archivesInfo = Db::name('archives')->field('channel,title')->where('aid', $aid)->find();
                        if (!empty($archivesInfo['channel'])) {
                            $title = $archivesInfo['title'];
                            //获取该频道内容的表名
                            $table = Db::name('channeltype')->where([
                                'id'     => $archivesInfo['channel'],
                                'status' => LinkkeywordModel::WEAPP_STATUS_ENABLE,
                            ])->value('table');
                            //拼接内容详情的表名
                            $contentModel = $table.'_content';
                            //获取内容详情
                            $content = Db::name($contentModel)->where('aid', $aid)->value('content');
                        }
                    } else {
                        if (strval(intval($tid)) != strval($tid)) {
                            $map = array('dirname'=>$tid);
                        } else {
                            $map = array('id'=>$tid);
                        }
                        $arctypeInfo = M('arctype')->field('id,typename')->where($map)->find();
                        if (!empty($arctypeInfo)) {
                            $title = $arctypeInfo['typename'];
                            //没有aid的是单篇内容，单篇内容详情表名是single_content
                            $contentModel = 'single_content';
                            //获取内容详情
                            $content = Db::name($contentModel)->where('typeid', $arctypeInfo['id'])->value('content');
                        }
                    }
                } catch (\Exception $e) {}

                if (empty($content)) {
                    return true;
                }

                //内容详情解码
                $content    = htmlspecialchars_decode($content);

                /*处理每个插件获取的内容应当是处理过的内容 - 【插件涉及到内容替换必备代码】*/
                // 追加指定内嵌样式到编辑器内容的img标签，兼容图片自动适应页面
                $titleNew = !empty($archivesInfo['title']) ? $archivesInfo['title'] : '';
                $content = img_style_wh($content, $titleNew);
                $content = handle_subdir_pic($content, 'html');
                $session_key = md5("weapp_archives_content");
                if (false === stripos($params, $content)) {
                    $content_cache = cache($session_key);
                    !empty($content_cache) && $content = $content_cache;
                }
                /*end*/

                /*追加指定内嵌样式到编辑器内容的img标签，兼容图片自动适应页面*/
                $titleNew = !empty($archivesInfo['title']) ? $archivesInfo['title'] : '';
                $content = img_style_wh($content, $titleNew);
                $content = handle_subdir_pic($content, 'html');
                /*--end*/
                $contentNew = $content;

                //查询关键字和链接
                $linkkeywordModel = new LinkkeywordModel;
                $linkKeyword      = $linkkeywordModel->getList();

                preg_match_all('/<img.*(\/)?>/iUs', $contentNew, $imginfo);
                $imginfo = $imginfo[0];
                if ($imginfo) {
                    foreach ($imginfo as $key => $value) {
                        $imgmd5 = md5($value);
                        $contentNew = str_ireplace($value, $imgmd5, $contentNew);
                    }
                }
                
                //处理内容详情
                foreach ($linkKeyword as $key => $val) {
                    $keywordStartPosition = stripos($contentNew, $val['title']);//关键字首次出现的起始位置
                    if ($keywordStartPosition !== false) {
                        $replace             = 1;//默认替换关键字为带链接的关键字
                        $keywordlength       = strlen($val['title']);//关键字长度
                        $keywordEndPosition  = $keywordStartPosition + $keywordlength;
                        $contentAfterKeyword = substr($contentNew, $keywordEndPosition);//关键字首次结束位置后的字符串
                        $aEndPosition        = stripos($contentAfterKeyword,'</a>');//从关键字首次结束位置后的字符串中，查找a结束标签</a>首次出现的位置
                        if ($aEndPosition === 0) {
                            $replace = 0;//如果关键字后就是</a>则说明关键字已经有链接，不再替换
                        } elseif ($aEndPosition !== false) {
                            $contentBetweenKeywordAndAend = substr($contentAfterKeyword, 0,
                                $aEndPosition);//关键字首次结束位置到下一个</a>标签之间的内容
                            $href                         = stripos($contentBetweenKeywordAndAend,
                                'href=');;//检查关键字首次结束位置到下一个</a>标签之间的内容中，是否包含href=，如果包含，则关键字没有链接，如果不包含，则关键字已有链接
                            if ($href === false) {
                                $replace = 0;//不替换
                            }
                        }
                        if ($replace == 1) {
                            //替换关键字为带链接的关键字
                            $target = (1 == $val['target']) ? ' target="_blank" ' : '';
                            $valLink    = '<a href="'.$val['url'].'" '.$target.' >'.$val['title'].'</a>';
                            $contentNew = substr_replace($contentNew, $valLink, $keywordStartPosition, $keywordlength);
                        }
                    }
                }

                if ($imginfo) {
                    foreach ($imginfo as $key => $value) {
                        $imgmd5 = md5($value);
                        $contentNew = str_ireplace($imgmd5, $value, $contentNew);
                    }
                }
                
                //替换内容详情
                $params = str_ireplace($content, $contentNew, $params);
                // 处理每个插件获取的内容应当是处理过的内容
                cache($session_key, $contentNew);
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