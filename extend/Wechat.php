<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/3/7
 * Time: 4:09 PM
 */


namespace addons\unishop\extend;

use addons\unishop\model\Config;
use addons\unishop\model\UserExtend;
use EasyWeChat\Factory;
use think\Cache;
use think\Session;

class Wechat
{
    public static function initEasyWechat($type = 'miniProgram')
    {
        $config = [
            // 必要配置
            'app_id' => Config::getByName('app_id')['value'],
            'secret' => Config::getByName('secret')['value'],

            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            //'response_type' => 'array',
//            'log' => [
//                'level' => 'debug',
//                'file' => __DIR__.'/wechat.log',
//            ],
        ];

        switch ($type) {
            case 'miniProgram':
                return Factory::miniProgram($config);
                break;
            case 'payment':
                $config['mch_id'] = Config::getByName('mch_id')['value'];
                $config['key'] = Config::getByName('key')['value'];
                // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
                $config['cert_path'] = Config::getByName('cert_path')['value']; // XXX: 绝对路径！！！！
                $config['key_path'] = Config::getByName('key_path')['value'];      // XXX: 绝对路径！！！！
                $config['notify_url'] = Config::getByName('notify_url')['value'];;     // 你也可以在下单时单独设置来想覆盖它
                return Factory::payment($config);
                break;
        }
    }

    /**
     * 小程序登录
     */
    public static function authSession($code)
    {
        $app = self::initEasyWechat('miniProgram');

        $result = $app->auth->session($code);

        if (isset($result['session_key']) && isset($result['openid'])) {
            //储存session_key 用来解密微信用户授权数据
            Session::set('session_key', $result['session_key']);
            Session::set('openid', $result['openid']);
            $result['userInfo'] = (new UserExtend())->getUserInfoByOpenid($result['openid']);
            $result['userInfo']['openid'] = $result['openid'];
            unset($result['session_key']);
        }

        return $result;
    }

    /**
     * 根据user_id获取用户Openid
     * @param $userId
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getOpenidByUserId($userId)
    {
        $openid = Cache::get('openid_' . $userId);
        if (empty($openid)) {
            $userExtend = (new UserExtend())->where(['user_id' => $userId])->field('openid')->find();
            if (empty($userExtend['openid'])) {
                return false;
            }
            $openid = $userExtend['openid'];
            Cache::set('openid_' . $userId, $openid, 7200);
        }
        return $openid;
    }

    /**
     * 小程序调起支付数据签名
     * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=7_7&index=5
     * @param array $params
     * @param string $key
     * @return string
     */
    public static function paySign($params, $key)
    {
        ksort($params);
        $string = "";
        foreach ($params as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $string .= $k . "=" . $v . "&";
            }
        }
        $string = $string . "key=" . $key;
        //$String= "appId=xxxxx&nonceStr=xxxxx&package=prepay_id="xxxxx&signType=MD5&timeStamp=xxxxx&key=xxxxx"
        return strtoupper(md5($string));
    }

    /**
     * 判断H5页面是否在微信内
     */
    public static function h5InWechat()
    {
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false ) {
            return true;
        }
        return false;
    }
}
