<?php

namespace app\admin\model\unishop;

use think\Model;


class Ads extends Model
{



    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'unishop_ads';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });

    }


    public function product(){
        return $this->belongsTo('product')->field('id,title');
    }





}
