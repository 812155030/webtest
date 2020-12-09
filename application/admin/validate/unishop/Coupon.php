<?php

namespace app\admin\validate\unishop;

use think\Validate;

class Coupon extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'value'=>'lt:least' // 小于  value:优惠券金额   least:消费多少可用
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'value' => '优惠券金额 必须小于 消费金额'
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['value'],
        'edit' => ['value'],
    ];
}
