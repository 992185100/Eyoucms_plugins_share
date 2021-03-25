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

namespace weapp\Wechat\controller;

use think\Db;
use think\Page;

/**
 * 关键字回复控制器
 */
class Keyword extends Base{

    /**
     * 构造方法
     */
    public function __construct(){
        parent::__construct();
        $token = I('param.token/s', '');
        $saName = I('param.sa/s', '');
        if (empty($token) && 'keyword_index' == $saName) {
            return $this->keyword_default();
        }
    }

    /*
     * 快捷入口
     */
    public function keyword_default()
    {
        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        if (empty($wechat_list)) {
            $this->error('请先新增公众号！', weapp_url('Wechat/Wechat/config_add'));
        } elseif (1 == count($wechat_list)) {
            $firstRow = current($wechat_list);
            $url = weapp_url('Wechat/Keyword/keyword_index', array('token'=>$firstRow['token']));
            $this->redirect($url);
        }
        $assign_data['wechat_list'] = $wechat_list;

        $this->assign($assign_data);
        echo $this->fetch('keyword_default');
        exit;
    }

    /*
     * 全部关键字回复
     */
    public function keyword_index()
    {
        $assign_data = array();
        $condition = array();
        // 获取到所有GET参数
        $param = I('param.');

        // 应用搜索条件
        foreach (['keywords','token'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.keyword'] = array('LIKE', "%{$param[$key]}%");
                } else {
                    $tmp_key = 'a.'.$key;
                    $condition[$tmp_key] = array('eq', $param[$key]);
                }
            }
        }

        /**
         * 数据查询
         */
        $count = DB::name('weapp_wx_keyword')->alias('a')->where($condition)->count();// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = DB::name('weapp_wx_keyword')
            ->field("a.*")
            ->alias('a')
            ->where($condition)
            ->order('a.id desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->getAllWithIndex('id');
        $pageStr = $pageObj->show(); // 分页显示输出
        $assign_data['list'] = $list; // 赋值数据集
        $assign_data['pageStr'] = $pageStr; // 赋值分页输出
        $assign_data['pageObj'] = $pageObj; // 赋值分页对象

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $assign_data['wechat_list'] = $wechat_list;

        $assign_data['keyword_type'] = $this->wechatLogic->get_keyword_type();

        $this->assign($assign_data);
        return $this->fetch('keyword_index');
    }

    /*
     * 文本回复
     */
    public function text_index()
    {
        $assign_data = array();
        $condition = array('a.type'=>'TEXT');
        // 获取到所有GET参数
        $param = I('param.');

        // 应用搜索条件
        foreach (['keywords','token'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.keyword'] = array('LIKE', "%{$param[$key]}%");
                } else {
                    $tmp_key = 'a.'.$key;
                    $condition[$tmp_key] = array('eq', $param[$key]);
                }
            }
        }

        /**
         * 数据查询
         */
        $count = DB::name('weapp_wx_keyword')->alias('a')->where($condition)->count();// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = DB::name('weapp_wx_keyword')
            ->field("a.id, a.keyword, t.text, t.token, t.update_time")
            ->alias('a')
            ->join('__WEAPP_WX_TEXT__ t', 't.id = a.pid', 'LEFT')
            ->where($condition)
            ->order('a.id desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->getAllWithIndex('id');
        $pageStr = $pageObj->show(); // 分页显示输出
        $assign_data['list'] = $list; // 赋值数据集
        $assign_data['pageStr'] = $pageStr; // 赋值分页输出
        $assign_data['pageObj'] = $pageObj; // 赋值分页对象

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $assign_data['wechat_list'] = $wechat_list;

        $this->assign($assign_data);
        return $this->fetch('text_index');
    }

    /*
     * 文本回复 - 添加
     */
    public function text_add()
    {
        if (IS_POST) {
            $post = I('post.');

            /*检测关键字唯一性*/
            $map = array(
                'token' => $post['token'],
                'keyword' => trim($post['keyword']),
            );
            $k_info = M('weapp_wx_keyword')->where($map)->find();
            if ($k_info) {
                $sa = strtolower($k_info['type']).'_index';
                $this->error('该关键字已存在，请查看'.$this->wechatLogic->get_keyword_type($k_info['type']).'列表');
            }
            /*--end*/

            /*保存到文本表*/
            $data = array(
                'add_time'  => getTime(),
                'update_time'  => getTime(),
            );
            $nowData = array_merge($post, $data);
            $id = DB::name('weapp_wx_text')->add($nowData);
            /*--end*/
            if (!empty($id)) {
                /*保存到主表*/
                $k_data = array(
                    'pid'       => $id,
                    'type'      => 'TEXT',
                    'add_time'  => getTime(),
                    'update_time'  => getTime(),
                );
                $k_nowData = array_merge($post, $k_data);
                $row = M('weapp_wx_keyword')->add($k_nowData);
                if (!empty($row)) {
                    $this->success("操作成功", weapp_url('Wechat/Keyword/text_index', array('token'=>$post['token'])));
                }
                /*--end*/
            }
            $this->error("操作失败");
        }

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $this->assign('wechat_list', $wechat_list);

        return $this->fetch('text_add');
    }

