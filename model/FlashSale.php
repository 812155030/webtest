<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/2/9
 * Time: 6:29 PM
 */


namespace addons\unishop\model;


use addons\unishop\extend\Hashids;
use think\Model;
use traits\model\SoftDelete;

class FlashSale extends Model
{
    use SoftDelete;

    // 表名
    protected $name = 'unishop_flash_sale';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 已归档
    const STATUS_YES = 1; // 是
    const STATUS_NO = 0; // 否

    // 已上架
    const SWITCH_YES = 1; // 是
    const SWITCH_NO = 0; // 否

    // 隐藏属性
    protected $hidden = [
        'id'
    ];

    // 追加属性
    protected $append = [
        'flash_id',
        'starttime_hour',
        'state',
        'current',
    ];

    /**
     * Encode flash_id
     */
    public function getFlashIdAttr($value, $data) {
        return Hashids::encodeHex($data['id']);
    }

    /**
     * Format time 'H:i'
     */
    public function getStarttimeHourAttr($value, $data) {
        return date('H:i', $data['starttime']);
    }

    /**
     * Now in progress
     */
    public function getCurrentAttr($value, $data) {
        if (date('Y-m-d H:00:00', time()) == date('Y-m-d H:00:00', $data['starttime'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * State
     */
    public function getStateAttr($value, $data) {
        $time = time();
        if (date('Y-m-d H:00:00', $time) == date('Y-m-d H:00:00', $data['starttime'])) {
            return 2; // '抢购进行中';
        } else if ($data['starttime'] < $time) {
            return 1; // '已开抢';
        } else {
            return 0; // '未开始';
        }
    }

    /**
     * 关联秒杀产品表
     * @return \think\model\relation\HasMany
     */
    public function product()
    {
        return $this->hasMany('flashProduct', 'flash_id', 'id');
    }

    /**
     * 获取离开始时间的倒计时
     * @param $targetTime
     * @return bool
     */
    public static function countdown($targetTime)
    {
        $time = $targetTime - time();
        if ($time > 0) {
            // 如果time等于0，那么时间是从1970-01-01 08:00:00开始的
            $countdown['day'] = intval(date('d', $time) - 1);
            $countdown['hour'] = intval(date('H', $time) - 8);
            $countdown['minute'] = intval(date('i', $time));
            $countdown['second'] = intval(date('s', $time));
            foreach ($countdown as &$item) {
                if ($item < 0) $item = 0;
            }
            return $countdown;
        }
        return false;
    }
}
