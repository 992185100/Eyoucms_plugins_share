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

namespace weapp\Systemdoctor\controller;

use think\Backup;
use think\Config;
use think\Db;
use think\Page;
use app\common\controller\Weapp;
use think\Request;
use weapp\Systemdoctor\model\AdminLogModel;
use app\common\model\Weapp as WeappModel;
use weapp\Systemdoctor\logic\FilemanagerLogic;

/**
 * 插件的控制器
 */
class Systemdoctor extends Weapp
{
    //不允许存在php文件
    public $php_dir = [
        "./data/backup",
        "./data/conf",
        "./public/static",
    ];
    //仅存在seesion文件,有前缀无拓展名
    public $session_dir = "./data/session";
    //提示多余表->文件夹存在数据库不存在的即多余
    public $compare_sql_dir = "./data/schema";
    //仅存在.sql文件
    public $sql_dir = "./data/sqldata";
    //按输入的文件类型检测文件目录
    public $check_dir = ["./template", "./uploads"];
    //代码特征
    public $info = 'eval|cmd|_GET|_POST';
    // 在线模板管理
    public $filemanagerLogic;
    public $baseDir = '';
    public $maxDir = '';
    public $globalTpCache = array();

    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();
        /*插件基本信息*/
        $this->weappInfo = $this->getWeappInfo();
        $this->assign('weappInfo', $this->weappInfo);
        /*--end*/
        $this->filemanagerLogic = new FilemanagerLogic();
        $this->globalTpCache = $this->filemanagerLogic->globalTpCache;
        $this->baseDir = $this->filemanagerLogic->baseDir; // 服务器站点根目录绝对路径
        $this->maxDir = $this->filemanagerLogic->maxDir; // 默认文件管理的最大级别目录
    }

    /**
     * 病毒扫描
     */
    public function virus_scan()
    {
        if (IS_POST) {
            $type = input('type/s');
            $info = input('info/s');
            $type = explode("|", $type);
            foreach ($type as $k => $val) {
                $type[$k] = "." . trim($val);
            }
            if(empty($info)){
                $info = $this->info;
            }
            $list = [];
            //不允许存在php文件
            foreach ($this->php_dir as $val) {
                $data = [];
                $data = getDirFile($val);
                foreach ($data as $v) {
                    if (strstr($v, '.php')) {
                        $list[] = $val . '/' . $v;
                    }
                }
            }
            //仅存在seesion文件,有前缀无拓展名
            $session_dir = glob($this->session_dir . '_*');
            $data        = [];
            foreach ($session_dir as $val) {
                $data = array_merge($data, getDirFile($val));
            }
            foreach ($data as $val) {
                if (substr($val, 0, 5) !== "sess_") {
                    $list[] = $session_dir . '/' . $val;
                }
            }
            //仅存在.sql文件
            $sql_dir = getDirFile($this->sql_dir);
            foreach ($sql_dir as $val) {
                if (!(strstr($val, '.htaccess') || strstr($val, '.sql'))) {
                    $list[] = $this->sql_dir . '/' . $val;
                }
            }
            //提示多余表->文件夹存在数据库不存在的即多余
            !defined('PREFIX') && define('PREFIX', Config::get('database.prefix'));// 数据库表前缀
            $table           = Db::query("show tables");
            $table           = array_map('reset', $table);
            $compare_sql_dir = getDirFile($this->compare_sql_dir);
            foreach ($compare_sql_dir as $key => $val) {
                if (strstr($val, '.htaccess')) {
                    unset($compare_sql_dir[$key]);
                } elseif (strstr($val, '.php') && substr($val, 0, 3) == "ey_") {
                    $val = str_replace("ey_", PREFIX, $val);
                    $val = str_replace(".php", '', $val);
                    if (!in_array($val, $table)) {
                        $val    = str_replace(PREFIX, "ey_", $val);
                        $list[] = $this->compare_sql_dir . '/' . $val . '.php';
                    }
                } else {
                    $list[] = $this->compare_sql_dir . '/' . $val;
                }
            }
            //按type检测文件目录
            foreach ($this->check_dir as $val) {
                $data = [];
                $data = getDirFile($val);
                foreach ($data as $v) {
                    foreach ($type as $t) {
                        if (strstr($v, $t) && !in_array($val . '/' . $v, $list)) {
                            //多个文件类型下,可能有同时存在多个类型的情况
                            $list[] = $val . '/' . $v;
                        }
                    }
                }
            }
            //检测页面保存目录
            $html_arcdir = tpCache("seo.seo_html_arcdir");
            //存在页面保存目录则去页面保存目录扫描
            if ($html_arcdir) {
                $html_arcdir = '.' . $html_arcdir;
                $html_list   = getDirFile($html_arcdir);
                if ($html_list) {
                    foreach ($html_list as $val) {
                        if (substr($val, -5) != ".html") {
                            $list[] = $html_arcdir . '/' . $val;
                        }
                    }
                }
            } else {
                //不存在页面保存目录则去根目录根据数据库信息扫描
                $arctype_list = Db::name('arctype')->where(['parent_id' => 0])->group('dirpath')->column('dirpath');
                if ($arctype_list) {
                    foreach ($arctype_list as $k => $v) {
                        $arctype_list[$k] = "." . $v;
                        $data             = [];
                        $data             = getDirFile($arctype_list[$k]);
                        if ($data) {
                            foreach ($data as $val) {
                                if (substr($val, -5) != ".html") {
                                    $list[] = $arctype_list[$k] . '/' . $val;
                                }
                            }
                        }
                    }
                }
            }
            //检测代码特征
            $assgin_list  = [];
            foreach ($list as $key => $value){
                $str='';
                $f_content = fopen($value, 'r');
                while(!feof($f_content)) { $str .= fgets($f_content,1024); }
                fclose($f_content);
                if(preg_match("#(".$info.")[ \r\n\t]{0,}([\[\(])#i", $str))
                {
                    $assgin_list[] = iconv("gb2312//IGNORE","utf-8",$value);
                }
            }

            if (empty($assgin_list)) {
                $this->success('没发现疑似木马文件！', null, '', 2);
            }

            $this->assign('list', $assgin_list);
        }
        return $this->fetch();
    }

    /**
     * 病毒扫描删除文件++++清除缓存
     */
    public function delete_file()
    {
        if (IS_AJAX) {
            $filename = input('filename/s');
            if (!empty($filename)) {
                //删除文件
                $filename = iconv('utf-8', "gb2312//IGNORE", $filename);
                if (file_exists($filename)) {
                    unlink($filename);
                    $this->success("删除成功!");
                }
                $this->error("删除失败!");
            } else {
                //清除缓存
                delFile(rtrim(HTML_ROOT, '/'));
                $this->success("清除缓存成功!");

            }
        }
        $this->error("非法访问!");
    }

    /**
     * 检测当前版本的数据库是否与官方一致
     */
    public function check_database()
    {
        if (IS_AJAX_POST) {
            /*------------------检测目录读写权限----------------------*/
            $tmp_str     = 'L2luZGV4LnBocD9tPWFwaSZjPVNlcnZpY2UmYT1nZXRfZGF0YWJhc2VfdHh0';
            $service_url = base64_decode(config('service_ey')) . base64_decode($tmp_str);
            $url         = $service_url . '&version=' . getCmsVersion();
            $context     = stream_context_set_default(array('http' => array('timeout' => 3, 'method' => 'GET')));
            $response    = @file_get_contents($url, false, $context);
            $params      = json_decode($response, true);
            if (false == $params) {
                $this->error('连接升级服务器超时，请刷新重试，或者联系技术支持！', null, ['code' => 2]);
            }

            if (is_array($params)) {
                if (1 == intval($params['code'])) {
                    /*------------------组合本地数据库信息----------------------*/
                    $dbtables       = Db::query('SHOW TABLE STATUS');
                    $local_database = array();
                    foreach ($dbtables as $k => $v) {
                        $table = $v['Name'];
                        if (preg_match('/^' . PREFIX . '/i', $table)) {
                            $local_database[$table] = [
                                'name'  => $table,
                                'field' => [],
                            ];
                        }
                    }
                    /*------------------end----------------------*/

                    /*------------------组合官方远程数据库信息----------------------*/
                    $info      = $params['info'];
                    $info      = preg_replace("#[\r\n]{1,}#", "\n", $info);
                    $infos     = explode("\n", $info);
                    $infolists = [];
                    foreach ($infos as $key => $val) {
                        if (!empty($val)) {
                            $arr                = explode('|', $val);
                            $infolists[$arr[0]] = $val;
                        }
                    }
                    /*------------------end----------------------*/

                    /*------------------校验数据库是否合格----------------------*/
                    foreach ([1] as $testk => $testv) {
                        $error = '';
                        // 对比数据表字段数量
                        foreach ($infolists as $k1 => $v1) {
                            $arr1 = explode('|', $v1);

                            if (1 >= count($arr1)) {
                                continue; // 忽略不对比的数据表
                            }

                            $fieldArr = explode(',', $arr1[1]);
                            $table    = preg_replace('/^ey_/i', PREFIX, $arr1[0]);
                            //判断是否缺少表
                            if (empty($local_database[$table])) {
                                $error .= $table . ' 数据表缺失!</br>';
                                continue;
                            }
                            $local_fields                    = Db::getFields($table); // 本地数据表字段列表
                            $local_database[$table]['field'] = $local_fields;
                            if (count($local_fields) < count($fieldArr)) {
                                //对比缺少的字段
                                $err_field = '';
                                foreach ($fieldArr as &$k2) {
                                    if (empty($local_fields[$k2])) {
                                        $err_field .= $k2 . '，';
                                    }
                                }
                                $error .= $table . ' 数据表缺失字段 ' . trim($err_field, '，') . '</br>';
                            }
                        }
                        if ($error != '') {
                            $this->error($error, null, ['code' => 2]);
                        } else {
                            $this->success('检测通过!');
                        }
                    }
                    /*------------------end----------------------*/
                } else if (2 == intval($params['code'])) {
                    $this->error('官方缺少版本号' . getCmsVersion() . '的数据库比较文件，请第一时间联系技术支持！', null, ['code' => 2]);
                }
            }

        }
        /*------------------end----------------------*/

        return $this->fetch('check_database');
    }

    /**
     * SQL命令行
     */
    public function sql_command()
    {
        $data = Db::query("SHOW TABLE STATUS");
        foreach ($data as $key => $val) {
            $data[$key]['count'] = Db::table($val['Name'])->count();
        }
        return $this->fetch('sql_command', ['data' => $data]);
    }

    /**
     * SQL命令行-获取详细表结构
     */
    public function sql_details()
    {
        if (IS_AJAX) {
            $table = input('table/s');
            if (empty($table)) {
                $this->error('没有指定数据表');
            }
            $data = Db::query("show create table " . $table);
            $info = $data[0]['Create Table'];
            $info = "<xmp>" . trim($info) . "</xmp>";

            $this->success('成功', '', $info);
        }
        $this->error('非法访问');
    }

    /**
     * SQL命令行运行
     */
    public function run_sql()
    {
        if (IS_AJAX) {
            $command = input('command/s');
            if (empty($command)) {
                $this->error('没有运行命令');
            }
            $command     = trim($command);
            $str_command     = str_replace(array("\r\n", "\r", "\n"), " ", $command);
//            $str_command = strtoupper($command);
            $delete      = $this->startsWith($str_command, 'DELETE');
            if ($delete) {
                $info['msg'] = '删除\'数据表\'或\'数据库\'的语句不允许在这里执行';
                $this->error($info['msg']);
            }

            $select = $this->startsWith($str_command, 'SELECT');
            if ($select) {
                // 查询
                // 启动事务
                Db::startTrans();
                try {
                    $data = Db::query($command);
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    echo $e;
                    exit();
                }
                if (count($data) <= 0) {
                    $info['msg'] = "运行SQL：" . $command . "成功，无返回记录！";
                } else {
                    if (count($data) > 50) {
                        $data = array_splice($data, 50);
                    }
                    $info['msg'] = "运行SQL：" . $command . "成功，共有" . count($data) . "条记录，最大返回50条！";
                    foreach ($data as $key => $val) {
                        $info['msg'] .= "</br>第 " . ($key + 1) . " 条<hr>";
                        foreach ($val as $k => $v) {
                            $info['msg'] .= $k . "：" . $v . "</br>";
                        }
                    }
                }
            } else {
                //更新/插入
                $arr_command = explode(";", $str_command);
                $i = 0;
                $err_msg = '';
                foreach ($arr_command as $val){
                    if (!empty($val)){
                        // 启动事务
                        Db::startTrans();
                        try {
                            $arr_data = Db::query($val);
                            // 提交事务
                            Db::commit();
                            $i+=1;
                        } catch (\Exception $e) {
                            // 回滚事务
                            Db::rollback();
                            $err_msg .= '错误未执行语句：'.$val .'</br>';
                            continue;
                        }
                    }
                }
                if ($i>0){
                    $info['msg'] = '成功执行 '. $i .' 条SQL语句</br>'.$err_msg;
                }else{
                    $info['msg'] = $err_msg;

                }
            }
            $this->success('成功', '', $info);
        }
        $this->error('非法访问');
    }

    /**
     * 插件后台管理 - 列表
     */
    public function index()
    {
        $trojan_horse = tpCache('weapp.weapp_check_illegal_open');
        $this->assign('trojan_horse', $trojan_horse);
        return $this->fetch('index');
    }

    /**
     * 诊断数据表
     */
    public function check_table()
    {
        if (IS_POST) {
            $r = Db::name('admin_log')->where("admin_id is NULL OR admin_id = ''")
                ->update([
                    'admin_id' => 0,
                    'log_time' => getTime(),
                ]);
            if ($r) {
                $this->success('修复成功');
            }
            $this->error('修复失败');
        }
        $this->error('非法访问');
    }

    /**
     * 上传sql文件
     */
    public function restoreUpload()
    {
        if (IS_POST) {
            $file = request()->file('sqlfile');
            if (empty($file)) {
                $this->error('请上传sql文件');
            }
            // 移动到框架应用根目录/data/sqldata/ 目录下
            $path                    = tpCache('global.web_sqldatapath');
            $path                    = !empty($path) ? $path : config('DATA_BACKUP_PATH');
            $path                    = trim($path, '/');
            $image_upload_limit_size = intval(tpCache('basic.file_size') * 1024 * 1024);
            $info                    = $file->validate(['size' => $image_upload_limit_size, 'ext' => 'sql,gz'])->move($path, $_FILES['sqlfile']['name']);
            if ($info) {
                //上传成功 获取上传文件信息
                $file_path_full = $info->getPathName();
                if (file_exists($file_path_full)) {
                    $sqls = Backup::parseSql($file_path_full);
                    if (Backup::install($sqls)) {
                        /*清除缓存*/
                        delFile(RUNTIME_PATH);
                        /*--end*/
                        $this->success("执行sql成功");
                    } else {
                        $this->error('执行sql失败');
                    }
                } else {
                    $this->error('sql文件上传失败');
                }
            } else {
                //上传错误提示错误信息
                $this->error($file->getError());
            }
        }
    }

    /**
     * 检测站点根目录权限
     */
    public function check_permission()
    {
        if (IS_AJAX) {
            /*------------------检测目录读写权限----------------------*/
            $filelist = glob('*', GLOB_ONLYDIR);
            $dirs     = array();
            $i        = -1;
            foreach ($filelist as $filename) {
                $curdir = $filename;
                if (!isset($dirs[$curdir])) {
                    $dirs[$curdir] = $this->TestIsFileDir($curdir);
                }
                if ($dirs[$curdir]['isdir'] == FALSE) {
                    continue;
                } else {
                    @tp_mkdir($curdir, 0777);
                    $dirs[$curdir] = $this->TestIsFileDir($curdir);
                }
                $i++;
            }

            if ($i > -1) {
                $n        = 0;
                $dirinfos = '';
                foreach ($dirs as $curdir) {
                    $dirinfos .= $curdir['name'] . "&nbsp;&nbsp;状态：";
                    if ($curdir['writeable']) {
                        $dirinfos .= "<font color='green'>[√正常]</font>";
                    } else {
                        $n++;
                        $dirinfos .= "<font color='red'>[×不可写]</font>";
                    }
                    $dirinfos .= "<br />";
                }
                $title = "已检测站点有 <font color='red'>{$n}</font> 处没有写入权限：<br />";
                $title .= "<font color='red'>问题分析（如有问题，请咨询技术支持）：<br />";
                $title .= "1、检查站点目录的用户组与所有者，禁止是 root ;<br />";
                $title .= "2、检查站点目录的读写权限，一般权限值是 0755 ;<br />";
                $title .= "</font><br />站点根目录列表如下：<br />";
                $msg   = $title . $dirinfos;
                $this->error($msg);
            }
        }

        return $this->fetch('check_permission');
    }

    /**
     * 测试目录路径是否有读写权限
     * @param string $dirname 文件目录路径
     * @return array
     */
    private function TestIsFileDir($dirname)
    {
        $dirs         = array('name' => '', 'isdir' => FALSE, 'writeable' => FALSE);
        $dirs['name'] = $dirname;
        tp_mkdir($dirname);
        if (is_dir($dirname)) {
            $dirs['isdir']     = TRUE;
            $dirs['writeable'] = $this->TestWriteAble($dirname);
        }
        return $dirs;
    }

    /**
     * 测试目录路径是否有写入权限
     * @param string $d 目录路劲
     * @return boolean
     */
    private function TestWriteAble($d)
    {
        $tfile = '_eyout.txt';
        $fp    = @fopen($d . $tfile, 'w');
        if (!$fp) {
            return false;
        } else {
            fclose($fp);
            $rs = @unlink($d . $tfile);
            return true;
        }
    }


    function startsWith($haystack, $needle)
    {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * 检测重复文档
     */
    public function repeat_archives_index()
    {
        $assign_data = array();
        $testing = input('param.testing/d');

        if (!empty($testing)) {
            $condition = array();
            // 获取到所有GET参数
            $param = input('param.');

            // 应用搜索条件
            foreach (['keywords','channel'] as $key) {
                if (isset($param[$key]) && $param[$key] !== '') {
                    if ($key == 'keywords') {
                        $condition['a.title'] = array('LIKE', "%{$param[$key]}%");
                    } else if ($key == 'channel' && !empty($param[$key])) {
                        $condition['a.channel'] = $param[$key];
                    } else {
                        $condition['a.'.$key] = array('eq', $param[$key]);
                    }
                }
            }

            // 多语言
            $condition['a.lang'] = array('eq', $this->admin_lang);
            // 回收站
            $condition['a.is_del'] = array('eq', 0);

            /**
             * 数据查询，搜索出主键ID的值
             */
            $row = Db::name('archives')->alias('a')->field('GROUP_CONCAT(aid) as aids, count(aid) as nums')
                ->where($condition)
                ->group('a.title')
                ->having('count(a.aid) > 1')
                ->select();
            $count = 0;
            $aids = '';
            foreach ($row as $key => $val) {
                $count += $val['nums'];
                $aids .= ','.$val['aids'];
            }
            $aids = trim($aids, ',');
            $aidArr = explode(',', $aids);
            
            $pagesize = input('param.pagesize/d', 100);
            $pageObj = new Page($count, $pagesize);// 实例化分页类 传入总记录数和每页显示的记录数
            $fields = "b.*, a.*, a.aid as aid";
            $list = DB::name('archives')
                ->field($fields)
                ->alias('a')
                ->join('__ARCTYPE__ b', 'a.typeid = b.id', 'LEFT')
                ->where(['aid'=>['IN', $aidArr]])
                ->order('title asc, aid asc')
                ->limit($pageObj->firstRow.','.$pageObj->listRows)
                ->select();
            foreach ($list as $key => $val) {
                $val['arcurl'] = get_arcurl($val);
                $list[$key] = $val;
            }

            $pageStr = $pageObj->show(); // 分页显示输出
            $assign_data['pageStr'] = $pageStr; // 赋值分页输出
            $assign_data['list'] = $list; // 赋值数据集
            $assign_data['pageObj'] = $pageObj; // 赋值分页对象
        }

        /* 模型 */
        $map = [
            'id'    => ['NOT IN', [6,8]],
            'status'    => 1,
        ];
        $channeltype_list = model('Channeltype')->getAll('id,title,nid', $map, 'id');
        $assign_data['channeltype_list'] = $channeltype_list;

        $assign_data['testing'] = $testing;
        $deltype = input('param.deltype/s');
        $assign_data['deltype'] = $deltype;

        $this->assign($assign_data);
        
        return $this->fetch('repeat_archives_index');
    }
    
    /**
     * 删除文档
     */
    public function repeat_archives_del()
    {
        if (IS_POST) {
            $archivesLogic = new \app\admin\logic\ArchivesLogic;
            $archivesLogic->del();
        }
    }

    /**
     * SQL命令行
     */
    public function sql_reset()
    {
        if (IS_AJAX_POST){
            $post = input('post.');
            $table = $post['table'];
            $table = htmlspecialchars_decode($table);
            $table = json_decode($table,true);
            foreach ($table as $k) {
                Db::query('alter table '.$k.' AUTO_INCREMENT 1');
            }
            return true;
//            Db::query('truncate table '.$table);截断
        }
        $data = Db::query("SHOW TABLE STATUS");
        foreach ($data as $key => $val) {
            $data[$key]['count'] = Db::table($val['Name'])->count();
        }
        return $this->fetch('sql_reset', ['data' => $data]);
    }

    /**
     * 后台操作日志
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function admin_log()
    {
        $list = array();
        $keywords = I('keywords/s');

        $map = array();
        if (!empty($keywords)) {
            $map['log_info'] = array('LIKE', "%{$keywords}%");
        }

        $count = AdminLogModel::where($map)->count('log_id');// 查询满足要求的总记录数
        $pageObj = new Page($count, config('paginate.list_rows'));// 实例化分页类 传入总记录数和每页显示的记录数
        $list = AdminLogModel::with('admin')->where($map)->order('log_id desc')->limit($pageObj->firstRow.','.$pageObj->listRows)->select();
        $pageStr = $pageObj->show(); // 分页显示输出
        $this->assign('list', $list); // 赋值数据集
        $this->assign('pageStr', $pageStr); // 赋值分页输出
        $this->assign('pageObj', $pageObj); // 赋值分页对象

        return $this->fetch('admin_log');
    }

    /**
     * 删除后台操作日志
     */
    public function del_admin_log()
    {
        $id_arr = I('del_id/a');
        $id_arr = eyIntval($id_arr);
        if(!empty($id_arr)){
            $r = AdminLogModel::where("log_id",'IN',$id_arr)->delete();
            if($r){
                adminLog('日志清除');
                $this->success("操作成功!");
            }else{
                $this->error("操作失败!");
            }
        }else{
            $this->error("参数有误!");
        }
    }

    /**
     * 插件后台管理 - 列表
     */
    public function data_replace_index()
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
        return $this->fetch('data_replace_index');
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
     * 内容替换主方法
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
     * 验证码管理
     */
    public function vertify()
    {
        // 获取插件数据
        $row = WeappModel::get(array('code' => $this->weappInfo['code']));
        if ($this->request->isPost()) {
            // 获取post参数
            $inc_type = input('inc_type/s', 'admin_login');
            $param = $this->request->only('captcha');
            $config = json_decode($row->data, true);
            if ('default' == $inc_type) {
                if (isset($config[$inc_type])) {
                    $config['captcha'][$inc_type] = array_merge($config['captcha'][$inc_type], $param['captcha'][$inc_type]);
                } else {
                    $config['captcha'][$inc_type] = $param['captcha'][$inc_type];
                }
            } else {
                $config['captcha'][$inc_type]['is_on'] = $param['captcha'][$inc_type]['is_on'];
                if (isset($config['captcha'][$inc_type]['config'])) {
                    $config['captcha'][$inc_type]['config'] = array_merge($config['captcha'][$inc_type]['config'], $param['captcha'][$inc_type]['config']);
                } else {
                    $config['captcha'][$inc_type]['config'] = $param['captcha'][$inc_type]['config'];
                }
            }
            // 转json赋值
            $row->data = json_encode($config);
            // 更新数据
            $r = $row->save();

            if ($r !== false) {
                adminLog('编辑验证码：插件配置'); // 写入操作日志
                $this->success("操作成功!", weapp_url('Systemdoctor/Systemdoctor/vertify', ['inc_type'=>$inc_type]));
            }
            $this->error("操作失败!");
        }

        $inc_type = input('param.inc_type/s', 'admin_login');

        // 获取配置JSON信息转数组
        $config = json_decode($row->data, true);
        $baseConfig = Config::get("captcha");
        if ('default' == $inc_type) {
            $row = isset($config['captcha']) ? $config['captcha'] : $baseConfig;
        } else {
            if (isset($config['captcha'][$inc_type])) {
                $row = $config['captcha'][$inc_type];
            } else {
                $baseConfig[$inc_type]['config'] = !empty($config['captcha']['default']) ? $config['captcha']['default'] : $baseConfig['default'];
                $row = $baseConfig[$inc_type];
            }
        }
        $this->assign('row', $row);
        $this->assign('inc_type', $inc_type);
        return $this->fetch('vertify_'.$inc_type);
    }

    /**
     * 模板管理首页
     */
    public function filemanager_index()
    {
        // 获取到所有GET参数
        $param = input('param.', '', null);
        $activepath = input('param.activepath', '', null);
        $activepath = $this->filemanagerLogic->replace_path($activepath, ':', true);

        /*当前目录路径*/
        $activepath = !empty($activepath) ? $activepath : $this->maxDir;
        $tmp_max_dir = preg_replace("#\/#i", "\/", $this->maxDir);
        if (!preg_match("#^".$tmp_max_dir."#i", $activepath)) {
            $activepath = $this->maxDir;
        }
        /*--end*/

        $inpath = "";
        $activepath = str_replace("..", "", $activepath);
        $activepath = preg_replace("#^\/{1,}#", "/", $activepath); // 多个斜杆替换为单个斜杆
        if($activepath == "/") $activepath = "";

        if(empty($activepath)) {
            $inpath = $this->baseDir.$this->maxDir;
        } else {
            $inpath = $this->baseDir.$activepath;
        }

        $list = $this->filemanagerLogic->getDirFile($inpath, $activepath);
        $assign_data['list'] = $list;

        /*文件操作*/
        $assign_data['replaceImgOpArr'] = $this->filemanagerLogic->replaceImgOpArr;
        $assign_data['editOpArr'] = $this->filemanagerLogic->editOpArr;
        $assign_data['renameOpArr'] = $this->filemanagerLogic->renameOpArr;
        $assign_data['delOpArr'] = $this->filemanagerLogic->delOpArr;
        $assign_data['moveOpArr'] = $this->filemanagerLogic->moveOpArr;
        /*--end*/

        $assign_data['activepath'] = $activepath;

        $this->assign($assign_data);
        return $this->fetch();
    }
    /**
     * 替换图片
     */
    public function filemanager_replace_img()
    {
        if (IS_POST) {
            $post = input('post.', '', null);
            $activepath = !empty($post['activepath']) ? trim($post['activepath']) : '';
            if (empty($activepath)) {
                $this->error('参数有误');
                exit;
            }

            $file = request()->file('upfile');
            if (empty($file)) {
                $this->error('请选择上传图片！');
                exit;
            } else {
                $image_type = tpCache('basic.image_type');
                $fileExt = !empty($image_type) ? str_replace('|', ',', $image_type) : config('global.image_ext');
                $image_upload_limit_size = intval(tpCache('basic.file_size') * 1024 * 1024);
                $result = $this->validate(
                    ['file' => $file],
                    ['file'=>'image|fileSize:'.$image_upload_limit_size.'|fileExt:'.$fileExt],
                    ['file.image' => '上传文件必须为图片','file.fileSize' => '上传文件过大','file.fileExt'=>'上传文件后缀名必须为'.$fileExt]
                );
                if (true !== $result || empty($file)) {
                    $this->error($result);
                    exit;
                }
            }

            $res = $this->filemanagerLogic->upload('upfile', $activepath, $post['filename'], 'image');
            if ($res['code'] == 1) {
                $this->success('操作成功！',weapp_url('Systemdoctor/Systemdoctor/filemanager_index', array('activepath'=>$this->filemanagerLogic->replace_path($activepath, ':', false))));
            } else {
                $this->error($res['msg'],weapp_url('Systemdoctor/Systemdoctor/filemanager_index', array('activepath'=>$this->filemanagerLogic->replace_path($activepath, ':', false))));
            }
        }

        $filename = input('param.filename/s', '', null);

        $activepath = input('param.activepath/s', '', null);
        $activepath = $this->filemanagerLogic->replace_path($activepath, ':', true);
        if ($activepath == "") $activepathname = "根目录";
        else $activepathname = $activepath;

        $info = array(
            'activepath'    => $activepath,
            'activepathname'    => $activepathname,
            'filename'  => $filename,
        );
        $this->assign('info', $info);
        return $this->fetch();
    }

    /**
     * 新建文件
     */
    public function filemanager_newfile()
    {
        if (IS_POST) {
            $post = input('post.', '', null);
            $content = input('post.content', '', null);
            $filename = !empty($post['filename']) ? trim($post['filename']) : '';
            $content = !empty($content) ? $content : '';
            $activepath = !empty($post['activepath']) ? trim($post['activepath']) : '';

            if (empty($filename) || empty($activepath)) {
                $this->error('参数有误');
                exit;
            }

            $r = $this->filemanagerLogic->editFile($filename, $activepath, $content);
            if ($r === true) {
                $this->success('操作成功！',weapp_url('Systemdoctor/Systemdoctor/filemanager_index', array('activepath'=>$this->filemanagerLogic->replace_path($activepath, ':', false))));
                exit;
            } else {
                $this->error($r);
                exit;
            }
        }

        $activepath = input('param.activepath/s', '', null);
        $activepath = $this->filemanagerLogic->replace_path($activepath, ':', true);
        $filename = 'newfile.htm';
        $content = "";
        $info = array(
            'filename'  => $filename,
            'activepath'=> $activepath,
            'content'   => $content,
            'extension' => 'text/html',
        );
        $this->assign('info', $info);
        return $this->fetch();
    }

    /**
     * 模板管理编辑
     */
    public function filemanager_edit()
    {
        if (IS_POST) {
            $post = input('post.', '', null);
            $content = input('post.content', '', null);
            $filename = !empty($post['filename']) ? trim($post['filename']) : '';
            $content = !empty($content) ? $content : '';
            $activepath = !empty($post['activepath']) ? trim($post['activepath']) : '';

            if (empty($filename) || empty($activepath)) {
                $this->error('参数有误');
                exit;
            }

            $r = $this->filemanagerLogic->editFile($filename, $activepath, $content);
            if ($r === true) {
                $this->success('操作成功！',weapp_url('Systemdoctor/Systemdoctor/filemanager_index', array('activepath'=>$this->filemanagerLogic->replace_path($activepath, ':', false))));
                exit;
            } else {
                $this->error($r);
                exit;
            }
        }

        $activepath = input('param.activepath/s', '', null);
        $activepath = $this->filemanagerLogic->replace_path($activepath, ':', true);

        $filename = input('param.filename/s', '', null);

        $activepath = str_replace("..", "", $activepath);
        $filename = str_replace("..", "", $filename);
        $path_parts  = pathinfo($filename);
        $path_parts['extension'] = strtolower($path_parts['extension']);

        /*不允许越过指定最大级目录的文件编辑*/
        $tmp_max_dir = preg_replace("#\/#i", "\/", $this->filemanagerLogic->maxDir);
        if (!preg_match("#^".$tmp_max_dir."#i", $activepath)) {
            $this->error('没有操作权限！');
            exit;
        }
        /*--end*/

        /*允许编辑的文件类型*/
        if (!in_array($path_parts['extension'], $this->filemanagerLogic->editExt)) {
            $this->error('只允许操作文件类型如下：'.implode('|', $this->filemanagerLogic->editExt));
            exit;
        }
        /*--end*/

        /*读取文件内容*/
        $file = $this->baseDir."$activepath/$filename";
        $content = "";
        if(is_file($file))
        {
            $filesize = filesize($file);
            if (0 < $filesize) {
                $fp = fopen($file, "r");
                $content = fread($fp, $filesize);
                fclose($fp);
                if ('css' != $path_parts['extension']) {
                    $content = htmlspecialchars($content, ENT_QUOTES);
                    $content = preg_replace("/(@)?eval(\s*)\(/i", 'intval(', $content);
                    // $content = preg_replace("/\?\bphp\b/i", "？ｍｕｍａ", $content);
                }
            }
        }
        /*--end*/

        if($path_parts['extension'] == 'js'){
            $extension = 'text/javascript';
        } else if($path_parts['extension'] == 'css'){
            $extension = 'text/css';
        } else {
            $extension = 'text/html';
        }

        $info = array(
            'filename'  => $filename,
            'activepath'=> $activepath,
            'extension' => $extension,
            'content'   => $content,
        );
        $this->assign('info', $info);
        return $this->fetch();
    }

    public function trojan_horse()
    {
        $value = input('post.value/d');
        
        tpCache('weapp', ['weapp_check_illegal_open' => $value]);

        $this->success('操作成功！');
    }
}