    /*
     * 文本回复 - 编辑
     */
    public function text_edit()
    {
        if (IS_POST) {
            $post = I('post.');
            $kid = I('post.kid');

            /*检测关键字唯一性*/
            $map = array(
                'id'        => array('NEQ', $kid),
                'token' => $post['token'],
                'keyword' => trim($post['keyword']),
            );
            $k_info = M('weapp_wx_keyword')->where($map)->find();
            if (!empty($k_info)) {
                $sa = strtolower($k_info['type']).'_index';
                $this->error('该关键字已存在，请查看'.$this->wechatLogic->get_keyword_type($k_info['type']).'列表');
            }
            /*--end*/

            /*保存到主表*/
            $k_data = array(
                'type'      => 'TEXT',
                'update_time'=> getTime(),
            );
            $k_nowData = array_merge($post, $k_data);
            $row = M('weapp_wx_keyword')->where('id', $kid)->update($k_nowData);
            /*--end*/
            if (!empty($row)) {
                /*保存到文本表*/
                $data = array(
                    'update_time'  => getTime(),
                );
                $nowData = array_merge($post, $data);
                $k_info = array();
                $k_info = M('weapp_wx_keyword')->where('id', $kid)->find();
                $result = DB::name('weapp_wx_text')->where('id', $k_info['pid'])->update($nowData);
                if (!empty($nowData)) {
                    $this->success("操作成功", weapp_url('Wechat/Keyword/text_index', array('token'=>$post['token'])));
                }
                /*--end*/
            }
            $this->error("操作失败");
        }

        $id = I('param.id/d', 0);
        $info = array();
        if(0 < $id){
            $sql = "SELECT k.id,k.keyword,t.text,t.token FROM __PREFIX__weapp_wx_keyword k LEFT JOIN __PREFIX__weapp_wx_text AS t ON t.id = k.pid WHERE k.id = {$id} AND k.token = t.token AND k.type = 'TEXT'";
            $data = DB::query($sql);
            $info = current($data);
        }
        $this->assign('field',$info);

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $this->assign('wechat_list', $wechat_list);

        return $this->fetch('text_edit');
    }

    /*
     * 文本回复 - 删除
     */
    public function text_del()
    {
        $id = $id_arr = I('del_id/a');
        if(is_array($id_arr)){
            foreach ($id_arr as $key => $val) {
                $id_arr[$key] = intval($val);
            }
            $id = implode(',', $id_arr); 
        } else {
            $id = intval($id);
        }
        if(!empty($id)){
            $row = M('weapp_wx_keyword')->where("id in ($id)")->select();
            $r = M('weapp_wx_keyword')->where("id in ($id)")->delete();
            if ($r) {
                $pid_arr = get_arr_column($row, 'pid');
                M('weapp_wx_text')->where('id', 'IN', $pid_arr)->delete();
                $this->success('删除成功！');
            } else {
                $this->error('删除失败！');
            }
        }else{
            $this->error('参数有误！');
        }
    }

    /*
     * 图片回复列表
     */
    public function pic_index()
    {
        $assign_data = array();
        $condition = array( 'a.type'=>'PIC');
        // 获取到所有GET参数
        $param = I('param.');

        // 应用搜索条件
        foreach (['keywords','token'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.keyword'] = array('LIKE', "%{$param[$key]}%");
                } else {
                    $tmp_key = 'a.'.$key;
                    $condition[$tmp_key] = array('eq', $param[$key]);
                }
            }
        }

        /**
         * 数据查询
         */
        $count = DB::name('weapp_wx_keyword')->alias('a')->where($condition)->count();// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = DB::name('weapp_wx_keyword')
            ->field("a.id, a.keyword, b.title, b.media_id, b.litpic, b.wx_img_url, b.token, b.update_time")
            ->alias('a')
            ->join('__WEAPP_WX_PIC__ b', 'b.id = a.pid', 'LEFT')
            ->where($condition)
            ->order('a.id desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->getAllWithIndex('id');
        $pageStr = $pageObj->show(); // 分页显示输出
        $assign_data['list'] = $list; // 赋值数据集
        $assign_data['pageStr'] = $pageStr; // 赋值分页输出
        $assign_data['pageObj'] = $pageObj; // 赋值分页对象

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $assign_data['wechat_list'] = $wechat_list;

        $this->assign($assign_data);
        return $this->fetch('pic_index');
    }

    /*
     * 图片回复 - 新增
     */
    public function pic_add(){

        $this->checkWechat();

        if (IS_POST) {
            $post = I('post.');
            $litpic = '';
            $media_id = '';
            $wx_img_url = '';

            /*检测关键字唯一性*/
            $map = array(
                'token' => $post['token'],
                'keyword' => trim($post['keyword']),
            );
            $k_info = M('weapp_wx_keyword')->where($map)->find();
            if (!empty($k_info)) {
                $this->error('该关键字已存在，请查看'.$this->wechatLogic->get_keyword_type($k_info['type']).'列表');
            }
            /*--end*/

            /*上传图片*/
            $file = request()->file('litpic');
            if(empty($file)){
                $this->error('请上传图片');
            } else {
                $logic = new \weapp\Wechat\logic\WechatLogic();
                $pic_info = $logic->upFile('litpic');
                if ($pic_info['state'] == 'SUCCESS') {
                    $litpic = $pic_info['url'];
                } else {
                    $this->error($pic_info['state']);
                }
            }
            /*--end*/
        
            /*新增永久素材*/
            if (!empty($litpic)) {
                weapp_vendor('wechat.wechat', 'Wechat');
                $wxconfig = M('weapp_wx_config')->where('token', $post['token'])->find();
                if (!empty($wxconfig)) {
                    $materialObj = new \material($wxconfig);
                    $params = $materialObj->addMaterial(ROOT_PATH.ltrim($litpic, '/'), 'image');
                    $media_id = $params['media_id'];
                    $wx_img_url = $params['url'];
                }
            }
            /*--end*/

            /*保存到图片表*/
            $data = array(
                'media_id'      => $media_id,
                'litpic'      => $litpic,
                'wx_img_url'      => $wx_img_url,
                'add_time'  => getTime(),
                'update_time'  => getTime(),
            );
            $nowData = array_merge($post, $data);
            $id = DB::name('weapp_wx_pic')->add($nowData);
            /*--end*/
            if (!empty($id)) {
                /*保存到主表*/
                $k_data = array(
                    'pid'       => $id,
                    'type'      => 'PIC',
                    'add_time'  => getTime(),
                    'update_time'  => getTime(),
                );
                $k_nowData = array_merge($post, $k_data);
                $row = M('weapp_wx_keyword')->add($k_nowData);
                if (!empty($row)) {
                    $this->success("操作成功", weapp_url('Wechat/Keyword/pic_index', array('token'=>$post['token'])));
                }
                /*--end*/
            }
            $this->error("操作失败");
        }

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $this->assign('wechat_list', $wechat_list);

        return $this->fetch('pic_add');
    }

