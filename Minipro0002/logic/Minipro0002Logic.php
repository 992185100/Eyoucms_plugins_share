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
 * Date: 2018-4-3`
 */

namespace weapp\Minipro0002\logic;

use weapp\Minipro0002\model\Minipro0002Model;

load_trait('controller/Jump'); // 引入traits\controller\Jump

/**
 * 业务逻辑
 */
class Minipro0002Logic
{
    use \traits\controller\Jump;

    /**
     * 实例化模型
     */
    private $model;

    /**
     * 析构函数
     */
    function __construct() 
    {
        $this->model = new Minipro0002Model;
    }

    /**
     * 接口转化 
     */
    public function get_api_url($query_str)
    {
        $apiUrl = 'aHR0cHM6Ly9zZXJ2aWNlLmV5eXN6LmNu';
        return base64_decode($apiUrl).$query_str;
    }

    /**
     * 获取最新的小程序参数配置
     */
    public function getSetting()
    {
        $data = $this->model->getValue($this->model->miniproType);
        if (!empty($data)) {
            $url = "/index.php?m=api&c=MiniproClient&a=minipro&appId=".$data['appId']."&appSecret=".$data['appSecret'];
            $response = httpRequest($this->get_api_url($url));
            $params = array();
            $params = json_decode($response, true);
            if (!empty($params) && $params['errcode'] == 0) {
                $bool = $this->model->setValue($this->model->miniproType, $params['errmsg']);
                if ($bool) {
                    $data = $this->model->getValue($this->model->miniproType);
                } else {
                    $data = $params['errmsg'];
                }
            }
        }

        return $data;
    }
}
