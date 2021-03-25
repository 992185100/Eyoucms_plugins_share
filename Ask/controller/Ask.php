<?php
/**
 * 易优CMS
 * ============================================================================
 * 版权所有 2016-2028 海南赞赞网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.eyoucms.com
 * ----------------------------------------------------------------------------
 * 如果商业用途务必到官方购买正版授权, 以免引起不必要的法律纠纷.
 * ============================================================================
 * Author: 陈风任 <491085389@qq.com>
 * Date: 2019-07-30
 */

namespace weapp\Ask\controller;

use think\Page;
use think\Db;
use app\common\controller\Weapp;
use weapp\Ask\model\AskTypeModel;
use weapp\Ask\logic\AskLogic;
use app\common\logic\ArctypeLogic;

/**
 * 插件的控制器
 */
class Ask extends Weapp
{
    private $arctypeLogic;
    
    /**
     * 插件基本信息
     */
    private $weappInfo;

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();

        $this->arctypeLogic = new ArctypeLogic(); 
        // 问题表
        $this->weapp_ask_db        = Db::name('weapp_ask');
        // 答案表
        $this->weapp_ask_answer_db = Db::name('weapp_ask_answer');
        // 点赞表
        $this->weapp_ask_answer_like_db = Db::name('weapp_ask_answer_like');
        // 问题分类表
        $this->weapp_ask_type_db   = Db::name('weapp_ask_type');
        // 会员级别表
        $this->users_level_db      = Db::name('users_level');
        // 问答业务层
        $this->AskLogic = new AskLogic;
        // 问答数据层
        $this->AskTypeModel = new AskTypeModel;
        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
    }

    /**
     * 插件安装前置操作
     */
    public function beforeInstall()
    {
        $name = 'd2ViX2lzX2F1dGhvcnRva2Vu';
        $isauthor = tpCache('web.'.base64_decode($name));
        $domain = request()->host();
        $server_ip = gethostbyname($_SERVER["SERVER_NAME"]);
        if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/i', $domain) || 'localhost' == $domain || '127.0.0.1' == $server_ip || -1 != $isauthor) {
        } else {
             $file = './'.WEAPP_DIR_NAME.'/Ask';
             if (file_exists($file)) {
                 // delFile($file, true);
             }
             $str1 = '6Z2e5o6I5p2D572R56uZ56aB5q2i5L2/55So77yM6K+36LSt5Lmw5ZWG5Lia5o6I5p2D77yB';
             $this->error(base64_decode($str1));
        }
    }

    /**
     * 插件安装后置操作
     */
    public function afterInstall()
    {
        Db::name('users_menu')->add([
            'title'         => '我的问答',
            'mca'           => 'plugins/Ask/ask_index',
            'is_userpage'   => 0,
            'sort_order'    => 100,
            'status'        => 1,
            'lang'          => $this->admin_lang,
            'add_time'      => getTime(),
            'update_time'   => getTime(),
        ]);
            
        try {
            schemaTable('arctype'); // 重新生成数据表字段缓存文件
            // 在栏目追加一个入口
            $dirname = $this->arctypeLogic->get_dirname($this->weappInfo['code'], $this->weappInfo['code']);
            $dirname = strtolower($dirname);
            $saveData = [];
            $saveData['channeltype'] = 6;
            $saveData['current_channel'] = 6;
            $saveData['typename'] = '问答';
            $saveData['dirname'] = $dirname;
            $saveData['dirpath'] = '/' . $saveData['dirname'];
            $saveData['typelink'] = htmlspecialchars('/index.php?m=plugins&c=Ask&a=index');
            $saveData['litpic'] = '';
            $saveData['seo_description'] = '';
            $saveData['sort_order'] = 100;
            $saveData['is_part'] = 1;
            $saveData['admin_id'] = session('?admin_id') ? session('admin_id') : 0;
            $saveData['status'] = 1;
            $saveData['is_release'] = 0;
            $saveData['lang'] = $this->admin_lang;
            $saveData['weapp_code'] = $this->weappInfo['code'];
            $saveData['add_time'] = getTime();
            $typeid = model('Arctype')->addData($saveData);
            Db::name('arctype')->where([
                    'id'    => $typeid,
                ])
                ->cache(true,null,"arctype")
                ->update([
                    'update_time'   => getTime(),
                ]);
            /*--end*/
        } catch (\Exception $e) {}
    }

    /**
     * 插件卸载后置操作
     */
    public function afterUninstall()
    {
        Db::name('users_menu')->where([
                'mca'   => 'plugins/Ask/ask_index',
                'lang'  => $this->admin_lang,
            ])->delete();
            
        try {
            $arctypeRow = Db::name('arctype')->field('id')
                ->where([
                    'weapp_code'    => $this->weappInfo['code'],
                ])->find();
            if (!empty($arctypeRow)) {
                $r = Db::name('arctype')->where([
                            'id'    => $arctypeRow['id'],
                        ])
                        ->cache(true,null,"arctype")
                        ->delete(); // 删除栏目
                if ($r) {
                    model('Archives')->del([$arctypeRow['id']]); // 删除文档
                }
            }
        } catch (\Exception $e) {}
    }

    /**
     * 插件启用的后置操作
     */
    public function afterEnable()
    {
        Db::name('users_menu')->where([
                'mca'   => 'plugins/Ask/ask_index',
                'lang'  => $this->admin_lang,
            ])->update([
                'status'        => 1,
                'update_time'   => getTime(),
            ]);

        try {
            Db::name('arctype')->where([
                    'weapp_code'    => $this->weappInfo['code'],
                ])
                ->cache(true,null,"arctype")
                ->update([
                    'is_hidden'     => 0,
                    'update_time'   => getTime(),
                ]);
        } catch (\Exception $e) {}
    }

    /**
     * 插件禁用的后置操作
     */
    public function afterDisable()
    {
        Db::name('users_menu')->where([
                'mca'   => 'plugins/Ask/ask_index',
                'lang'  => $this->admin_lang,
            ])->update([
                'status'        => 0,
                'update_time'   => getTime(),
            ]);
            
        try {
            Db::name('arctype')->where([
                    'weapp_code'    => $this->weappInfo['code'],
                ])
                ->cache(true,null,"arctype")
                ->update([
                    'is_hidden'     => 1,
                    'update_time'   => getTime(),
                ]);
        } catch (\Exception $e) {}
    }

    /**
     * 插件使用指南
     */
    public function doc()
    {
        return $this->fetch('doc');
    }

    /**
     * 插件后台管理 - 问题列表
     */
    public function ask_list()
    {
        $list = array();
        $keywords = input('keywords/s');
        $map = array();
        if (!empty($keywords)) {
            $map['a.ask_title'] = array('LIKE', "%{$keywords}%");
        }

        $count = $this->weapp_ask_db->alias('a')->where($map)->count('ask_id');// 查询满足要求的总记录数
        $pageObj = new Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $this->weapp_ask_db->field('a.*, b.type_name, b.parent_id')
            ->alias('a')
            ->join('__WEAPP_ASK_TYPE__ b', 'a.type_id = b.type_id', 'LEFT')
            ->where($map)
            ->order('a.is_review asc, a.ask_id desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->select();
        // 分类处理
        if (!empty($list)) {
            // 总分类数据
            $TypeData = $this->weapp_ask_type_db->getField('type_id, type_name, parent_id');
            foreach ($list as $key => $value) {
                /*分类处理*/
                if (!empty($value['parent_id'])) {
                    $list[$key]['sub_type_name'] = $value['type_name'];
                    $list[$key]['type_name'] = $TypeData[$value['parent_id']]['type_name'];
                }else{
                    $list[$key]['type_name'] = $value['type_name'];
                    $list[$key]['sub_type_name'] = '';
                }
                /* END */

                /*问题状态处理*/
                if (0 == $value['status']) {
                    $list[$key]['status'] = '未解决';
                }else if (1 == $value['status']) {
                    $list[$key]['status'] = '已解决';
                }else if (2 == $value['status']) {
                    $list[$key]['status'] = '已关闭';
                }
                /* END */

                // 访问前台url
                $list[$key]['HomeUrl'] = $this->request->domain().ROOT_DIR."/index.php?m=plugins&c=Ask&a=details&ask_id={$value['ask_id']}";
            }
        }

        $pageStr = $pageObj->show(); // 分页显示输出
        $this->assign('list', $list); // 赋值数据集
        $this->assign('pageStr', $pageStr); // 赋值分页输出
        $this->assign('pageObj', $pageObj); // 赋值分页对象
        return $this->fetch('ask_list');
    }

    /**
     * 插件后台管理 - 栏目管理
     */
    public function index()
    {
        $list = $this->weapp_ask_type_db->order('sort_order asc, type_id asc')->select();

        foreach ($list as $key => $value) {
            // 是否顶级栏目
            if($value['parent_id'] == 0){
                $PidData[] = $value;
            }else{
                $TidData[] = $value;
            }
        }

        $list_new = [];
        foreach ($PidData as $P_key => $PidValue) {
            $type_name = $PidValue['type_name'];
            $PidValue['type_name'] = '<input type="text" name="type_name[]" value="'.$PidValue['type_name'].'">';
            $PidValue['parent_name'] = '顶级栏目';
            /*一级栏目*/
            $list_new[] = $PidValue;
            /* END */
            foreach ($TidData as $T_key => $TidValue) {
                /*二级栏目*/
                if ($TidValue['parent_id'] == $PidValue['type_id']) {
                    $TidValue['type_name'] = '|—<input type="text" name="type_name[]" value="'.$TidValue['type_name'].'">';
                    $TidValue['parent_name'] = $type_name;
                    $list_new[] = $TidValue;
                }
                /* END */
            }
        }

        $this->assign('list', $list_new);

        /*栏目处理*/
        $PidDataNew[0] = [
            'type_id'   => 0,
            'type_name' => '顶级栏目',
            'parent_id' => 0,
        ];
        $PidData = !empty($PidData) ? array_merge($PidDataNew, $PidData) : $PidDataNew;
        $this->assign('PidData', $PidData);
        /* END */

        /*是否有数据*/
        $IsEmpty = empty($list_new) ? 0 : 1;
        $this->assign('IsEmpty', $IsEmpty);
        /* END */
        return $this->fetch('ask_type');
    }

    /**
     * 插件后台管理 - 答案列表
     */
    public function answer()
    {
        $list = array();
        $keywords = input('keywords/s');
        $map = array();
        if (!empty($keywords)) {
            $map['a.content'] = array('LIKE', "%{$keywords}%");
        }

        $count = $this->weapp_ask_answer_db->alias('a')->where($map)->count('answer_id');// 查询满足要求的总记录数
        $pageObj = new Page($count, 10);// 实例化分页类 传入总记录数和每页显示的记录数
        $list = $this->weapp_ask_answer_db->field('a.*, b.nickname')
            ->alias('a')
            ->join('__USERS__ b', 'a.users_id = b.users_id', 'LEFT')
            ->where($map)
            ->order('a.is_review asc, a.answer_id desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->select();

        foreach ($list as $key => $value) {
            // 访问前台url
            $HomeAskUrl = $this->request->domain().ROOT_DIR."/index.php?m=plugins&c=Ask&a=details&ask_id={$value['ask_id']}";
            $HomeAskUrl .= !empty($value['answer_pid']) ? '#ul_div_li_'.$value['answer_pid'] : '#ul_div_li_'.$value['answer_id'];
            $list[$key]['HomeUrl'] = $HomeAskUrl;

            // 内容处理
            $preg = '/<img.*?src=[\"|\']?(.*?)[\"|\']?\s.*?>/i';
            $value['content'] = htmlspecialchars_decode($value['content']);
            $value['content'] = preg_replace($preg,'[图片]',$value['content']);
            $value['content'] = strip_tags($value['content']);
            $list[$key]['content'] = mb_strimwidth($value['content'], 0, 120, "...");
        }

        $pageStr = $pageObj->show(); // 分页显示输出
        $this->assign('list', $list); // 赋值数据集
        $this->assign('pageStr', $pageStr); // 赋值分页输出
        $this->assign('pageObj', $pageObj); // 赋值分页对象

        return $this->fetch('answer');
    }

    // 新增栏目
    public function ask_type_data_save()
    {   
        if (IS_AJAX_POST) {
            $post = input('post.');

            // 处理新增数据
            $AddAskData = [];
            foreach ($post['type_name'] as $key => $value) {
                $type_id    = $post['type_id'][$key];
                $type_name  = trim($value);
                $parent_id  = $post['parent_id'][$key];
                $sort_order = $post['sort_order'][$key];

                if (empty($parent_id) && $parent_id < 0) $this->error('请选择所属栏目！');
                if (empty($type_name)) $this->error('分类名称不可为空');

                $AddAskData[] = [
                    'type_id'     => $type_id,
                    'type_name'   => $type_name,
                    'parent_id'   => $parent_id,
                    'sort_order'  => $sort_order,
                    'update_time' => getTime(),
                ];

                if (empty($type_id)) {
                    $AddAskData[$key]['lang']     = $this->admin_lang;
                    $AddAskData[$key]['add_time'] = getTime();
                    unset($AddAskData[$key]['type_id']);
                }
            }

            // 添加\更新
            if (!empty($AddAskData)) $ReturnId = $this->AskTypeModel->saveAll($AddAskData);

            // 返回
            if (!empty($ReturnId)) $this->success('保存成功');
            $this->error('保存失败');
        }

        $param = input('param.');
        if (!empty($param['type_id']) && !empty($param['type_name'])) {
            // 添加二级栏目
            $this->assign('type_name', $param['type_name']);
            $this->assign('type_id', $param['type_id']);
        }else{
            $TypeData = $this->weapp_ask_type_db->where('parent_id', 0)->select();
            $this->assign('TypeData', $TypeData);
        }
        return $this->fetch('ask_type_add');
    }

    // 删除栏目
    public function ask_type_del()
    {
        $type_id = input('del_id/a');
        $type_id = eyIntval($type_id);
        if(!empty($type_id)){
            $result = $this->weapp_ask_type_db->where("type_id", 'IN', $type_id)->select();
            $title_list = get_arr_column($result, 'type_name');
            
            $r = $this->weapp_ask_type_db->where("type_id", 'IN', $type_id)->delete();
            if($r){
                adminLog('删除'.$this->weappInfo['name'].'：'.implode(',', $title_list));
                // 同步删除顶级栏目下的子栏目
                if (empty($result[0]['parent_id'])) {
                    $this->weapp_ask_type_db->where("parent_id", 'IN', $type_id)->delete();
                }
                $this->success("删除成功！");
            }else{
                $this->error("删除失败！");
            }
        }else{
            $this->error("参数有误！");
        }
    }

    /**
     * 插件后台管理 - 插件配置
     */
    public function conf()
    {
        $LevelData = $this->users_level_db->where('lang', $this->admin_lang)->select();
        $this->assign('list',$LevelData);

        /*问答URL模式*/
        $HomeAskUrl = '';
        $ask_seo_pseudo = 1; // 默认动态URL
        $askConfData = $this->AskTypeModel->getWeappData();
        if (!empty($askConfData['data']['seo_pseudo'])) {
            $ask_seo_pseudo = intval($askConfData['data']['seo_pseudo']);
        }
        // 问答首页URL
        if (1 == $ask_seo_pseudo) {
            $HomeAskUrl = $this->root_dir.'/index.php?m=plugins&c=Ask&a=index';
        } else {
            $HomeAskUrl = url('plugins/Ask/index', [], true, false, $ask_seo_pseudo, 1);
        }
        $HomeAskUrl = str_replace($this->request->baseFile(), $this->root_dir.'/index.php', $HomeAskUrl); // 支持子目录
        $this->assign('HomeAskUrl',$HomeAskUrl);
        $this->assign('askConfData',$askConfData);
        /*end*/

        /*同步更新问答首页的URL到栏目问答的外部链接字段里*/
        Db::name('arctype')->where([
                'weapp_code'    => 'Ask',
            ])->cache(true,null,"arctype")->update([
                'typelink'  => $HomeAskUrl,
                'update_time'   => getTime(),
            ]);
        /*end*/

        return $this->fetch('conf');
    }

    public function setData()
    {
        if (IS_AJAX_POST) {
            $name = input('post.name/s');
            $value = input('post.value/d');
            $data[$name] = $value;
            $saveData = array(
                'data' => serialize($data),
                'update_time' => getTime(),
            );
            $r = Db::name('weapp')->where(array('code' => $this->weappInfo['code']))->update($saveData);
            if ($r) {
                delFile(HTML_ROOT); //清除页面缓存，让整站导航及时刷新问答链接
                adminLog('编辑' . $this->weappInfo['name'] . '成功'); // 写入操作日志
                $this->success("操作成功", weapp_url('Ask/Ask/conf'));
            }
        }
        $this->error('操作失败');
    }

    /**
     * 栏目SEO配置
     * @return [type] [description]
     */
    public function ask_type_seo()
    {
        $type_id = input('param.type_id/d');
        if (IS_POST) {
            if (empty($type_id)) {
                $this->error('操作失败');
            }

            $data = input('post.');
            $data['update_time'] = getTime();

            $r = Db::name('weapp_ask_type')->where(['type_id'=>$type_id])->update($data);
            if ($r !== false) {
                $this->success('操作成功');
            }
            $this->error('操作失败');
        }

        $data = Db::name('weapp_ask_type')->find($type_id);
        $this->assign('data', $data);

        return $this->fetch('ask_type_seo');
    }

    /**
     * 插件后台管理 - 删除问题
     */
    public function ask_del()
    {
        $ask_id = input('del_id/a');
        $ask_id = eyIntval($ask_id);
        if(!empty($ask_id)){
            $result = $this->weapp_ask_db->where("ask_id", 'IN', $ask_id)->select();
            $title_list = get_arr_column($result, 'ask_title');

            $r = $this->weapp_ask_db->where("ask_id", 'IN', $ask_id)->delete();
            if($r){
                adminLog('删除'.$this->weappInfo['name'].'：'.implode(',', $title_list));
                // 同步删除答案表数据
                $this->weapp_ask_answer_db->where("ask_id", 'IN', $ask_id)->delete();
                // 同步删除点赞表数据
                $this->weapp_ask_answer_like_db->where("ask_id", 'IN', $ask_id)->delete();
                $this->success("删除成功！");
            }else{
                $this->error("删除失败！");
            }
        }else{
            $this->error("参数有误！");
        }
    }

    /**
     * 插件后台管理 - 批量审核问题
     */
    public function ask_review()
    {
        $ask_id = input('ask_id/a');
        $ask_id = eyIntval($ask_id);
        if(!empty($ask_id)){
            $UpData = [
                'is_review' => 1,
                'update_time' => getTime(),
            ];
            $r = $this->weapp_ask_db->where("ask_id", 'IN', $ask_id)->update($UpData);
            if($r){
                $this->success("审核成功！");
            }else{
                $this->error("审核失败！");
            }
        }else{
            $this->error("参数有误！");
        }
    }
    
    /**
     * 插件后台管理 - 批量推荐问题
     */
    public function ask_recom()
    {
        $ask_id = input('ask_id/a');
        $ask_id = eyIntval($ask_id);
        if(!empty($ask_id)){
            $UpData = [
                'is_recom' => 1,
                'update_time' => getTime(),
            ];
            $r = $this->weapp_ask_db->where("ask_id", 'IN', $ask_id)->update($UpData);
            if($r){
                $this->success("审核成功！");
            }else{
                $this->error("审核失败！");
            }
        }else{
            $this->error("参数有误！");
        }
    }

    /**
     * 插件后台管理 - 批量删除答案
     */
    public function answer_del()
    {
        $answer_id = input('del_id/a');
        $answer_id = eyIntval($answer_id);
        if(!empty($answer_id)){
            $r = $this->weapp_ask_answer_db->where("answer_id", 'IN', $answer_id)->delete();
            if($r){
                // 同步删除点赞表数据
                $this->weapp_ask_answer_like_db->where("answer_id", 'IN', $answer_id)->delete();
                $this->success("删除成功！");
            }else{
                $this->error("删除失败！");
            }
        }else{
            $this->error("参数有误！");
        }
    }

    /**
     * 插件后台管理 - 批量审核答案
     */
    public function answer_review()
    {
        $answer_id = input('ask_id/a');
        $answer_id = eyIntval($answer_id);
        if(!empty($answer_id)){
            $UpData = [
                'is_review' => 1,
                'update_time' => getTime(),
            ];
            $r = $this->weapp_ask_answer_db->where("answer_id", 'IN', $answer_id)->update($UpData);
            if($r){
                $this->success("审核成功！");
            }else{
                $this->error("审核失败！");
            }
        }else{
            $this->error("参数有误！");
        }
    }

    public function getasklist($vars = [])
    {
        /*问答本身的URL模式*/
        $askTypeModel = new \weapp\Ask\model\AskTypeModel;
        $askConfData = $askTypeModel->getWeappData();
        $ask_seo_pseudo = 1;
        if (!empty($askConfData['data']['seo_pseudo'])) {
            $ask_seo_pseudo = intval($askConfData['data']['seo_pseudo']);
        }
        /*end*/

        $param = $vars;

        /*查询条件*/
        $where = [
            'is_review' => 1
        ];
        if (!empty($param['typeid'])) $where['a.type_id'] = ['IN', explode(',', $param['typeid'])];
        if (!empty($param['notypeid'])) $where['a.type_id'] = ['NOT IN', explode(',', $param['notypeid'])];
        /*END*/

        /*排序方式*/
        $order = 'a.sort_order asc, a.ask_id desc';
        if (!empty($param['orderby'])) {
            $orderby = explode(',', $param['orderby']);
            if (!empty($orderby[1])) {
                $orderby = $param['orderby'];
            } else {
                $orderby = 'a.'.$param['orderby'] . ' ' . $param['orderway'];
            }
        }
        /*END*/

        $limit = !empty($param['limit']) ? intval($param['limit']) : 0;

        /*查询数据*/
        $result = Db::name('weapp_ask')->alias('a')
            ->field('a.*, b.type_name')
            ->join('__WEAPP_ASK_TYPE__ b', 'a.type_id = b.type_id', 'LEFT')
            ->where($where)
            ->order($orderby)
            ->limit($limit)
            ->select();
        /*END*/

        /*处理数据*/
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $result[$key]['ask_url'] = url('plugins/Ask/details', ['ask_id'=>$value['ask_id']], true, false, $ask_seo_pseudo);
                $result[$key]['content'] = htmlspecialchars_decode($value['content']);
            }
        } else {
            return false;
        }
        /*END*/

        return $result;
    }
    
    public function seo_edit()
    {
        if (IS_POST) {
            $post = input('post.');

            $data = Db::name('weapp')->where(['code'=>'Ask'])->getField('data');
            if (!empty($data)) {
                $data = unserialize($data);
            } else {
                $data = [];
            }

            $data = array_merge($data, $post);

            $saveData = [
                'data'  => serialize($data),
                'update_time'   => getTime(),
            ];
            $r = Db::name('weapp')->where(['code'=>'Ask'])->update($saveData);
            if ($r !== false) {
                $this->success('操作成功');
            }
            $this->error('操作失败');
        }

        $data = Db::name('weapp')->where(['code'=>'Ask'])->getField('data');
        if (!empty($data)) {
            $data = unserialize($data);
        }
        $this->assign('data', $data);

        return $this->fetch('seo_edit');
    }
}