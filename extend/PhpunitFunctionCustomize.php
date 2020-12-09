<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/5/3
 * Time: 9:03 PM
 */


namespace addons\unishop\extend;


use addons\unishop\controller\User;
use think\Config;
use think\exception\HttpResponseException;
use think\Request;
use think\Response;

trait PhpunitFunctionCustomize
{
    /**
     * 测试账户
     * @var string
     */
    static $username = 'unishop';
    static $mobile = '11111511115';
    static $password = '123456';

    /**
     * 短信动作
     * @var string
     */
    static $smsCode = '1111';
    protected $eventRegister = 'register'; // 注册
    protected $eventReserpwd = 'resetpwd'; // 重置密码

    /**
     * 访问本地接口
     * @param $class
     * @param string $action
     * @return array
     */
    public function request($class, $action = 'index', $params = [], $method = 'POST')
    {
        $data = [];
        if (!empty($class)) {
            $header = $get = $post = [];

            if (!empty($params['header'])) {
                $header = $params['header'];
                unset($params['header']);
            }

            $method = strtolower($method);
            $params['millisecond'] = 0;
            switch ($method) {
                case 'get':
                    $get = $params;
                    break;
                case 'post':
                    $post = $params;
                    break;
            }

            try {
                $data = [];
                Request::destroy();
                $controller = strtolower(substr($class, strrpos($class, '\\') + 1));
                $request = Request::instance([
                    'route' => [
                        'addon' => 'unishop',
                        'controller' => $controller,
                        'action' => $action
                    ],
                    'controller' => $controller,
                    'action' => $action,
                    'get' => $get,
                    'post' => $post,
                    'request' => $params,
                    'header' => $header
                ]);
                $obj = new $class($request);
                $obj->$action();
            } catch (HttpResponseException $e) {
                $data = $e->getResponse();
            }
        }

        // 输出数据到客户端
        if ($data instanceof Response) {
            $response = $data;
        } elseif (!is_null($data)) {
            // 默认自动识别响应输出类型
            $type = $request->isAjax() ?
                Config::get('default_ajax_return') :
                Config::get('default_return_type');

            $response = Response::create($data, $type);
        } else {
            $response = Response::create();
        }

        return $response->getData();
    }

    /**
     * 模拟用户登录
     */
    public function userLogin()
    {
        $contents = $this->request(User::class, 'login',[
            'mobile' => self::$mobile,
            'password' => self::$password
        ]);

        if ($contents['code'] == 0) {
            // 模拟发送短信
            \app\common\model\Sms::create(['event' => $this->eventRegister, 'mobile' => self::$mobile, 'code' => self::$smsCode, 'ip' => 'phpunit', 'createtime' => time()]);

            $this->request(User::class, 'register', [
                'captcha' => self::$smsCode,
                'mobile' => self::$mobile,
                'password' => self::$password,
                'username' => self::$username
            ]);
            $contents = $this->request(User::class, 'login',[
                'mobile' => self::$mobile,
                'password' => self::$password
            ]);
        }
        return $contents;
    }


}
