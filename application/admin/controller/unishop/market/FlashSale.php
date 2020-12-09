<?php

namespace app\admin\controller\unishop\market;

use addons\unishop\extend\Redis;
use app\admin\model\unishop\FlashProduct;
use app\admin\model\unishop\OrderExtend;
use app\admin\model\unishop\OrderProduct;
use app\common\controller\Backend;
use think\Db;
use think\Exception;
use think\exception\PDOException;
use think\exception\ValidateException;
use think\Hook;

/**
 * 秒杀管理
 *
 * @icon fa fa-circle-o
 */
class FlashSale extends Backend
{
    /**
     * Multi方法可批量修改的字段
     */
    protected $multiFields = 'switch';

    /**
     * 是否开启Validate验证
     */
    protected $modelValidate = true;

    /**
     * 是否开启模型场景验证
     */
    protected $modelSceneValidate = true;

    /**
     * FlashSale模型对象
     * @var \app\admin\model\unishop\FlashSale
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\unishop\FlashSale;
        $this->view->assign("statusList", $this->model->getStatusList());
    }


    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        $this->request->filter(['strip_tags']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                ->where($where)
                ->count();

            $list = $this->model
                ->with([
                    'product'
                ])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            $list = collection($list)->toArray();
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);

                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }

                //Db::startTrans();
                $this->model->startTrans();
                try {

                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : $name) : $this->modelValidate;
                        $this->model->validateFailException(true)->validate($validate);
                    }
                    $params['starttime'] = strtotime($params['starttime']);
                    $params['endtime'] = strtotime($params['endtime']);
                    $this->model->allowField(true)->save($params);

                    $products = [];
                    if (isset($params['product'])) {
                        $time = time();
                        $redis = new Redis();
                        foreach ($params['product'] as $item) {
                            array_push($products, [
                                'flash_id' => $this->model->id,
                                'product_id' => $item['id'],
                                'number' => $item['number'],
                                'introduction' => $item['introduction'],
                                'createtime' => $time,
                                'updatetime' => $time,
                            ]);

                            // 是否上架
                            if ($params['switch'] == \app\admin\model\unishop\FlashSale::SWITCH_YES) {
                                $redis->handler->hMSet('flash_sale_' . $this->model->id . '_' . $item['id'], [
                                    'flash_id' => $this->model->id,
                                    'product_id' => $item['id'],
                                    'id' => 0, // 新增的时候没有flash_product_id. 这个值无关紧要
                                    'number' => $item['number'],
                                    'sold' => 0, // 出售0个
                                    'switch' => 1, // 默认全部上架
                                    'starttime' => $params['starttime'],
                                    'endtime' => $params['endtime'],
                                ]);
                            }

                        }
                    }
                    if (empty($products)) {
                        throw new ValidateException(__('Add at least one product'));
                    }

                    $flashProduct = new FlashProduct();
                    $flashProduct->insertAll($products);

                    //Db::commit();
                    $this->model->commit();
                    $this->success();
                } catch (ValidateException $e) {
                    //Db::rollback();
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    //Db::rollback();
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    //Db::rollback();
                    $this->model->rollback();
                    $this->error($e->getMessage());
                }

                $this->error(__('No rows were inserted'));

            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        return $this->view->fetch();
    }

    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {

            $params = $this->request->post("row/a");

            if ($row['status'] == \app\admin\model\unishop\FlashSale::STATUS_YES) {
                $this->error(__('Activity filed，can not change'));
            }


            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                //Db::startTrans();
                $this->model->startTrans();
                try {
                    if (isset($params['switch']) && $params['switch'] == \app\admin\model\unishop\FlashSale::SWITCH_YES) {
                        Redis::available();
                    }

                    $row->checkItCanEdit();
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);

                    $products = [];
                    if (isset($params['product'])) {
                        $time = time();
                        foreach ($params['product'] as $item) {
                            array_push($products, [
                                'flash_id' => $row['id'],
                                'product_id' => $item['id'],
                                'number' => $item['number'],
                                'introduction' => $item['introduction'],
                                'createtime' => $time,
                                'updatetime' => $time,
                            ]);
                        }
                    }
                    if (empty($products)) {
                        throw new ValidateException(__('Add at least one product'));
                    }
                    $flashProduct = new FlashProduct();
                    $flashProduct->where(['flash_id' => $row['id']])->delete();
                    $flashProduct->insertAll($products);

                    //Db::commit();
                    $this->model->commit();
                } catch (ValidateException $e) {
                    //Db::rollback();
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    //Db::rollback();
                    $this->model->rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    //Db::rollback();
                    $this->model->rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 真实删除
     */
    public function destroy($ids = "")
    {
        $pk = $this->model->getPk();
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            $this->model->where($this->dataLimitField, 'in', $adminIds);
        }
        if ($ids) {
            $this->model->where($pk, 'in', $ids);
        }
        $count = 0;
        Db::startTrans();
        try {
            $list = $this->model->onlyTrashed()->select();
            foreach ($list as $k => $v) {
                FlashProduct::destroy(['flash_id' => $v->id]);
                $count += $v->delete(true);
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
            $this->error(__('No rows were deleted'));
        }
        $this->error(__('Parameter %s can not be empty', 'ids'));
    }

