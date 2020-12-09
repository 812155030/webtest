<?php

namespace app\admin\validate\unishop;

use think\Validate;

class FlashSale extends Validate
{
    /**
     * 验证规则
     */
    protected $rule = [
        'starttime|开始时间' => 'require|unique:unishop_flash_sale|<:endtime',
        'endtime|结束时间' => 'require',
        'title|场景名' => 'require',
    ];

    /**
     * 提示消息
     */
    protected $message = [
        'starttime.require' => '开始时间不能为空',
        'starttime.unique' => '开始时间已存在，不能重复',
        'starttime.lt' => '结束时间必须大于开始时间',
        'endtime.require' => '结束时间不能为空',
        'title' => '场景名称不能为空',
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['starttime', 'endtime', 'title'],
        'edit' => ['starttime', 'endtime', 'title'],
    ];

}
