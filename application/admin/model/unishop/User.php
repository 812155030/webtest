<?php

namespace app\admin\model\unishop;

use app\common\model\MoneyLog;
use think\Model;

class User extends Model
{

    // 表名
    protected $name = 'user';
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    /**
     * 修改用户昵称
     */
    public function getUsernameAttr($value, $data)
    {
        return !empty($data['username']) ? $data['username'] : __('Visitor');
    }

}
