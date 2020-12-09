<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/5/4
 * Time: 6:20 PM
 */


namespace tests\unishop\controller;


use addons\unishop\controller\Sms;
use addons\unishop\controller\User;
use addons\unishop\extend\PhpunitFunctionCustomize;
use PHPUnit\Framework\TestCase;


class UserTest extends TestCase
{
    use PhpunitFunctionCustomize;

    /**
     * @test
     */
    public function register()
    {
        $contents = $this->userLogin();

        $this->assertNotEmpty($contents);
        $this->assertIsArray($contents);
        $this->assertSame(1, $contents['code']);
        $this->assertIsArray($contents['data']);
        $this->assertIsArray($contents['data']);
        $this->assertNotEmpty($contents['data']['id']);
        $this->assertNotEmpty($contents['data']['username']);
        $this->assertNotEmpty($contents['data']['mobile']);
        $this->assertArrayHasKey('avatar', $contents['data']);
        $this->assertNotEmpty($contents['data']['token']);
        $this->assertNotEmpty($contents['data']['user_id']);

        return $contents['data'];
    }

    /**
     * @test
     * @depends register
     */
    public function login(array $userinfo)
    {
        $contents = $this->request(User::class, 'login', [
            'mobile' => self::$mobile,
            'password' => self::$password
        ]);

        $this->assertIsArray($contents);
        $this->assertSame(1, $contents['code']);
        $this->assertArrayHasKey('data', $contents);
        $this->assertNotEmpty($contents['data']['id']);
        $this->assertNotEmpty($contents['data']['username']);
        $this->assertNotEmpty($contents['data']['mobile']);
        $this->assertArrayHasKey('avatar', $contents['data']);
        $this->assertNotEmpty($contents['data']['token']);
        $this->assertNotEmpty($contents['data']['user_id']);

        return $contents['data'];
    }

    /**
     * @test
     * @depends login
     */
    public function resetpwd(array $userinfo)
    {
        // 模拟发送短信
        \app\common\model\Sms::create(['event' => $this->eventReserpwd, 'mobile' => self::$mobile, 'code' => self::$smsCode, 'ip' => 'phpunit', 'createtime' => time()]);

        $contents = $this->request(User::class, 'resetpwd', [
            'captcha' => self::$smsCode,
            'mobile' => self::$mobile,
            'password' => self::$password
        ]);

        $this->assertSame(1, $contents['code']);
    }

    /**
     * @test
     * @depends login
     */
    public function edit(array $userinfo)
    {
        $contents = $this->request(User::class, 'edit',[
            'username' => self::$username . mt_rand(0,100000)
        ]);
        $this->assertSame(1, $contents['code']);
    }

    /**
     * @test
     * @depends login
     */
    public function status()
    {
        $contents = $this->request(User::class, 'status');

        $this->assertEquals(1, $contents['code']);
        $this->assertEquals(1, $contents['data']);
    }

    /**
     * @test
     */
    public function authSession()
    {
        $contents = $this->request(User::class, 'authSession');
        // 这里只测试接口是否通
        $this->assertEquals(1, $contents['code']);
    }

}