    /*
     * 图片回复 - 编辑
     */
    public function pic_edit()
    {
        $this->checkWechat();

        if (IS_POST) {
            $post = I('post.');
            $kid = I('post.kid');
            $litpic = I('post.oldlitpic', '');
            $media_id = I('post.media_id', '');
            $wx_img_url = I('post.wx_img_url', '');

            /*检测关键字唯一性*/
            $map = array(
                'id'        => array('NEQ', $kid),
                'token'     => $post['token'],
                'keyword' => trim($post['keyword']),
            );
            $k_info = M('weapp_wx_keyword')->where($map)->find();
            if (!empty($k_info)) {
                $this->error('该关键字已存在，请查看'.$this->wechatLogic->get_keyword_type($k_info['type']).'列表');
            }
            /*--end*/

            /*上传图片*/
            $nowLitpic = '';
            $file = request()->file('litpic');
            if(!empty($file)){
                $logic = new \weapp\Wechat\logic\WechatLogic();
                $pic_info = $logic->upFile('litpic');
                if ($pic_info['state'] == 'SUCCESS') {
                    $nowLitpic = $pic_info['url'];
                } else {
                    $this->error($pic_info['state']);
                }
            }
            /*--end*/
        
            /*新增永久素材*/
            if (!empty($nowLitpic)) {
                weapp_vendor('wechat.wechat', 'Wechat');
                $wxconfig = M('weapp_wx_config')->where('token', $post['token'])->find();
                if (!empty($wxconfig)) {
                    $materialObj = new \material($wxconfig);
                    /*删除永久素材*/
                    if (!empty($post['media_id'])) {
                        $materialObj->delMaterial($post['media_id']);
                    }
                    /*--end*/
                    $params = $materialObj->addMaterial(ROOT_PATH.ltrim($nowLitpic, '/'), 'image');
                    $media_id = $params['media_id'];
                    $wx_img_url = $params['url'];
                    $litpic = $nowLitpic;
                }
            }
            /*--end*/

            /*保存到主表*/
            $k_data = array(
                'type'      => 'PIC',
                'update_time'=> getTime(),
            );
            $k_nowData = array_merge($post, $k_data);
            $row = M('weapp_wx_keyword')->where('id', $kid)->update($k_nowData);
            /*--end*/
            if (!empty($row)) {
                /*保存到图片表*/
                $data = array(
                    'media_id'      => $media_id,
                    'litpic'      => $litpic,
                    'wx_img_url'      => $wx_img_url,
                    'update_time'  => getTime(),
                );
                $nowData = array_merge($post, $data);
                $k_info = array();
                $k_info = M('weapp_wx_keyword')->where('id', $kid)->find();
                $result = DB::name('weapp_wx_pic')->where('id', $k_info['pid'])->update($nowData);
                if (!empty($result)) {
                    $this->success("操作成功", weapp_url('Wechat/keyword/pic_index', array('token'=>$post['token'])));
                }
                /*--end*/
            }
            $this->error("操作失败");
        }

        $id = I('param.id/d', 0);
        $info = array();
        if(0 < $id){
            $sql = "SELECT k.id,k.keyword,i.title,i.litpic,i.media_id,i.token,i.wx_img_url FROM __PREFIX__weapp_wx_keyword k LEFT JOIN __PREFIX__weapp_wx_pic i ON i.id = k.pid WHERE k.id = {$id} AND k.token = i.token AND k.type = 'PIC'";
            $data = DB::query($sql);
            $info = current($data);
        }
        $this->assign('field',$info);

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $this->assign('wechat_list', $wechat_list);

        return $this->fetch('pic_edit');
    }

