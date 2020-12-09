<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/10/25
 * Time: 11:09 下午
 */


namespace addons\unishop\controller;

use addons\unishop\extend\Wechat;
use addons\unishop\model\UserExtend;
use app\common\library\Sms;
use think\Cache;
use think\Session;
use think\Validate;

class User extends Base
{
    protected $noNeedLogin = ['login', 'status', 'authSession', 'decryptData', 'register', 'resetpwd', 'loginForWechatMini'];

    /**
     * 会员登录
     *
     * @param string $account 账号
     * @param string $password 密码
     */
    public function login()
    {
        $mobile = $this->request->post('mobile');
        $password = $this->request->post('password');
        if (!$mobile || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($mobile, $password);
        if ($ret) {
            $data = $this->auth->getUserinfo();
            $data['avatar'] = \addons\unishop\model\Config::getImagesFullUrl($data['avatar']);
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 重置密码
     *
     * @param string $mobile 手机号
     * @param string $newpassword 新密码
     * @param string $captcha 验证码
     */
    public function resetpwd()
    {
        $mobile = $this->request->post("mobile");

        $newpassword = $this->request->post("password");
        $captcha = $this->request->post("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }

        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if (!$user) {
            $this->error(__('User not found'));
        }
        $ret = Sms::check($mobile, $captcha, 'resetpwd');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        Sms::flush($mobile, 'resetpwd');

        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'), 1);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email 邮箱
     * @param string $mobile 手机号
     */
    public function register()
    {
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $mobile = $this->request->post('mobile');
        $captcha = $this->request->post("captcha");

        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $captcha, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        Sms::flush($mobile, 'register');

        $avatar = \addons\unishop\model\Config::getByName('avatar')['value'] ?? '';
        $ret = $this->auth->register($username, $password, '', $mobile, ['avatar' => $avatar]);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 更改用户信息
     */
    public function edit()
    {
        $userInfo = $this->auth->getUserinfo();
        $username = $this->request->post('username', $userInfo['username']);
        $mobile = $this->request->post('mobile', $userInfo['mobile']);
        $avatar = $this->request->post('avatar', $userInfo['avatar']);

        $user = \app\common\model\User::get($this->auth->id);
        $user->username = $username;
        $user->mobile = $mobile;
        $user->avatar = $avatar;
        if ($user->save()) {
            $this->success(__('Modified'), 1);
        } else {
            $this->error(__('Fail'), 0);
        }
    }

    /**
     * 登录状态
     */
    public function status()
    {
        $this->success('', $this->auth->isLogin());
    }

    /**
     * 微信小程序登录
     */
    public function authSession()
    {
        $platform = $this->request->header('platform');
        switch ($platform) {
            case 'MP-WEIXIN':
                $code = $this->request->get('code');
                $data = Wechat::authSession($code);

                // 如果有手机号码，自动登录
                if (isset($data['userInfo']['mobile']) && (!empty($data['userInfo']['mobile']) || $data['userInfo']['mobile'] != '')) {
                    $this->auth->direct($data['userInfo']['id']);
                    if ($this->auth->isLogin()) {
                        $data['userInfo']['token'] = $this->auth->getToken();
                        // 支付的时候用
                        Cache::set('openid_' . $data['userInfo']['id'], $data['openid'], 7200);
                    }
                }

                break;
            default:
                $data = [];
        }
        $this->success('', $data);
    }


    /**
     * 微信小程序消息解密
     */
    public function decryptData()
    {
        $iv = $this->request->post('iv');

        $encryptedData = $this->request->post('encryptedData');

        $app = Wechat::initEasyWechat('miniProgram');

        $decryptedData = $app->encryptor->decryptData(Session::get('session_key'), $iv, $encryptedData);

        $this->success('', $decryptedData);
    }

    /**
     * 微信小程序通过授权手机号登录
     */
    public function loginForWechatMini()
    {
        $iv = $this->request->post('iv');

        $encryptedData = $this->request->post('encryptedData');

        $app = Wechat::initEasyWechat('miniProgram');

        $decryptedData = $app->encryptor->decryptData(Session::get('session_key'), $iv, $encryptedData);

        if (isset($decryptedData['phoneNumber'])) {
            $openid = Session::get('openid');

            // 看看有没有这个mobile的用户
            $user = \addons\unishop\model\User::getByMobile($decryptedData['phoneNumber']);
            if ($user) {
                // 有 处理：1，把；user_extend对应的user删除；2，把user_extend表的user_id字段换成已存在的用户id
                $userExtend = UserExtend::getByOpenid($openid);
                if ($userExtend) {
                    if ($userExtend['user_id'] != $user->id) {
                        \addons\unishop\model\User::destroy($userExtend['user_id']);
                        $userExtend->user_id = $user->id;
                        $userExtend->save();
                    }
                } else {
                    UserExtend::create(['user_id' => $user->id, 'openid' => $openid]);
                }
            } else {
                // 没有
                $userExtend = UserExtend::getByOpenid($openid);
                if ($userExtend) {
                    $user = \addons\unishop\model\User::get($userExtend->user_id);
                    $user->mobile = $decryptedData['phoneNumber'];
                    $user->save();
                } else {
                    $params = [
                        'level'    => 1,
                        'score'    => 0,
                        'jointime'  => time(),
                        'joinip'    => $_SERVER['REMOTE_ADDR'],
                        'logintime' => time(),
                        'loginip'   => $_SERVER['REMOTE_ADDR'],
                        'prevtime'  => time(),
                        'status'    => 'normal',
                        'avatar'    => '',
                        'username'  => __('Tourist'),
                        'mobile'    => $decryptedData['phoneNumber']
                    ];
                    $user = \addons\unishop\model\User::create($params, true);
                    UserExtend::create(['user_id' => $user->id, 'openid' => $openid]);
                }
            }

            $userInfo['id'] = $user->id;
            $userInfo['openid'] = $openid;
            $userInfo['mobile'] = $user->mobile;
            $userInfo['avatar'] = \addons\unishop\model\Config::getImagesFullUrl($user->avatar);
            $userInfo['username'] = $user->username;

            $this->auth->direct($userInfo['id']);
            if ($this->auth->isLogin()) {
                $userInfo['token'] = $this->auth->getToken();
                // 支付的时候用
                Cache::set('openid_' . $userInfo['id'], $openid, 7200);
            }

            $this->success('', $userInfo);

        } else {
            $this->error(__('Logged in failed'));
        }

    }


}
