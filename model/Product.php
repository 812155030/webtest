<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2019/11/10
 * Time: 11:45 上午
 */


namespace addons\unishop\model;


use addons\unishop\extend\Hashids;
use think\Exception;
use think\Model;
use traits\model\SoftDelete;

/**
 * 商品模型
 * Class Product
 * @package addons\unishop\model
 */
class Product extends Model
{
    use SoftDelete;

    // 表名
    protected $name = 'unishop_product';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 是否上架？
    const SWITCH_ON = 1; //是
    const SWITCH_OFF = 0; //否

    // 是否开启规格？
    const SPEC_ON = 1; //是
    const SPEC_OFF = 0; //否

    // 追加属性
    protected $append = [
        //'images_text',
        //'spec_list',
        //'spec_table_list',
        'product_id'
    ];

    // 隐藏属性
    protected $hidden = [
        'id',
        'real_look',
        'real_sales',
        'images',
        'specList',
        'specTableList',
    ];

    /**
     * 处理图片
     * @param $value
     * @return string
     */
    public function getImageAttr($value) {
        return Config::getImagesFullUrl($value);
    }

    /**
     * 加密商品id
     * @param $value
     * @param $data
     * @return string
     */
    public function getProductIdAttr($value, $data) {
        return Hashids::encodeHex($data['id']);
    }

    /**
     * 获取销售量
     * @param $value
     * @param $data
     */
    public function getSalesAttr($value, $data) {
        return $data['sales'] + $data['real_sales'];
    }

    /**
     * 处理图片
     * @param $value
     * @param $data
     * @return string
     */
    public function getImagesTextAttr($value, $data){
        $images = explode(',', $data['images']);
        foreach ($images as &$image) {
            $image = Config::getImagesFullUrl($image);
        }
        return $images;
    }

    /**
     * 处理规格属性
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getSpecListAttr($value, $data) {
        return !empty($data['specList']) ? json_decode($data['specList'], true) : [];
    }

    /**
     * 处理规格值
     * @param $value
     * @param $data
     * @return mixed
     */
    public function getSpecTableListAttr($value, $data) {
        $specs = !empty($data['specTableList']) ? json_decode($data['specTableList'], true) : [];
        foreach ($specs as &$spec) {
            $spec['image'] = Config::getImagesFullUrl($spec['image']);
        }
        return $specs;
    }

    /**
     * 获取创建订单需要的产品信息
     * @param string $spec
     * @param int $number
     * @return array
     * @throws Exception
     */
    public function getDataOnCreateOrder(string $spec = '', $number = 1)
    {
        $data = (new \addons\unishop\extend\Product)->getBaseData($this->getData(), $spec);
        if ($data['stock'] < 1) {
            throw new Exception('产品库存不足');
        }
        $product = $this->getData();
        $data['title'] = $product['title'];
        $data['spec'] = $spec;
        $data['number'] = $number;
        $data['id'] = Hashids::encodeHex($product['id']);

        return $data;
    }


}