    /**
     * 归档结束
     */
    public function done($ids = null)
    {
        Db::startTrans();
        try {

            $row = $this->model->get($ids, [
                'product' => function ($query) { // flashProduct
                    // 悲观锁
                    $query->lock(true)->with(['product']); //product
                }
            ]);
            if (!$row) {
                throw new Exception(__('No Results were found'));
            }
            if ($row['status'] == \app\admin\model\unishop\FlashSale::STATUS_YES) {
                throw new Exception(__('Activity filed，can not change'));
            }
            $orderExtent = new OrderProduct();
            $orders = $orderExtent->where(['flash_id' => $ids])
                ->field('number,spec,product_id')->select();

            if (!$orders) {
                throw new Exception(__('No one buys'));
            }

            $productList = [];
            foreach ($orders as $key => $order) {
                if ($order['spec'] == '' || !$order['spec'] || empty($order['spec']) || is_null($order['spec'])) {
                    // 没有规格的商品
                    if (isset($productList[$order['product_id']])) {
                        $productList[$order['product_id']] += $order['number'];
                    } else {
                        $productList[$order['product_id']] = $order['number'];
                    }
                } else {
                    // 有规格的商品
                    if (!isset($productList[$order['product_id']])) {
                        $productList[$order['product_id']] = [];
                    }

                    if (!isset($productList[$order['product_id']][$order['spec']])) {
                        $productList[$order['product_id']][$order['spec']] = $order['number'];
                    } else {
                        $productList[$order['product_id']][$order['spec']] += $order['number'];
                    }

                }
            }

            $products = $specNumber = [];
            foreach ($productList as $product_id => $value) {
                foreach ($row['product'] as $flashProduct) {
                    if ($flashProduct['product_id'] == $product_id) {

                        if (is_array($value)) {
                            // 有规格 (这里循环的是同一个商品不同的规格)
                            foreach ($value as $spec => $number) {
                                $products[] = $flashProduct['product']->getData();
                                $specNumber[$spec] = $number;
                            }
                        } else {
                            // 无规格
                            $products[] = $flashProduct['product']->getData();
                            $specNumber[] = $value;
                        }
                    }
                }
            }

            // 让秒杀下架
            $row->status = \app\admin\model\unishop\FlashSale::STATUS_YES;
            $row->switch = \app\admin\model\unishop\FlashSale::SWITCH_NO;
            $row->save();

            $this->model->activityFiled($products, $specNumber);

            // 删除redis数据
            $redis = new Redis();
            foreach ($products as $product) {
                $redis->handler->del('flash_sale_' . $ids . '_' . $product['id']);
            }

            Db::commit();
            $this->success(__('Success'));
        } catch (Exception $e) {
            Db::rollback();
            $this->error($e->getMessage());
        }
    }

    /**
     * 秒杀的产品列表
     */
    public function product()
    {
        $flashId = $this->request->request('flash_id');

        $flashProductModel = new FlashProduct();

        $products = $flashProductModel
            ->with([
                'product' => function ($query) {
                    $query->with('category')->field(['id', 'title', 'category_id', 'image', 'stock']);
                }
            ])
            ->where(['flash_id' => $flashId])
            ->select();
        $list = [];
        foreach ($products as $key => $item) {
            $list[$key]['flash_product_id'] = $item['id'];
            $list[$key]['id'] = $item['product_id'];
            $list[$key]['product_id'] = $item['product_id'];
            $list[$key]['title'] = $item['product']['title'];
            $list[$key]['image'] = $item['product']['image'];
            $list[$key]['stock'] = $item['product']['stock'];
            $list[$key]['sold'] = $item['sold'];
            $list[$key]['switch'] = $item['switch'];
            $list[$key]['category'] = $item['product']['category'];
            $list[$key]['number'] = $item['number'];
            $list[$key]['introduction'] = $item['introduction'];
        }

        $result = array("total" => count($list), "rows" => $list);

        return json($result);
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
                            if ($item['status'] == \app\admin\model\unishop\FlashSale::STATUS_YES) {
                                throw new Exception(__('Activity filed，can not change'));
                            }
                            foreach ($item['product'] as $product) {
                                // 上架
                                if ($values['switch'] == \app\admin\model\unishop\FlashSale::SWITCH_YES) {
                                    $redis->handler->hMSet('flash_sale_' . $product['flash_id'] . '_' . $product['product_id'], [
                                        'flash_id' => $product['flash_id'],
                                        'product_id' => $product['product_id'],
                                        'id' => $product['id'],
                                        'number' => $product['number'],
                                        'sold' => $product['sold'],
                                        'switch' => $product['switch'],
                                        'starttime' => $item['starttime'],
                                        'endtime' => $item['endtime'],
                                    ]);
                                } else {
                                    // 下架
                                    $redis->handler->del('flash_sale_' . $product['flash_id'] . '_' . $product['product_id']);
                                }
                            }
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
