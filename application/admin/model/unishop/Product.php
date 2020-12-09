<?php

namespace app\admin\model\unishop;

use think\Model;
use traits\model\SoftDelete;

/**
 * 商品模型
 * Class Product
 * @package app\admin\model\unishop
 */
class Product extends Model
{

    use SoftDelete;

    //数据库
    protected $connection = 'database';
    // 表名
    protected $name = 'unishop_product';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    protected static function init()
    {
        self::afterInsert(function ($row) {
            $pk = $row->getPk();
            $row->getQuery()->where($pk, $row[$pk])->update(['weigh' => $row[$pk]]);
        });
    }

    /**
     * 关联分类
     * @return \think\model\relation\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo('category');
    }

    /**
     * 关联运费
     * @return \think\model\relation\BelongsTo
     */
    public function delivery()
    {
        return $this->belongsTo('delivery');
    }


}