    /*
     * 图片回复 - 删除
     */
    public function pic_del()
    {
        $id = $id_arr = I('del_id/a');
        if(is_array($id_arr)){
            foreach ($id_arr as $key => $val) {
                $id_arr[$key] = intval($val);
            }
            $id = implode(',', $id_arr);  
        } else {
            $id = intval($id);
        }
        if(!empty($id)){
            $row = M('weapp_wx_keyword')->field('a.id, a.pid, a.token, b.media_id')
                ->alias('a')
                ->join('__WEAPP_WX_PIC__ b', 'b.id = a.pid', 'LEFT')
                ->where("a.id in ($id)")
                ->select();
            $r = M('weapp_wx_keyword')->where("id in ($id)")->delete();
            if ($r) {
                $pid_arr = get_arr_column($row, 'pid');
                $p = M('weapp_wx_pic')->where('id', 'IN', $pid_arr)->delete();
                if ($p) {
                    $wxconfig = M('weapp_wx_config')->where('token', $row[0]['token'])->find();
                    if($wxconfig){
                        weapp_vendor('wechat.wechat', 'Wechat');
                        $materialObj = new \material($wxconfig);
                        foreach ($row as $key => $val) {
                            if (!empty($val['media_id'])) {
                                $materialObj->delMaterial($val['media_id']);
                            }
                        }
                    }
                }
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }else{
            $this->error('参数有误');
        }
    }

    /*
     * 图文列表
     */
    public function img_index()
    {
        $assign_data = array();
        $condition = array('a.type'=>'IMG');
        // 获取到所有GET参数
        $param = I('param.');

        // 应用搜索条件
        foreach (['keywords','token'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.keyword'] = array('LIKE', "%{$param[$key]}%");
                } else {
                    $tmp_key = 'a.'.$key;
                    $condition[$tmp_key] = array('eq', $param[$key]);
                }
            }
        }

        /**
         * 数据查询
         */
        $count = DB::name('weapp_wx_keyword')->alias('a')->where($condition)->count();// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = DB::name('weapp_wx_keyword')
            ->field("a.id, a.keyword, a.update_time, b.title, b.url, b.litpic, b.intro, b.token")
            ->alias('a')
            ->join('__WEAPP_WX_IMG__ b', 'b.id = a.pid', 'LEFT')
            ->where($condition)
            ->order('a.id desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->getAllWithIndex('id');
        $pageStr = $pageObj->show(); // 分页显示输出
        $assign_data['pageStr'] = $pageStr; // 赋值分页输出
        $assign_data['list'] = $list; // 赋值数据集
        $assign_data['pageObj'] = $pageObj; // 赋值分页对象

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $assign_data['wechat_list'] = $wechat_list;

        $this->assign($assign_data);
        return $this->fetch('img_index');
    }

    /*
     * 图文回复 - 新增
     */
    public function img_add(){

        $this->checkWechat();

        if (IS_POST) {
            $post = I('post.');
            $litpic = '';
            $media_id = $wx_img_url = '';
            $thumb_media_id = $thumb_wx_img_url = '';

            /*检测关键字唯一性*/
            $map = array(
                'token' => $post['token'],
                'keyword' => trim($post['keyword']),
            );
            $k_info = M('weapp_wx_keyword')->where($map)->find();
            if (!empty($k_info)) {
                $this->error('该关键字已存在，请查看'.$this->wechatLogic->get_keyword_type($k_info['type']).'列表');
            }
            /*--end*/

            /*上传图片*/
            $is_remote = !empty($post['is_remote']) ? $post['is_remote'] : 0;
            if (1 == $is_remote) {
                $remoteJson = $this->wechatLogic->save_remote($post['litpic_remote']);
                $remoteData = json_decode($remoteJson, true);
                if ('SUCCESS' == $remoteData['state']) {
                    $litpic = $remoteData['url'];
                }
            } else {
                $file = request()->file('litpic_local');
                if(empty($file)){
                    $this->error('请上传封面图');
                } else {
                    $logic = new \weapp\Wechat\logic\WechatLogic();
                    $pic_info = $logic->upFile('litpic_local');
                    if ($pic_info['state'] == 'SUCCESS') {
                        $litpic = $pic_info['url'];
                    } else {
                        $this->error($pic_info['state']);
                    }
                }
            }
            /*--end*/

            /*新增永久图文素材*/
            if (!empty($litpic)) {
                weapp_vendor('wechat.wechat', 'Wechat');
                $wxconfig = M('weapp_wx_config')->where('token', $post['token'])->find();
                if (!empty($wxconfig)) {
                    $materialObj = new \material($wxconfig);
                    $type = 'thumb';
                    $params = $materialObj->addMaterial(ROOT_PATH.ltrim($litpic, '/'), $type);
                    if ('thumb' == $type) {
                        $thumb_media_id = $params['media_id'];
                        $thumb_wx_img_url = $params['url'];
                    } else {
                        $media_id = $params['media_id'];
                        $wx_img_url = $params['url'];
                    }
                }
            }
            /*--end*/

            /*摘要*/
            $intro = html_msubstr($post['intro'], 0, 110, true);
            /*--end*/

            /*保存到单图文表*/
            $data = array(
                'litpic'    => $litpic,
                'media_id'    => $media_id,
                'wx_img_url'    => $wx_img_url,
                'thumb_media_id'    => $thumb_media_id,
                'thumb_wx_img_url'    => $thumb_wx_img_url,
                'intro' => $intro,
                'add_time'  => getTime(),
                'update_time'  => getTime(),
            );
            $nowData = array_merge($post, $data);
            $id = DB::name('weapp_wx_img')->add($nowData);
            /*--end*/
            if (0 < $id) {
                /*保存到主表*/
                $k_data = array(
                    'pid'       => $id,
                    'type'      => 'IMG',
                    'add_time'  => getTime(),
                    'update_time'  => getTime(),
                );
                $k_nowData = array_merge($post, $k_data);
                $row = M('weapp_wx_keyword')->add($k_nowData);
                /*--end*/
                if (!empty($row)) {
                    $this->success("操作成功", weapp_url('Wechat/Keyword/img_index', array('token'=>$post['token'])));
                }
            }
            $this->error("操作失败");
        }

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $this->assign('wechat_list', $wechat_list);

        return $this->fetch('img_add');
    }
    
    /*
     * 图文回复 - 编辑
     */
    public function img_edit()
    {
        $this->checkWechat();

        if (IS_POST) {
            $post = I('post.');
            $kid = I('post.kid');

            /*检测关键字唯一性*/
            $map = array(
                'id'        => array('NEQ', $kid),
                'token'     => $post['token'],
                'keyword' => trim($post['keyword']),
            );
            $k_info = M('weapp_wx_keyword')->where($map)->find();
            if (!empty($k_info)) {
                $this->error('该关键字已存在，请查看'.$this->wechatLogic->get_keyword_type($k_info['type']).'列表');
            }
            /*--end*/

            /*保存到主表*/
            $k_data = array(
                'type'      => 'IMG',
                'update_time'=> getTime(),
            );
            $k_nowData = array_merge($post, $k_data);
            $row = M('weapp_wx_keyword')->where('id', $kid)->update($k_nowData);
            /*--end*/
            if (!empty($row)) {
                $litpic = '';
                $media_id = $wx_img_url = '';
                $thumb_media_id = $thumb_wx_img_url = '';

                /*上传图片*/
                $is_remote = !empty($post['is_remote']) ? $post['is_remote'] : 0;
                if (1 == $is_remote) {
                    $remoteJson = $this->wechatLogic->save_remote($post['litpic_remote']);
                    $remoteData = json_decode($remoteJson, true);
                    if ('SUCCESS' == $remoteData['state']) {
                        $litpic = $remoteData['url'];
                    }
                } else {
                    $file = request()->file('litpic_local');
                    if(!empty($file)){
                        $logic = new \weapp\Wechat\logic\WechatLogic();
                        $pic_info = $logic->upFile('litpic_local');
                        if ($pic_info['state'] == 'SUCCESS') {
                            $litpic = $pic_info['url'];
                        } else {
                            $this->error($pic_info['state']);
                        }
                    }
                }
                /*--end*/

                /*新增永久图文素材*/
                if (!empty($litpic)) {
                    weapp_vendor('wechat.wechat', 'Wechat');
                    $wxconfig = M('weapp_wx_config')->where('token', $post['token'])->find();
                    if (!empty($wxconfig)) {
                        $materialObj = new \material($wxconfig);
                        /*删除永久素材*/
                        if (!empty($post['media_id'])) {
                            $materialObj->delMaterial($post['media_id']);
                        }
                        /*--end*/
                        $type = 'thumb';
                        $params = $materialObj->addMaterial(ROOT_PATH.ltrim($litpic, '/'), $type);
                        if ('thumb' == $type) {
                            $thumb_media_id = $params['media_id'];
                            $thumb_wx_img_url = $params['url'];
                        } else {
                            $media_id = $params['media_id'];
                            $wx_img_url = $params['url'];
                        }
                    }
                }
                /*--end*/

                /*摘要*/
                $intro = html_msubstr($post['intro'], 0, 110, true);
                /*--end*/

                /*保存到单图文表*/
                $data = array(
                    'intro' => $intro,
                    'update_time'  => getTime(),
                );
                $nowData = array_merge($post, $data);
                /*封面图是否被更改*/
                if (empty($litpic)) {
                    unset($nowData['litpic']);
                } else {
                    $nowData['litpic'] = $litpic;
                }
                if (!empty($media_id)) $nowData['media_id'] = $media_id;
                if (!empty($wx_img_url)) $nowData['wx_img_url'] = $wx_img_url;
                if (!empty($thumb_media_id)) $nowData['thumb_media_id'] = $thumb_media_id;
                if (!empty($thumb_wx_img_url)) $nowData['thumb_wx_img_url'] = $thumb_wx_img_url;
                /*--end*/
                $k_info = array();
                $k_info = M('weapp_wx_keyword')->where('id', $kid)->find();
                $result = DB::name('weapp_wx_img')->where('id', $k_info['pid'])->update($nowData);
                /*--end*/
                if (!empty($result)) {
                    $this->success("操作成功", weapp_url('Wechat/Keyword/img_index', array('token'=>$post['token'])));
                }
            }
            $this->error("操作失败");
        }

        $id = I('param.id');
        $info = array();
        if(0 < $id){
            $sql = "SELECT k.id,k.keyword,i.title,i.url,i.litpic,i.intro,i.token,i.aid,i.media_id FROM __PREFIX__weapp_wx_keyword k LEFT JOIN __PREFIX__weapp_wx_img i ON i.id = k.pid WHERE k.id = {$id} AND k.token = i.token AND k.type = 'IMG'";
            $data = DB::query($sql);
            $info = current($data);
            if (is_http_url($info['litpic'])) {
                $info['is_remote'] = 1;
                $info['litpic_remote'] = $info['litpic'];
            } else {
                $info['is_remote'] = 0;
                $info['litpic_local'] = $info['litpic'];
            }
        }
        $this->assign('field',$info);

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $this->assign('wechat_list', $wechat_list);

        return $this->fetch('img_edit');
    }

    /*
     * 图文回复 - 删除
     */
    public function img_del()
    {
        $id = $id_arr = I('del_id/a');
        if(is_array($id_arr)){
            foreach ($id_arr as $key => $val) {
                $id_arr[$key] = intval($val);
            }
            $id = implode(',', $id_arr);  
        } else {
            $id = intval($id);
        }
        if(!empty($id)){
            $row = M('weapp_wx_keyword')->where("id in ($id)")->select();
            $r = M('weapp_wx_keyword')->where("id in ($id)")->delete();
            if ($r) {
                $pid_arr = get_arr_column($row, 'pid');
                M('weapp_wx_img')->where('id', 'IN', $pid_arr)->delete();
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }else{
            $this->error('参数有误');
        }
    }

    /*
     * 组合图文消息列表
     */
    public function news_index()
    {
        $assign_data = array();
        $condition = array('a.type'=>'NEWS');
        // 获取到所有GET参数
        $param = I('param.');

        // 应用搜索条件
        foreach (['keywords','token'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.keyword'] = array('LIKE', "%{$param[$key]}%");
                } else {
                    $tmp_key = 'a.'.$key;
                    $condition[$tmp_key] = array('eq', $param[$key]);
                }
            }
        }

        /**
         * 数据查询
         */
        $count = DB::name('weapp_wx_keyword')->alias('a')->where($condition)->count();// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = DB::name('weapp_wx_keyword')
            ->field("a.id, a.keyword, a.pid, a.update_time, b.img_id, b.token, b.msg_id")
            ->alias('a')
            ->join('__WEAPP_WX_NEWS__ b', 'b.id = a.pid', 'LEFT')
            ->where($condition)
            ->order('a.id desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->getAllWithIndex('id');
        $pageStr = $pageObj->show(); // 分页显示输出
        $assign_data['pageStr'] = $pageStr; // 赋值分页输出
        $assign_data['list'] = $list; // 赋值数据集
        $assign_data['pageObj'] = $pageObj; // 赋值分页对象

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $assign_data['wechat_list'] = $wechat_list;

        $this->assign($assign_data);
        return $this->fetch('news_index');
    }

    /*
     * 组合图文 - 添加
     */
    public function news_add()
    {
        $this->checkWechat();

        if (IS_POST) {
            $post = I('post.');
            $img_id = trim($post['img_id'], ',');

            $map = array(
                'token' => $post['token'],
                'keyword' => trim($post['keyword']),
            );
            $k_info = M('weapp_wx_keyword')->where($map)->find();
            if (!empty($k_info)) {
                $this->error('该关键字已存在，请查看'.$this->wechatLogic->get_keyword_type($k_info['type']).'列表');
            }

            if(empty($img_id)){
                $this->error("请选择图文");
            }

            $data = array(
                'img_id'    => $img_id,
                'add_time'  => getTime(),
                'update_time'  => getTime(),
            );
            $nowData = array_merge($post, $data);
            $id = DB::name('weapp_wx_news')->add($nowData);
            if (0 < $id) {
                $k_data = array(
                    'pid'       => $id,
                    'type'      => 'NEWS',
                    'add_time'  => getTime(),
                    'update_time'  => getTime(),
                );
                $k_nowData = array_merge($post, $k_data);
                $row = M('weapp_wx_keyword')->add($k_nowData);
                if (!empty($row)) {
                    $this->success("操作成功", weapp_url('Wechat/Keyword/news_index', array('token'=>$post['token'])));
                }
            }
            $this->error("操作失败");
        }

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $this->assign('wechat_list', $wechat_list);

        return $this->fetch('news_add');
    }

    /*
     * 组合图文 - 编辑
     */
    public function news_edit()
    {
        $this->checkWechat();

        if (IS_POST) {
            $post = I('post.');
            $kid = I('post.kid');
            $img_id = trim($post['img_id'], ',');

            $map = array(
                'id'        => array('NEQ', $kid),
                'token' => $post['token'],
                'keyword' => trim($post['keyword']),
            );
            $k_info = M('weapp_wx_keyword')->where($map)->find();
            if (!empty($k_info)) {
                $this->error('该关键字已存在，请查看'.$this->wechatLogic->get_keyword_type($k_info['type']).'列表');
            }

            $k_data = array(
                'type'      => 'NEWS',
                'update_time'=> getTime(),
            );
            $k_nowData = array_merge($post, $k_data);
            $row = M('weapp_wx_keyword')->where('id', $kid)->update($k_nowData);
            if (!empty($row)) {
                $data = array(
                    'update_time'  => getTime(),
                );
                $nowData = array_merge($post, $data);
                if (!empty($img_id)) {
                    $nowData['img_id'] = $img_id;
                } else {
                    unset($nowData['img_id']);
                }
                $k_info = array();
                $k_info = M('weapp_wx_keyword')->where('id', $kid)->find();
                $result = DB::name('weapp_wx_news')->where('id', $k_info['pid'])->update($nowData);
                if (!empty($result)) {
                    $this->success("操作成功", weapp_url('Wechat/Keyword/news_index', array('token'=>$post['token'])));
                }
            }
            $this->error("操作失败");
        }

        $id = I('param.id');
        $info = array();
        if(0 < $id){
            $sql = "SELECT k.id,k.pid,k.keyword,i.img_id,i.token FROM __PREFIX__weapp_wx_keyword k LEFT JOIN __PREFIX__weapp_wx_news i ON i.id = k.pid WHERE k.id = {$id} AND k.token = i.token AND k.type = 'NEWS'";
            $data = DB::query($sql);
            $info = current($data);
        }
        $this->assign('field', $info);

        $wechat_list = M('weapp_wx_config')->field('id,wxname,token')->getAllWithIndex('token');
        $this->assign('wechat_list', $wechat_list);

        return $this->fetch('news_edit');
    }

    /*
     * 组合图文 - 删除
     */
    public function news_del()
    {
        $id = $id_arr = I('del_id/a');
        if(is_array($id_arr)){
            foreach ($id_arr as $key => $val) {
                $id_arr[$key] = intval($val);
            }
            $id = implode(',', $id_arr);  
        } else {
            $id = intval($id);
        }
        if(!empty($id)){
            $row = M('weapp_wx_keyword')->where("id in ($id)")->select();
            $r = M('weapp_wx_keyword')->where("id in ($id)")->delete();
            if ($r) {
                if (!empty($row)) {
                    $pid_arr = get_arr_column($row, 'pid');
                    M('weapp_wx_news')->where('id', 'IN', $pid_arr)->delete();
                }
                $this->success('删除成功');
            } else {
                $this->error('删除失败');
            }
        }else{
            $this->error('参数有误');
        }
    }

    /*
     * 组合图文 - 预览
     */
    public function news_preview()
    {
        $id = I('param.id/d', 0);
        $first = array();
        $data = array();
        $news = M('weapp_wx_news')->field('img_id')->where(array('id'=>$id))->find();
        if (!empty($news['img_id'])) {
            $arr = explode(',', $news['img_id']);
            $img_list = M('weapp_wx_img')->where(array('id'=>array('in',$arr)))->getAllWithIndex('id');
            foreach ($arr as $key => $val) {
                $data[] = $img_list[$val];
            }
            if (isset($data[0])) {
                $first = $data[0];
                unset($data[0]);
            }
        }
        $this->assign('first', $first);
        $this->assign('list', $data);
        return $this->fetch('news_preview');
    }
    
    /*
     * 选择组合图文
     */
    public function news_select()
    {
        $assign_data = array();
        $condition = array();
        // 获取到所有GET参数
        $param = I('param.');
        $token = I('param.token/s');

        // 应用搜索条件
        foreach (['keywords','aid'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['i.title'] = array('LIKE', "%{$param[$key]}%");
                } else if ($key == 'aid' && '' !== $param[$key]) {
                    if (1 == $param[$key]) {
                        $condition['i.'.$key] = array('egt', $param[$key]);
                    } else if (0 == $param[$key]) {
                        $condition['i.'.$key] = array('eq', $param[$key]);
                    }
                } else {
                    $condition['k.'.$key] = array('eq', $param[$key]);
                }
            }
        }

        $condition['k.token'] = $token;
        $condition['k.type'] = 'IMG';

        /**
         * 数据查询
         */
        $count = DB::name('weapp_wx_keyword')->alias('k')
            ->join('__WEAPP_WX_IMG__ i', 'i.id = k.pid', 'LEFT')
            ->where($condition)
            ->count('k.id');// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = DB::name('weapp_wx_keyword')
            ->field("k.id,k.pid,k.keyword,k.update_time,i.title,i.url,i.litpic,i.intro,i.aid")
            ->alias('k')
            ->join('__WEAPP_WX_IMG__ i', 'i.id = k.pid', 'LEFT')
            ->where($condition)
            ->order('i.id desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->getAllWithIndex('id');
        $pageStr = $pageObj->show(); // 分页显示输出
        $assign_data['pageStr'] = $pageStr; // 赋值分页输出
        $assign_data['list'] = $list; // 赋值数据集
        $assign_data['pageObj'] = $pageObj; // 赋值分页对象

        $this->assign($assign_data);

        return $this->fetch('news_select');
    }

    /**
     * 选择文档
     */
    public function news_select_archives()
    {
        $assign_data = array();
        $condition = array();
        // 获取到所有GET参数
        $param = I('param.');
        $typeid = I('typeid/d', 0);

        // 应用搜索条件
        foreach (['keywords','typeid'] as $key) {
            if (isset($param[$key]) && $param[$key] !== '') {
                if ($key == 'keywords') {
                    $condition['a.title'] = array('LIKE', "%{$param[$key]}%");
                } else if ($key == 'typeid') {
                    $result = model('Arctype')->getHasChildren($param[$key]);
                    $condition['a.typeid'] = array('IN', array_keys($result));
                } else {
                    $condition['a.'.$key] = array('eq', $param[$key]);
                }
            }
        }

        $condition['a.arcrank'] = 0;
        $condition['a.status'] = 1;

        /**
         * 数据查询，搜索出主键ID的值
         */
        $count = DB::name('archives')->alias('a')->where($condition)->count('aid');// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = DB::name('archives')
            ->field("a.aid")
            ->alias('a')
            ->where($condition)
            ->order('a.aid desc')
            ->limit($pageObj->firstRow.','.$pageObj->listRows)
            ->getAllWithIndex('aid');

        /**
         * 完善数据集信息
         * 在数据量大的情况下，经过优化的搜索逻辑，先搜索出主键ID，再通过ID将其他信息补充完整；
         */
        if ($list) {
            $aids = array_keys($list);
            $fields = "b.*, a.*, a.aid as aid";
            $row = DB::name('archives')
                ->field($fields)
                ->alias('a')
                ->join('__ARCTYPE__ b', 'a.typeid = b.id', 'LEFT')
                ->where('a.aid', 'in', $aids)
                ->getAllWithIndex('aid');
            foreach ($list as $key => $val) {
                $arcurl = arcurl('home/Article/view', $row[$val['aid']], true, true);
                $row[$val['aid']]['arcurl'] = $arcurl;
                /*封面图*/
                $litpic = $row[$val['aid']]['litpic'];
                if (!empty($litpic) && !is_http_url($litpic)) {
                    $row[$val['aid']]['litpic'] = request()->domain().$litpic;
                }
                /*--end*/
                $list[$key] = $row[$val['aid']];
            }
        }
        $pageStr = $pageObj->show(); // 分页显示输出
        $assign_data['pageStr'] = $pageStr; // 赋值分页输出
        $assign_data['list'] = $list; // 赋值数据集
        $assign_data['pageObj'] = $pageObj; // 赋值分页对象

        /*允许发布文档列表的栏目*/
        $select_html = allow_release_arctype($typeid);
        $this->assign('select_html',$select_html);
        /*--end*/

        $this->assign($assign_data);

        return $this->fetch('news_select_archives');
    }

    /**
     * 群发图文
     */
    public function news_sendall()
    {
        $pid = I('param.pid/d', 0);
        $token = I('param.token/s', '');
        $msgtype = I('param.msgtype/s', '');
        // 公众号基本信息
        $wxconfig = M('weapp_wx_config')->where('token', $token)->find();
        if (!in_array($wxconfig['type'], array(2,4))) {
            $this->error('必须通过微信认证，才能正常使用！');
        }

        $articles = array();

        // 获取组合图文对应的单图文ID
        $img_ids = M('weapp_wx_news')->where('id', $pid)->getField('img_id');
        // 获取对应单图文列表
        $wximgRow = M('weapp_wx_img')->where('id','in',$img_ids)->order("FIELD(id, $img_ids)")->select();
        if (!empty($wximgRow)) {
            /*获取相应站内文档*/
            $aids = get_arr_column($wximgRow, 'aid');
            $row = M('archives')
                ->field('b.*, a.*, a.aid as aid')
                ->alias('a')
                ->join('__ARCTYPE__ b', 'a.typeid = b.id', 'LEFT')
                ->where('a.aid', 'in', $aids)
                ->getAllWithIndex('aid');
            $channeltypeList = M('channeltype')->field('id,ctl_name,table')->getAllWithIndex('id');
            foreach ($row as $key => $val) {
                $ctl_name = $channeltypeList[$val['channel']]['ctl_name'];
                $val['arcurl'] = arcurl('home/'.$ctl_name.'/view', $val, true, true).'#wechat_redirect';
                /*获取内容*/
                $table = $channeltypeList[$val['channel']]['table'];
                $content = M($table.'_content')->where('aid',$val['aid'])->getField('content');
                $val['content'] = htmlspecialchars_decode($content);
                /*--end*/

                $row[$key] = $val;
            }
            /*--end*/
            if (!empty($row)) {
                foreach ($wximgRow as $key => $val) {
                    $aid = $val['aid'];
                    if (empty($row[$aid])) {
                        continue;
                    }
                    $arcurl = $row[$aid]['arcurl'];
                    $author = $row[$aid]['author'];
                    $title = $row[$aid]['title'];
                    $content = $row[$aid]['content'];
                    $articles[] = array(
                        'thumb_media_id'    => $val['thumb_media_id'],
                        'author'    => $author,
                        'title'    => $title,
                        'content_source_url'    => $arcurl,
                        'content'    => $content,
                        'show_cover_pic'    => 0,
                    );
                }
            }
        }

        if (empty($articles)) {
            $this->error('该组合图文没有站内文档，群发无效！');
        }

        weapp_vendor('wechat.wechat', 'Wechat');
        if (!empty($articles)) {
            $materialObj = new \material($wxconfig);
            $params = $materialObj->addNews($articles); // 新增永久图文素材
            if (!empty($params['media_id'])) {
                $media_id = $params['media_id'];
                $grouphairObj = new \grouphair($wxconfig);
                $params = $grouphairObj->sendall($media_id, $msgtype);
                // $params = $grouphairObj->preview($media_id, $msgtype, 'ou4hA0uaSBEw-BKbqhkEZgZIwGrQ');
                /*更新群发之后的错误码*/
                $updateData = array(
                    'msg_id' => isset($params['msg_id']) ? $params['msg_id'] : '',
                    'errcode' => isset($params['errcode']) ? $params['errcode'] : '',
                    'is_del'    => 0,
                    'update_time'   => getTime(),
                );
                M('weapp_wx_news')->where('id', $pid)->update($updateData);
                /*--end*/
                if (isset($params['errcode']) && 0 == $params['errcode']) {
                    $this->success("操作成功");
                }
            }
            $this->error('操作失败');
        }

        $this->error('参数有误');
    }

    /**
     * 删除群发【订阅号与服务号认证后均可用】
     */
    public function news_mass_delete()
    {
        $token = I('param.token/s', '');
        $msg_id = I('param.msg_id/s', '');

        if (empty($msg_id)) {
            $this->error('该图文没有群发，撤销无效！');
        }

        // 公众号基本信息
        $wxconfig = M('weapp_wx_config')->where('token', $token)->find();
        if (!in_array($wxconfig['type'], array(2,4))) {
            $this->error('必须通过微信认证，才能正常使用！');
        }
        weapp_vendor('wechat.wechat', 'Wechat');
        $grouphairObj = new \grouphair($wxconfig);
        $params = $grouphairObj->delete($msg_id);
        if (isset($params['errcode']) && 0 == $params['errcode']) {
            M('weapp_wx_news')->where(array('token'=>$token, 'msg_id'=>$msg_id))->update(array('is_del'=>1, 'update_time'=>getTime()));
            $this->success("操作成功");
        }

        $this->error("操作失败");
    }
    
    /**
     * 验证公众号权限
     */
    public function checkWechat()
    {
        $token = I('param.token/s', '');
        $wxconfig = M('weapp_wx_config')->where("token = '{$token}'")->find();
        if (!in_array($wxconfig['type'], array(2,4))) {
            $this->error('必须通过微信认证，才能正常使用！');
        }
    }
}