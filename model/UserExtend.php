<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/3/8
 * Time: 7:20 PM
 */


namespace addons\unishop\model;

use fast\Random;
use think\Db;
use think\Exception;
use think\Model;

/**
 * 扩展用户表
 * Class UserExtend
 * @package addons\unishop\model
 */
class UserExtend extends Model
{
    // 表名
    protected $name = 'unishop_user_extend';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 关联基础用户表
     */
    public function user()
    {
        return $this->hasOne('user', 'id', 'user_id')->field('id,avatar,mobile,username');
    }

    /**
     * 通过微信小程序openid获取用户id
     */
    public function getUserInfoByOpenid($openid)
    {
        $userExtend = $this
            ->with('user')
            ->where(['openid' => $openid])
            ->find();
        if ($userExtend) {
            $user = $userExtend->user;
        } else {
            Db::startTrans();
            try {
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
                    'username'  => __('Tourist')
                ];
                $user = User::create($params, true);

                self::create([
                    'user_id' => $user->id,
                    'openid' => $openid
                ], true);

                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                return false;
            }
        }
        $user = $user->toArray();
        return $user;
    }


}
