<?php
/**
 * Created by PhpStorm.
 * User: zhengmingwei
 * Date: 2020/3/1
 * Time: 7:43 PM
 */


namespace app\admin\controller\unishop\market;


use addons\unishop\extend\Redis;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;

class FlashProduct extends Backend
{
    /**
     * Multi方法可批量修改的字段
     */
    protected $multiFields = 'switch';

    /**
     * FlashSale模型对象
     * @var \app\admin\model\unishop\FlashSale
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\unishop\FlashProduct;
    }


    /**
     * 批量更新
     */
    public function multi($ids = "")
    {

        $ids = $ids ? $ids : $this->request->param("ids");
        if ($ids) {
            if ($this->request->has('params')) {
                parse_str($this->request->post("params"), $values);
                $values = array_intersect_key($values, array_flip(is_array($this->multiFields) ? $this->multiFields : explode(',', $this->multiFields)));
                if ($values || $this->auth->isSuperAdmin()) {
                    $adminIds = $this->getDataLimitAdminIds();
                    if (is_array($adminIds)) {
                        $this->model->where($this->dataLimitField, 'in', $adminIds);
                    }
                    $count = 0;
                    Db::startTrans();
                    try {
                        Redis::available();

                        $list = $this->model->with(['product'])->where($this->model->getPk(), 'in', $ids)->select();

                        $redis = new Redis();
                        foreach ($list as $index => $item) {
                            $redis->handler->hSet('flash_sale_' . $item['flash_id'] . '_' . $item['product_id'], 'switch', $values['switch']);
                            $count += $item->allowField(true)->isUpdate(true)->save($values);
                        }
                        Db::commit();
                    } catch (PDOException $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    } catch (Exception $e) {
                        Db::rollback();
                        $this->error($e->getMessage());
                    }
                    if ($count) {
                        $this->success();
                    } else {
                        $this->error(__('No rows were updated'));
                    }
                } else {
                    $this->error(__('You have no permission'));
                }
            }
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }
}
