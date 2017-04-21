<?php

/**
 * @desc Wish订单拉取
 * @author Gordon
 * @since 2015-06-22
 */
class WishListing extends WishModel
{


    /** @var object 拉单返回信息 */
    public $orderResponse = null;

    /** @var int 账号ID */
    public $_accountID = null;

    /** @var string 异常信息 */
    public $exception = null;

    /** @var int 日志编号 */
    public $_logID = 0;

    const EVENT_DISABLED_VARIANTS = 'disabled_variants';
    const EVENT_NAME = 'get_product';

    /**
     * @desc wish 产品审核状态
     * @var unknown
     */
    const REVIEW_STATUS_APPROVED = 'approved';//通过
    const REVIEW_STATUS_REJECTED = 'rejected';//不通过
    const REVIEW_STATUS_PENDING = 'pending';//等待

    const PROMOTED_STATUS_NO = 0;//不是促销
    const PROMOTED_STATUS_YES = 1;//促销

    public $detail;
    public $sale_property;
    public $status_text;
    public $sku;
    public $review_status_text;
    public $promoted_text;
    public $variants_id;
    public $oprator;
    public $parent_sku;
    public $name;
    public $main_image;
    public $account_name;
    public $num_sold_total;
    public $num_saves_total;
    public $subsku;
    public $description = '';
    public $product_link;
    public $main_image_url;
    public $seller_name;
    public $send_warehouse;
    public static $wishAccountPairs;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }


    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return 'ueb_wish_listing';
    }

    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID)
    {
        $this->_accountID = $accountID;
    }

    /**
     * @desc 拉取账号LISTING
     * @param array $params
     * @return boolean
     */
    // public function getAccountListings($params = array()) {
    // 	if (isset($params['index']) && !empty($params['index']))
    // 		$index = (int)$params['index'];
    // 	else
    // 		$index = 0;
    // 	if (isset($params['limit']) && !empty($params['limit']))
    // 		$limit = (int)$params['limit'];
    // 	else
    // 		$limit = 50;
    // 	$hasflish = false;

    // 	while (!$hasflish) {
    // 		$request = new ListAllProductsRequest();
    // 		$request->setAccount($this->_accountID);
    // 		$request->setStartIndex($index);
    // 		$request->setLimit($limit);
    // 		$response = $request->setRequest()->sendRequest()->getResponse();
    // 		if ($request->getIfSuccess() && !empty($response)) {
    // 			$datas = $response->data;
    // 			$flag = $this->saveWishListing($datas);
    // 			if (!isset($response->paging->next) || empty($response->paging->next)){
    // 				$hasflish = true;
    // 			}
    // 			if (!$flag)
    // 				return false;
    // 		} else {
    // 			$hasflish = true;
    // 		}
    // 		$index++;
    // 	}
    // 	return true;
    // }

    /**
     * 获取wish更新时间
     * @param  [type] $dateUploaded [description]
     * @return [type]               [description]
     */
    public static function getWishDateUploaded($dateUploaded)
    {
        if (preg_match('/\d{1,2}-\d{1,2}-\d{4}/', $dateUploaded)) {
            $tmp = explode('-', $dateUploaded);
            return $tmp[2] . '-' . $tmp[0] . '-' . $tmp[1];
        }
        return '0000-00-00';
    }

    /**
     * @desc 保存listing数据
     * @param unknown $datas
     */
    public function saveWishListing($datas)
    {
        $errMsgs = '';
        $encryptSku = new encryptSku();
        foreach ($datas as $data) {
            //MHelper::writefilelog('wish/wishlisting/'.date("Ymd").'/'.$this->_accountID.'/saveWishListing_productid_'.$this->_accountID.'.log', $data->Product->id."\r\n");
            $dbTransaction = $this->getDbConnection()->beginTransaction();
            try {
                $productData = $data->Product;
                //检查产品是否已经存在
                $checkExists = $this->getDbConnection()->createCommand()->from(self::model()->tableName())->select('id')->where("product_id = :id", array(':id' => $data->Product->id))->queryRow();
                $params = array();
                $params = array(
                    'account_id' => $this->_accountID,
                    'product_id' => $productData->id,
                    'name' => $productData->name,
                    'num_sold' => $productData->number_sold,
                    'num_saves' => $productData->number_saves,
                    'review_status' => $productData->review_status,
                    'brand' => isset($productData->brand) ? $productData->brand : '',
                    'landing_page_url' => 'https://www.wish.com/c/' . $productData->id,
                    'upc' => isset($productData->upc) ? $productData->upc : '',
                    'main_image' => $productData->main_image,
                    'extra_images' => isset($productData->extra_images) ? $productData->extra_images : '',
                    'enabled' => 0,
                    'modify_time' => date("Y-m-d H:i:s", time()),
                    'is_promoted' => $productData->is_promoted == 'True' ? 1 : 0,
                    'date_uploaded' => isset($productData->date_uploaded) ? self::getWishDateUploaded($productData->date_uploaded) : '0000-00-00',
                );
                // 添加最后更新时间

                if ($productData->last_updated) {
                    $params['last_updated'] = \DateTime::createFromFormat('m-d-Y\TH:i:s', $productData->last_updated)->format('Y-m-d H:i:s');
                }
                $variants = $productData->variants;
                $params['is_varation'] = count($variants) > 1 ? 1 : 0;//是否多属性
                $onlineSku = isset($productData->parent_sku) ? $productData->parent_sku : (isset($variants[0]) ? $variants[0]->Variant->sku : '');
                $sku = $encryptSku->getRealSku($onlineSku);
                if (empty($sku)) {
                    $sku = $onlineSku;
                }
                $params['parent_sku'] = $onlineSku;
                $params['sku'] = $sku;
                foreach ($variants as $variant) {
                    if ($variant->Variant->enabled == 'True') {
                        $params['enabled'] = 1;
                        break;
                    };
                }
                //tags
                $tags = array();
                if (isset($productData->tags) && $productData->tags) {
                    foreach ($productData->tags as $tag) {
                        $tags[] = $tag->Tag->name;
                    }
                }
                $params['tags'] = implode(',', $tags);

                //默认光明本地仓，通过listingID获取海外仓ID，并入库
                $params['warehouse_id'] = 41;
                $warehouseInfo = WishOverseasWarehouse::model()->getInfoByCondition("product_id ='{$productData->id}'");
                if ($warehouseInfo) $params['warehouse_id'] = (int)$warehouseInfo['overseas_warehouse_id'];

                if ($checkExists) {
                    //获取对应的主键id
                    $listingID = $checkExists['id'];
                    $flag = $this->getDbConnection()->createCommand()->update(self::model()->tableName(), $params, "id=:id", array(':id' => $listingID));
                    if (!$flag) {
                        throw new Exception(Yii::t('wish', 'Update Product Info Failure'));
                    }
                } else {
                    $flag = $this->getDbConnection()->createCommand()->insert(self::model()->tableName(), $params);
                    if (!$flag) {
                        throw new Exception(Yii::t('wish', 'Save Product Info Failure'));
                    }
                    $listingID = $this->getDbConnection()->getLastInsertID();

                }
                unset($params);

                //save ueb_wish_listing_extend
                $extendData = array(
                    'listing_id' => $listingID,
                    'description' => $productData->description
                );
                $existsExtendInfo = $this->getDbConnection()->createCommand()
                    ->from('ueb_wish_listing_extend')
                    ->select('id')
                    ->where('listing_id=:listing_id', array(':listing_id' => $listingID))
                    ->queryRow();
                if ($existsExtendInfo) {
                    $flag = $this->getDbConnection()->createCommand()
                        ->update('ueb_wish_listing_extend', $extendData,
                            'listing_id=:listing_id', array(':listing_id' => $listingID));
                    if (!$flag) {
                        throw new Exception(Yii::t('wish', 'Update Product Extend Failure'));
                    }
                } else {
                    $flag = $this->getDbConnection()->createCommand()->insert('ueb_wish_listing_extend', $extendData);
                    if (!$flag) {
                        throw new Exception(Yii::t('wish', 'Save Product Extend Failure'));
                    }
                }
                unset($extendData);
                //本地删除
                // save ueb_listing_variants
                $existsVariantIds = array();
                foreach ($variants as $variant) {
                    $variantParams = array();
                    $variantParams['listing_id'] = $listingID;
                    $variantParams['account_id'] = $this->_accountID;
                    $variantOnlineSku = $variant->Variant->sku;
                    $variantSku = $encryptSku->getRealSku($variantOnlineSku);
                    if (empty($variantSku))
                        $variantSku = $variantOnlineSku;
                    $ID = $variantParams['variation_product_id'] = $variant->Variant->id;
                    $existsVariantIds[] = $ID;
                    $variantParams['product_id'] = $variant->Variant->product_id;
                    $variantParams['online_sku'] = $variantOnlineSku;
                    $variantParams['sku'] = $variantSku;
                    if (isset($variant->Variant->color))
                        $variantParams['color'] = $variant->Variant->color;
                    if (isset($variant->Variant->size))
                        $variantParams['size'] = $variant->Variant->size;
                    $variantParams['inventory'] = $variant->Variant->inventory;
                    $variantParams['price'] = $variant->Variant->price;
                    $variantParams['shipping'] = $variant->Variant->shipping;
                    $variantParams['msrp'] = $variant->Variant->msrp;
                    $variantParams['shipping_time'] = substr($variant->Variant->shipping_time, 0, strpos($variant->Variant->shipping_time, '-'));
                    $variantParams['enabled'] = $variant->Variant->enabled == 'True' ? 1 : 0;
                    $variantParams['modify_time'] = date("Y-m-d H:i:s", time());
                    if (isset($variant->Variant->all_images))
                        $variantParams['all_image'] = $variant->Variant->all_images;
                    $existsVariant = $this->getDbConnection()->createCommand()
                        ->from('ueb_listing_variants')
                        ->select('id')
                        ->where('variation_product_id=:variation_product_id AND listing_id=:listing_id',
                            array(':variation_product_id' => $ID, ':listing_id' => $listingID))
                        ->queryRow();
                    if ($existsVariant) {
                        $flag = $this->getDbConnection()->createCommand()
                            ->update('ueb_listing_variants', $variantParams,
                                'variation_product_id=:variation_product_id AND listing_id=:listing_id',
                                array(':variation_product_id' => $ID, ':listing_id' => $listingID));
                        if (!$flag) {
                            throw new Exception(Yii::t('wish', 'Update Product Variation Failure'));
                        }
                    } else {
                        $flag = $this->getDbConnection()->createCommand()->insert('ueb_listing_variants', $variantParams);
                        if (!$flag) {
                            throw new Exception(Yii::t('wish', 'Save Product Variation Failure'));
                        }
                    }
                    unset($variantParams);
                }
                if (isset($checkExists) && $checkExists) {
                    $this->getDbConnection()->createCommand()->delete('ueb_listing_variants', 'variation_product_id not in (' . MHelper::simplode($existsVariantIds) . ') AND listing_id=:listing_id',
                        array(':listing_id' => $listingID));
                }


                //MHelper::writefilelog('wish/wishlisting/'.date("Ymd").'/'.$this->_accountID.'/commit_'.$this->_accountID.'.log', json_encode(array('accountId'=>$this->_accountID, $productData->id))."\r\n");                  
                $dbTransaction->commit();

            } catch (Exception $e) {
                //MHelper::writefilelog('wish/wishlisting/'.date("Ymd").'/'.$this->_accountID.'/rollback_'.$this->_accountID.'.log', json_encode(array('accountId'=>$this->_accountID, $productData->id))."\r\n");                
                $errMsgs .= $e->getMessage();
                $dbTransaction->rollback();
            }
        }//endforeach
        $this->setExceptionMessage($errMsgs);
        return $errMsgs == '' ? true : false;
    }

    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message)
    {
        $this->exception = $message;
    }

    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage()
    {
        return $this->exception;
    }


    /**
     * @desc 根据本地SKU，同时根据账号id聚合，得到列表
     * @param unknown $sku
     * @param string $fields
     * @return multitype:
     */
    public function getProductListingBySkuGroupByAccountId($sku, $fields = 'account_id')
    {
        if (empty($sku)) return array();
        return $this->getDbConnection()->createCommand()
            ->from(self::tableName())
            ->where('sku=:sku', array(':sku' => $sku))
            ->group('account_id')
            ->select($fields)
            ->queryAll();
    }


    public function getProductJoinVariantsBySkus($sku, $accountId)
    {
        return $this->getDbConnection()->createCommand()
            ->from(WishVariants::tableName() . ' V')
            ->join(self::tableName() . ' P', "V.listing_id=P.id")
            ->where('V.account_id=:account_id AND P.account_id = :paccount_id AND (V.sku=:sku OR P.sku=:parent_sku)  ',
                array(':account_id' => $accountId, ':sku' => $sku, ':parent_sku' => $sku, ':paccount_id' => $accountId))
            ->select('P.sku as psku, P.parent_sku,V.sku,V.online_sku')
            ->queryRow();
    }

    /**
     * @desc 根据线上sku下架主产品及其子产品
     * @param unknown $skus
     * @return boolean
     */
    public function disabledWishProductByOnlineSku($skus, $accountID)
    {
        if (!$skus || empty($accountID)) return false;
        if (!is_array($skus)) {
            $skus = array($skus);
        }
        //首先获取需要下架的sku对应的id
        $result = $this->getDbConnection()->createCommand()
            ->from(self::tableName())
            ->where(array('IN', 'parent_sku', $skus))
            ->andWhere("account_id=:account_id", array(':account_id' => $accountID))
            ->select('id')
            ->queryAll();
        if (!$result) return false;
        $transaction = $this->getDbConnection()->beginTransaction();
        try {
            $listingIds = array();
            foreach ($result as $val) {
                $listingIds[] = $val['id'];
            }
            $this->getDbConnection()->createCommand()
                ->update(self::tableName(),
                    array('enabled' => WishVariants::WISH_PRODUCT_DISABLED),
                    array('IN', 'id', $listingIds));

            $this->getDbConnection()->createCommand()
                ->update(WishVariants::tableName(),
                    array('enabled' => WishVariants::WISH_PRODUCT_DISABLED),
                    array('IN', 'listing_id', $listingIds));
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollBack();
            return false;
        }
    }

    // ========== Start：接口请求调用部分 =============

    public function disabledVariants($accountID, $variantsSkuList)
    {
        $result = array(
            'success' => array(),
            'failure' => array()
        );
        $errorMsg = array();
        if (!is_array($variantsSkuList))
            $variantsSkuList = array($variantsSkuList);
        $disableProductVariantRequest = new DisabledProductVariantRequest();
        $disableProductVariantRequest->setAccount($accountID);
        foreach ($variantsSkuList as $sku) {
            $disableProductVariantRequest->setSku($sku);
            $response = $disableProductVariantRequest->setRequest()->sendRequest()->getResponse();
            if ($disableProductVariantRequest->getIfSuccess()) {
                $result['success'][] = $sku;
            } else {
                $result['failure'][] = $sku;
                $errorMsg[] = $result['errorMsg'][$sku] = $disableProductVariantRequest->getErrorMsg();
            }
        }
        $this->setExceptionMessage(implode(",", $errorMsg));
        return $result;
    }

    /**
     * @desc 下线产品
     * @param unknown $accountID
     * @param unknown $skuList
     * @return Ambigous <multitype:multitype: , unknown, multitype:>
     */
    public function disabledProduct($accountID, $skuList)
    {
        $result = array(
            'success' => array(),
            'failure' => array()
        );
        $errorMsg = array();
        if (!is_array($skuList))
            $skuList = array($skuList);
        $disableProductRequest = new DisabledProductRequest();
        $disableProductRequest->setAccount($accountID);
        foreach ($skuList as $sku) {
            $disableProductRequest->setSku($sku);
            $response = $disableProductRequest->setRequest()->sendRequest()->getResponse();
            if ($disableProductRequest->getIfSuccess()) {
                $result['success'][] = $sku;
            } else {
                $result['failure'][] = $sku;
                $errorMsg[] = $result['errorMsg'][$sku] = $disableProductRequest->getErrorMsg();
            }
        }
        $this->setExceptionMessage(implode(",", $errorMsg));
        return $result;
    }
    // ========== End: 接口请求调用部分 ==============


    // ========== Start: 针对于产品列表搜索展示  ============

    public function search()
    {
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'date_uploaded',
        );
        $dataProvider = parent::search($this, $sort, '', $this->_setdbCriteria());
        $dataProvider->setData($this->_additions($dataProvider->data));
        return $dataProvider;
    }

    /**
     * @desc  设置条件
     * @return CDbCriteria
     */
    private function _setdbCriteria()
    {
        $cdbcriteria = new CDbCriteria();
        /* $cdbcriteria->select = 't.id, t.product_id, t.parent_sku, t.sku, t.name, t.account_id,
         SUM(t.num_sold) AS num_sold_total,SUM(t.num_saves) AS num_saves_total,	review_status, enabled'; */
        $cdbcriteria->select = 't.id,t.sku,t.account_id,t.date_uploaded,t.warehouse_id,t.product_id,t.date_uploaded,t.is_promoted';
        //$cdbcriteria->group = 't.sku';

        $condition = array();
        $params = array();
        if (isset($_REQUEST['enabled']) && $_REQUEST['enabled']) {
            $enabled = (int)$_REQUEST['enabled'];
            if ($enabled == WishVariants::WISH_PRODUCT_DISABLED_MAPPING)
                $enabled = WishVariants::WISH_PRODUCT_DISABLED;
            $condition[] = 'enabled=:enabled';
            $params[':enabled'] = $enabled;
            $cdbcriteria->addCondition('t.enabled=' . $enabled);
        }

        if (isset($_REQUEST['online_sku']) && $_REQUEST['online_sku']) {
            $condition[] = "online_sku=:online_sku";
            $params[':online_sku'] = addslashes($_REQUEST['online_sku']);
        }
        if (isset($_REQUEST['account_id']) && $_REQUEST['account_id']) {
            $condition[] = 'account_id=:account_id';
            $params[':account_id'] = (int)$_REQUEST['account_id'];
        }
        if (isset($_REQUEST['sku']) && $_REQUEST['sku']) {
            $sku = addslashes($_REQUEST['sku']);
            $condition[] = "sku like '{$sku}%'";
        }
        if (isset($_REQUEST['subsku']) && $_REQUEST['subsku']) {
            $sku = addslashes($_REQUEST['subsku']);
            $condition[] = "sku=:sku";
            $params[':sku'] = $sku;
        }
        //发货仓库查询
        if (isset($_REQUEST['send_warehouse']) && $_REQUEST['send_warehouse']) {
            //如果查询子SKU，则不把海外仓加到条件中
            if (isset($_REQUEST['subsku']) && $_REQUEST['subsku']) {
            } else {
                $warehouse_id = (int)$_REQUEST['send_warehouse'];
                $condition[] = 'warehouse_id=:warehouse_id';
                $params[':warehouse_id'] = $warehouse_id;
                $cdbcriteria->addCondition('t.warehouse_id=' . $warehouse_id);
            }
        }

        //是否促销
        /*  if(isset($_REQUEST['is_promoted'])){
             $isPromoted = (int)$_REQUEST['is_promoted'];
             $condition[] = 'is_promoted=:is_promoted';
             $params[':is_promoted'] = $isPromoted;
             $cdbcriteria->addCondition('t.is_promoted='.$isPromoted);
         } */

        //销售人员只能看指定分配的账号数据
        $accountIdArr = array();
        if (isset(Yii::app()->user->id)) {
            $accountIdArr = WishAccountSeller::model()->getListByCondition('account_id', 'seller_user_id = ' . Yii::app()->user->id);
            if ($accountIdArr) {
                $ids = implode(',', $accountIdArr);
                $cdbcriteria->addCondition('t.account_id in(' . $ids . ')');
            }
        }

        if (isset($_REQUEST['name']) && $_REQUEST['name']) {
            $title = $this->merge_spaces(trim($_REQUEST['name']));
            $titleSkuArr = $this->getSkuByAllTitle($title);
            if (!$titleSkuArr) {
                $cdbcriteria->addCondition('1=0');
            } else {
                $titleSkuArr = array_flip(array_flip($titleSkuArr)); //去重SKU
                $cdbcriteria->addInCondition('t.sku', $titleSkuArr);
            }
        }

        //查询主表销售人员关联的listing
        if (isset($_REQUEST['seller_id']) && $_REQUEST['seller_id']) {
            $sellerID = (int)$_REQUEST['seller_id'];
            $wishProductSellerRelationModel = new WishProductSellerRelation();
            $wishRelationTable = $wishProductSellerRelationModel->tableName();
            $tempSql = " (select seller_id,item_id from {$wishRelationTable} group by item_id) ";
            $cdbcriteria->join = " left join {$tempSql} ps on ps.item_id = t.product_id ";
            $cdbcriteria->addCondition("ps.seller_id = {$sellerID}");
        }

        //刊登时间
        if ((isset($_REQUEST['date_uploaded'][0]) && !empty($_REQUEST['date_uploaded'][0])) && isset($_REQUEST['date_uploaded'][1]) && !empty($_REQUEST['date_uploaded'][1])) {
            $cdbcriteria->addCondition("t.date_uploaded >= '" . addslashes($_REQUEST['date_uploaded'][0]) . "' AND t.date_uploaded <= '" . addslashes($_REQUEST['date_uploaded'][1]) . "'");
        }

        if ($condition && (isset($params[':online_sku']) || isset($params[':sku']))) {
            $conditions = implode(" AND ", $condition);
            $variantList = $this->getDbConnection()->createCommand()->from(self::model('WishVariants')->tableName())
                ->select('listing_id')
                ->group('listing_id')
                ->where($conditions, $params)
                //->limit(5000)
                ->queryAll();
            $listingIds = '';
            if ($variantList) {
                $variantIds = array();
                foreach ($variantList as $variant) {
                    $variantIds[] = $variant['listing_id'];
                }
                $listingIds = implode(",", $variantIds);
                $cdbcriteria->addCondition('id in(' . $listingIds . ')');
            } else {
                $cdbcriteria->addCondition('id < 0');
            }
        }
        return $cdbcriteria;
    }

    /**
     * @desc 增加额外的数据
     * @param unknown $datas
     */
    private function _additions($datas)
    {
        if (empty($datas)) return $datas;
        $warehouseList = array();
        $warehouseList = WishOverseasWarehouse::model()->getWarehouseList();
        $sellerUserList = User::model()->getPairs();
        foreach ($datas as $key => $data) {
            //获取当前父级SKU所拥有的变种产品列表
            //notice: 此处代码编程方式是原有模式下的代码，后由业务方改变而致，为保持可能回滚的业务需求，暂不完全修改
            $condition = array('sku=:sku', 'account_id=:account_id', 'id=:id', 'warehouse_id=:warehouse_id');
            $params = array(':sku' => $data['sku'], ':account_id' => $data['account_id'], ':id' => $data['id'], ':warehouse_id' => $data['warehouse_id']);
            if (isset($_REQUEST['enabled']) && $_REQUEST['enabled']) {
                $condition[] = 'enabled=:enabled';
                $enabled = (int)$_REQUEST['enabled'];
                if ($enabled == WishVariants::WISH_PRODUCT_DISABLED_MAPPING)
                    $enabled = WishVariants::WISH_PRODUCT_DISABLED;
                $params[':enabled'] = $enabled;
            }
            if (isset($_REQUEST['account_id']) && $_REQUEST['account_id']) {
                $condition[] = "account_id = '" . (int)$_REQUEST['account_id'] . "'";
            }
            if (isset($_REQUEST['parent_sku']) && $_REQUEST['parent_sku']) {
                $condition[] = "parent_sku = '" . addslashes($_REQUEST['parent_sku']) . "'";
            }

            //是否促销
            if (isset($_REQUEST['is_promoted']) && is_int($_REQUEST['is_promoted'])) {
                $isPromoted = (int)$_REQUEST['is_promoted'];
                $condition[] = 'is_promoted=:is_promoted';
                $params[':is_promoted'] = $isPromoted;
            }
            if ($condition)
                $conditions = implode(' AND ', $condition);
            $listings = $this->getDbConnection()->createCommand()->from(self::tableName())
                ->select('id, product_id, parent_sku, sku, name, account_id,num_sold,num_saves,	review_status, enabled, main_image, date_uploaded, warehouse_id')
                ->where($conditions, $params)
                ->queryAll();
            $newListingIds = array();
            $num_sold_total = $num_saves_total = 0;
            if ($listings) {
                foreach ($listings as $id) {
                    $newListingIds[] = $id['id'];
                    $num_sold_total += $id['num_sold'];
                    $num_saves_total += $id['num_saves'];
                }
            }
            $parentInfo = $listings[0];
            $datas[$key]->id = $parentInfo['id'];
            $datas[$key]->product_id = $parentInfo['product_id'];
            $datas[$key]->parent_sku = $parentInfo['parent_sku'];
            $datas[$key]->sku = $parentInfo['sku'];
            $datas[$key]->name = $parentInfo['name'];
            $datas[$key]->product_link = "<a href='https://www.wish.com/c/{$parentInfo['product_id']}' target='__blank'>{$parentInfo['product_id']}</a>";
            $datas[$key]->main_image_url = "<img src='{$parentInfo['main_image']}' width='60' height='60'/>";
            $datas[$key]->account_id = $parentInfo['account_id'];
            $datas[$key]->num_sold_total = $num_sold_total;
            $datas[$key]->num_saves_total = $num_saves_total;
            $datas[$key]->date_uploaded = $parentInfo['date_uploaded'];

            $datas[$key]->review_status = $parentInfo['review_status'];
            $datas[$key]->enabled = $parentInfo['enabled'];
            $datas[$key]->account_name = isset(self::$wishAccountPairs[$datas[$key]->account_id]) ? self::$wishAccountPairs[$datas[$key]->account_id] : '';

            $datas[$key]->review_status_text = $this->getWishProductReviewStatusText($data['review_status']); //获取审核状态
            $datas[$key]->promoted_text = $this->getWishPromotedOptions($data['is_promoted']); //获取审核状态

            $datas[$key]->send_warehouse = (isset($parentInfo['warehouse_id']) && $parentInfo['warehouse_id'] > 0 && isset($warehouseList[$parentInfo['warehouse_id']])) ? $warehouseList[$parentInfo['warehouse_id']] : ''; //发货仓库

            $variants = $this->filterWishProductVarantListByListingIds($newListingIds);
            if (empty($variants)) {
                $productSellerRelationInfo = WishProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU($parentInfo['product_id'], $parentInfo['sku'], $parentInfo['parent_sku']);
                $sellerName = $productSellerRelationInfo && isset($sellerUserList[$productSellerRelationInfo['seller_id']]) ? $sellerUserList[$productSellerRelationInfo['seller_id']] : '-';
                $variant = array(
                    'staus_text' => $this->getWishProductVariantStatusText($parentInfo['enabled']),
                    'sale_property' => '',
                    'variants_id' => 0,
                    'enabled' => $parentInfo['enabled'],
                    'oprator' => '',
                    'online_sku' => $parentInfo['parent_sku'],
                    'subsku' => $parentInfo['sku'],
                    'msrp' => '', 'shipping' => '', 'price' => '',
                    'inventory' => '',
                    'account_name' => isset(self::$wishAccountPairs[$datas[$key]->account_id]) ? self::$wishAccountPairs[$datas[$key]->account_id] : '',
                    'seller_name' => $sellerName
                );
                $datas[$key]->detail[] = $variant;
                continue;
            }
            $datas[$key]->detail = array();
            foreach ($variants as $variant) {
                $productSellerRelationInfo = WishProductSellerRelation::model()->getProductSellerRelationInfoByItemIdandSKU($variant['product_id'], $variant['sku'], $variant['online_sku']);
                $sellerName = $productSellerRelationInfo && isset($sellerUserList[$productSellerRelationInfo['seller_id']]) ? $sellerUserList[$productSellerRelationInfo['seller_id']] : '-';
                $variant['staus_text'] = $this->getWishProductVariantStatusText($variant['enabled']);
                $variant['sale_property'] = $this->getWishProductVariantSalePropertyText($variant['color'], $variant['size']);
                $variant['variants_id'] = $variant['id'];
                $variant['oprator'] = $this->getWishProductVariantOprator($data['review_status'], $variant['enabled'], $variant['id']);
                $variant['account_name'] = isset(self::$wishAccountPairs[$variant['account_id']]) ? self::$wishAccountPairs[$variant['account_id']] : '';
                $variant['subsku'] = $variant['sku'];
                $variant['seller_name'] = $sellerName;
                $datas[$key]->detail[] = $variant;
            }
        }
        return $datas;
    }

    /**
     * @desc 获取wish产品审核状态文本
     */
    public function getWishProductReviewStatusText($reviewStatus)
    {
        $str = '';
        $color = 'red';
        switch ($reviewStatus) {
            case self::REVIEW_STATUS_REJECTED:
                $str = Yii::t('wish_listing', 'Review Rejected Status');
                break;
            case self::REVIEW_STATUS_PENDING:
                $str = Yii::t('wish_listing', 'Review Pending Status');
                break;
            case self::REVIEW_STATUS_APPROVED:
                $str = Yii::t('wish_listing', 'Review Approved Status');
                $color = 'green';
                break;
        }
        return '<font color="' . $color . '">' . $str . '</font>';
    }

    /**
     * @desc
     * @param string $isPromoted
     * @return string|multitype:string
     */
    public function getWishPromotedOptions($isPromoted = null)
    {
        $options = array(
            self::PROMOTED_STATUS_NO => '无促销',
            self::PROMOTED_STATUS_YES => '促销中'
        );
        if (!is_null($isPromoted)) {
            return isset($options[$isPromoted]) ? $options[$isPromoted] : '';
        }
        return $options;
    }

    /**
     * @desc 获取产品变种列表
     * @param unknown $productId
     */
    public function filterWishProductVarantListByProductId($productId)
    {
        $condition = array();
        $params = array();
        if (isset($_REQUEST['enabled']) && $_REQUEST['enabled']) {
            $condition[] = 'enabled=:enabled';
            $enabled = (int)$_REQUEST['enabled'];
            if ($enabled == WishVariants::WISH_PRODUCT_DISABLED_MAPPING)
                $enabled = WishVariants::WISH_PRODUCT_DISABLED;
            $params[':enabled'] = $enabled;
        }
        if (isset($_REQUEST['account_id']) && $_REQUEST['account_id']) {
            $condition[] = "account_id = '" . (int)$_REQUEST['account_id'] . "'";

        }

        if (isset($_REQUEST['online_sku']) && $_REQUEST['online_sku']) {
            $condition[] = "online_sku LIKE '" . addslashes($_REQUEST['online_sku']) . "%'";
        }
        $conditions = null;
        if ($condition)
            $conditions = implode(' AND ', $condition);
        return self::model('WishVariants')->getWishProductVarantListByProductId($productId, $conditions, $params);
    }

    /**
     * @desc 根据listingId获取子sku
     * @param unknown $listingIds
     * @return multitype:|Ambigous <multitype:, mixed, NULL, unknown, multitype:unknown Ambigous <unknown, NULL> , CActiveRecord, multitype:unknown Ambigous <CActiveRecord, NULL> , multitype:unknown >
     */
    public function filterWishProductVarantListByListingIds($listingIds)
    {
        if (empty($listingIds)) return array();
        $condition = array();
        $params = array();
        $conditions = "listing_id IN('" . implode("','", $listingIds) . "') ";
        if (isset($_REQUEST['enabled']) && $_REQUEST['enabled']) {
            $condition[] = 'enabled=:enabled';
            $enabled = (int)$_REQUEST['enabled'];
            if ($enabled == WishVariants::WISH_PRODUCT_DISABLED_MAPPING)
                $enabled = WishVariants::WISH_PRODUCT_DISABLED;
            $params[':enabled'] = $enabled;
        }
        if (isset($_REQUEST['account_id']) && $_REQUEST['account_id']) {
            $condition[] = "account_id = '" . (int)$_REQUEST['account_id'] . "'";
        }
        if (isset($_REQUEST['online_sku']) && $_REQUEST['online_sku']) {
            $condition[] = "online_sku LIKE '" . addslashes($_REQUEST['online_sku']) . "%'";
        }
        if (isset($_REQUEST['subsku']) && $_REQUEST['subsku']) {
            $condition[] = "sku='" . addslashes($_REQUEST['subsku']) . "'";
        }

        $wishVariantsModel = new WishVariants();

        /*   //在多变体表获取销售人员对应的子SKU
           if(isset($_REQUEST['seller_id']) && $_REQUEST['seller_id']){
               $sellerID = (int)$_REQUEST['seller_id'];
               $where = "seller_id = {$sellerID}";
               // $wishProductSellerRelationModel = new WishProductSellerRelation();
               $List = WishProductSellerRelation::model()->getListByCondition("item_id,online_sku",$where);
               $variationIds = '';
               if($List){
                   $itemIds = array();
                   foreach ($List as $item){
                       //查询多变体表
                       $itemID = $item['item_id'];
                       $onlineSKU = $item['online_sku'];
                       $where = "product_id = '{$itemID}' AND online_sku = '{$onlineSKU}'";
                       $info = $wishVariantsModel->getInfoByCondition($where);
                       if($info) $itemIds[] = $info['id'];
                   }
                   if ($itemIds){
                       $variationIds = implode(",", $itemIds);
                       $condition[] ="id in(".$variationIds.")";
                   }
               }
           }*/

        if ($condition)
            $conditions .= " AND " . implode(' AND ', $condition);
        return $wishVariantsModel->getWishProductVarantList($conditions, $params);
    }

    /**
     * @desc 获取操作文案
     * @param unknown $status
     * @param unknown $variantId
     * @return string
     */
    public function getWishProductVariantOprator($reviewStatus, $status, $variantId)
    {
        $str = "<select style='width:75px;' onchange = 'offLine(this," . $variantId . ")' >
				<option>" . Yii::t('system', 'Please Select') . "</option>";
        /**
         * fixed: 产品正在审核也可以下架
         */
        if ($status == WishVariants::WISH_PRODUCT_ENABLED) {
            $str .= '<option value="offline">' . Yii::t('wish_listing', 'Product Disabled') . '</option>';
        }
        $str .= "</select>";
        return $str;
    }

    /**
     * @desc 获取产品变种状态文案
     * @param unknown $enabled
     * @return string
     */
    public function getWishProductVariantStatusText($enabled)
    {
        $statusText = '';
        $color = 'red';
        switch ($enabled) {
            case WishVariants::WISH_PRODUCT_ENABLED:
                $color = 'green';
                $statusText = Yii::t('wish_listing', 'Product Enabled');
                break;
            case WishVariants::WISH_PRODUCT_DISABLED:
                $statusText = Yii::t('wish_listing', 'Product Disabled');
                break;
        }
        return '<font color=' . $color . '>' . $statusText . '</font>';
    }

    /**
     * @desc 获取产品变种销售属性文案
     * @param unknown $color
     * @param unknown $size
     * @return string
     */
    public function getWishProductVariantSalePropertyText($color, $size)
    {
        $saleProperty = '';
        if ($color)
            $saleProperty .= Yii::t('wish_listing', 'Color') . ':' . $color;
        if ($size)
            //$saleProperty .= " | ".Yii::t('wish_listing', 'Size').':'.$size;
            $saleProperty .= "<br />" . Yii::t('wish_listing', 'Size') . ':' . $size;
        return $saleProperty;
    }

    /**
     * @desc 设置搜索栏内容
     * @return multitype:multitype:string multitype:string   multitype:string NULL
     */
    public function filterOptions()
    {
        $isPromotedd = Yii::app()->request->getParam("is_promoted");
        return array(
            array(
                'name' => 'sku',
                'type' => 'text',
                'search' => '=',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),

            array(
                'name' => 'subsku',
                'type' => 'text',
                'search' => 'LIKE',
                'htmlOption' => array(
                    'size' => '22',
                ),
                'rel' => true
            ),
            array(
                'name' => 'online_sku',
                'type' => 'text',
                'search' => 'LIKE',
                'rel' => true,
                'htmlOption' => array(
                    'size' => '22',
                    'style' => 'width:260px'
                )
            ),
            array(
                'name' => 'parent_sku',
                'type' => 'text',
                'search' => 'LIKE',
                'htmlOption' => array(
                    'size' => '22'
                )
            ),

            array(
                'name' => 'product_id',
                'type' => 'text',
                'search' => '=',
                'htmlOption' => array(
                    'size' => '22'
                )
            ),
            array(
                'name' => 'name',
                'type' => 'text',
                'search' => 'LIKE',
                'rel' => true,
                'alias' => 't',
                'htmlOption' => array(
                    'size' => '200',
                    'style' => 'width:300px;',
                    'width' => '400px'
                ),

            ),
            array(
                'name' => 'date_uploaded',
                'type' => 'text',
                'search' => 'RANGE',
                'htmlOptions' => array(
                    'class' => 'date',
                    'dateFmt' => 'yyyy-MM-dd HH:mm:ss',
                    'style' => 'width:120px;',
                    'width' => '300px'
                ),
            ),
            array(
                'name' => 'review_status',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getWishReviewStatusOptions()
            ),
            array(
                'name' => 'is_promoted',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getWishPromotedOptions(),
                'value' => $isPromotedd
            ),
            array(
                'name' => 'account_id',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getWishAccountList()
            ),
            array(
                'name' => 'send_warehouse',
                'type' => 'dropDownList',
                'search' => '=',
                'rel' => true,
                'data' => WishOverseasWarehouse::model()->getWarehouseList()
            ),
            array(
                'name' => 'enabled',
                'type' => 'dropDownList',
                'search' => '=',
                'rel' => true,
                'data' => $this->getWishProductStatusOptions()
            ),
            array(
                'name' => 'seller_id',
                'type' => 'dropDownList',
                'rel' => true,
                'data' => User::model()->getWishUserList(),
                'search' => '=',

            ),
            array(
                'name' => 'num_sold',
                'type' => 'text',
                'search' => 'RANGE',

            ),
            array(
                'name' => 'num_saves',
                'type' => 'text',
                'search' => 'RANGE',

            ),
        );
    }

    /**
     * @desc  获取公司账号
     */
    public function getWishAccountList()
    {
        if (self::$wishAccountPairs == null) {
            $list = array();
            $ret = self::model('WishAccount')->getIdNamePairs();
            if (isset(Yii::app()->user->id)) {
                $accountIdArr = WishAccountSeller::model()->getListByCondition('account_id', 'seller_user_id = ' . Yii::app()->user->id);
            }

            if ($ret) {
                //如果有账号限制
                if ($accountIdArr) {
                    foreach ($ret as $key => $val) {
                        if (in_array($key, $accountIdArr)) {
                            $list[$key] = $val;
                        }
                    }
                } else {
                    $list = $ret;
                }
            }
            self::$wishAccountPairs = $list;
        }
        return self::$wishAccountPairs;
    }

    /**
     * @desc 获取产品状态选线
     * @return multitype:NULL Ambigous <string, string, unknown>
     */
    public function getWishProductStatusOptions()
    {
        return array(
            WishVariants::WISH_PRODUCT_ENABLED => Yii::t('wish_listing', 'Product Enabled'),
            WishVariants::WISH_PRODUCT_DISABLED_MAPPING => Yii::t('wish_listing', 'Product Disabled')
        );
    }

    /**
     * @desc 获取产品审核状态选项
     * @return multitype:NULL Ambigous <string, string, unknown>
     */
    public function getWishReviewStatusOptions()
    {
        return array(
            self::REVIEW_STATUS_REJECTED => Yii::t('wish_listing', 'Review Rejected Status'),
            self::REVIEW_STATUS_PENDING => Yii::t('wish_listing', 'Review Pending Status'),
            self::REVIEW_STATUS_APPROVED => Yii::t('wish_listing', 'Review Approved Status')
        );
    }

    /**
     * @desc 定义字段名称
     * @return multitype:string NULL Ambigous <string, string, unknown>
     */
    public function attributeLabels()
    {
        return array(
            'variants_id' => '',
            'sku' => Yii::t('wish_listing', 'Sku'),
            'subsku' => Yii::t('wish_listing', 'Sub sku'),
            'enabled' => Yii::t('wish_listing', 'Status Text'),
            'parent_sku' => Yii::t('wish_listing', 'Parent Sku'),
            'name' => Yii::t('wish_listing', 'Product Name'),
            'review_status_text' => Yii::t('wish_listing', 'Product Review Status'),
            'review_status' => Yii::t('wish_listing', 'Product Review Status'),
            'online_sku' => Yii::t('wish_listing', 'Online Sku'),
            'sale_property' => Yii::t('wish_listing', 'Sale Property'),
            'inventory' => Yii::t('wish_listing', 'Inventory'),
            'price' => Yii::t('wish_listing', 'Price'),
            'shipping' => Yii::t('wish_listing', 'Shipping'),
            'msrp' => Yii::t('wish_listing', 'Market Recommand Price'),
            'oprator' => Yii::t('system', 'Oprator'),
            'staus_text' => Yii::t('wish_listing', 'Status Text'),
            'account_id' => Yii::t('wish_listing', 'Account Name'),
            'account_name' => Yii::t('wish_listing', 'Account Name'),
            'num_sold' => Yii::t('wish_listing', 'Num Sold'),
            'num_saves' => Yii::t('wish_listing', 'Num Saves'),
            'main_image' => Yii::t('wish_listing', 'Product Images'),
            'product_id' => Yii::t('wish_listing', 'Product ID'),
            'date_uploaded' => Yii::t('wish_listing', 'Date Uploaded'),
            'seller_name' => Yii::t('common', 'Seller Name'),
            'send_warehouse' => Yii::t('wish_listing', 'Send Warehouse'),
            'seller_id' => Yii::t('wish_listing', 'Seller'),
            'is_promoted' => Yii::t('wish_listing', 'Is Promoted'),
        );
    }
    // ========== End: 针对于产品列表搜索展示 ==============


    /**
     * @desc 查询listing,用于sku导入下架
     *
     */
    public function getListingSkusForOffline($sku, $accountId)
    {
        $parent = $this->getDbConnection()->createCommand()
            ->from(self::tableName() . ' P')
            ->where('P.account_id = :paccount_id AND P.sku=:parent_sku  ', array(':parent_sku' => $sku, ':paccount_id' => $accountId))
            ->select('P.sku as psku, P.parent_sku')
            ->queryRow();

        if ($parent) {
            return $parent;
        } else {
            $son = $this->getDbConnection()->createCommand()
                ->from(WishVariants::tableName() . ' V')
                ->where('V.account_id = :account_id AND V.sku=:sku  ', array(':sku' => $sku, ':account_id' => $accountId))
                ->select('V.sku, V.online_sku')
                ->queryRow();
            if ($son) {
                $son['psku'] = '';
                return $son;
            } else {
                return false;
            }
        }
    }

    /**
     * @desc 将listing、variants当前表数据备份到历史表中
     */
    public function bakListing($log_id)
    {
        $bak_time = date('Y-m-d H:i:s', time());
        set_time_limit(5 * 3600);
        ini_set('memory_limit', '2048M');
//        $dbTransaction = $this->dbConnection->getCurrentTransaction();
//        if( !$dbTransaction ){
//            $dbTransaction = $this->dbConnection->beginTransaction();//开启事务
//        } else {
//            $dbTransaction->rollback();
//        }
        //try{
        //备份ueb_wish_listing表
        $flag = WishListing::model()->getDbConnection()->createCommand()->update(WishListing::model()->tableName(), array('bak_time' => $bak_time, 'log_id' => $log_id), "account_id = '{$this->_accountID}'");
        if (!$flag) {
            $this->setExceptionMessage('update log_id Failure');
            return false;
        }
        $sql_bak_listing = "insert into market_wish.ueb_wish_listing_history select * from market_wish.ueb_wish_listing where account_id = '{$this->_accountID}'";
        $flag = WishListing::model()->getDbConnection()->createCommand($sql_bak_listing)->query();
        if (!$flag) {
            $this->setExceptionMessage('bak listing Failure');
            return false;
        }
        //备份ueb_listing_variants表
        WishVariants::model()->getDbConnection()->createCommand()->update(WishVariants::model()->tableName(), array('bak_time' => $bak_time, 'log_id' => $log_id), "account_id = '{$this->_accountID}'");
        $sql_bak_variants = "insert into market_wish.ueb_listing_variants_history select * from market_wish.ueb_listing_variants where  account_id = '{$this->_accountID}'";
        $flag = WishVariants::model()->getDbConnection()->createCommand($sql_bak_variants)->query();
        if (!$flag) {
            $this->setExceptionMessage('bak variants Failure');
            return false;
        }
        //$dbTransaction->commit();
        return true;
//        } catch (Exception $e){
//            $dbTransaction->rollback();
//            $this->setExceptionMessage($e->getMessage());
//            return false;
//        }
    }


    /**
     * @desc 保存listing数据新
     * @param unknown $datas
     */
    public function saveWishListingNew($datas, $logID)
    {
        $encryptSku = new encryptSku();
        $listing_insert = array();
        $variants_insert = array();
        $extend_insert = array();
        $id_array = array();
//        $dbTransaction = $this->getDbConnection()->getCurrentTransaction();
//        if (empty($dbTransaction)){
//                $dbTransaction = $this->getDbConnection()->beginTransaction();
//        } else {
//            $dbTransaction->rollback();
//        }
        try {
            foreach ($datas as $data) {

                $productData = $data->Product;
                $product_id = $data->Product->id;

                //检查产品是否已经存在
                $checkExists = $this->getDbConnection()->createCommand()->from(self::model()->tableName())->select('id')->where("product_id = :id", array(':id' => $data->Product->id))->queryRow();

                $variants = $productData->variants;
                $onlineSku = isset($productData->parent_sku) ? $productData->parent_sku : (isset($variants[0]) ? $variants[0]->Variant->sku : '');
                $sku = $encryptSku->getRealSku($onlineSku);
                if (empty($sku)) {
                    $sku = $onlineSku;
                }
                $enabled = 0;
                foreach ($variants as $variant) {
                    if ($variant->Variant->enabled == 'True') {
                        $enabled = 1;
                        break;
                    };
                }
                //tags
                $tags = array();
                if (isset($productData->tags) && $productData->tags) {
                    foreach ($productData->tags as $tag) {
                        $tags[] = $tag->Tag->name;
                    }
                }

                //listing
                $listing_one = array(
                    'account_id' => $this->_accountID,
                    'product_id' => $productData->id,
                    'name' => $productData->name,
                    'parent_sku' => $onlineSku,
                    'sku' => $sku,
                    'num_sold' => $productData->number_sold,
                    'num_saves' => $productData->number_saves,
                    'review_status' => $productData->review_status,
                    'brand' => isset($productData->brand) ? $productData->brand : '',
                    'landing_page_url' => isset($productData->landing_page_url) ? $productData->landing_page_url : '',
                    'upc' => isset($productData->upc) ? $productData->upc : '',
                    'main_image' => $productData->main_image,
                    'extra_images' => isset($productData->extra_images) ? $productData->extra_images : '',
                    'enabled' => $enabled,
                    'is_promoted' => $productData->is_promoted == 'True' ? 1 : 0,
                    'tags' => implode(',', $tags),
                    'modify_time' => date("Y-m-d H:i:s", time()),
                    'confirm_status' => 1,
                    'bak_time' => date("Y-m-d H:i:s", time()),
                    'log_id' => $logID,
                );
                if ($checkExists) {
                    //获取对应的主键id
                    $listingID = $checkExists['id'];
                    $id_array[] = $listingID;
                    $listing_one['id'] = $listingID;
                    $listing_insert[] = $listing_one;
                } else {
                    //新增listing
                    $flag = $this->getDbConnection()->createCommand()->insert(self::model()->tableName(), $listing_one);
                    if (!$flag)
                        throw new Exception(Yii::t('wish', 'Save Product Failure'));
                    $listingID = $this->getDbConnection()->getLastInsertID();

                    //extend
                    $extend_insert[] = array(
                        'listing_id' => $listingID,
                        'description' => $productData->description
                    );
                }

                //variants
                foreach ($variants as $variant) {
                    $variantOnlineSku = $variant->Variant->sku;
                    $variantSku = $encryptSku->getRealSku($variantOnlineSku);
                    if (empty($variantSku)) {
                        $variantSku = $variantOnlineSku;
                    }

                    $variants_insert[] = array(
                        'account_id' => $this->_accountID,
                        'listing_id' => $listingID,
                        'variation_product_id' => $variant->Variant->id,
                        'product_id' => $variant->Variant->product_id,
                        'online_sku' => $variantOnlineSku,
                        'sku' => $variantSku,
                        'color' => isset($variant->Variant->color) ? $variant->Variant->color : null,
                        'size' => isset($variant->Variant->size) ? $variant->Variant->size : null,
                        'inventory' => $variant->Variant->inventory,
                        'price' => $variant->Variant->price,
                        'shipping' => $variant->Variant->shipping,
                        'msrp' => $variant->Variant->msrp,
                        'shipping_time' => $variant->Variant->shipping_time,
                        'all_image' => isset($variant->Variant->all_images) ? $variant->Variant->all_images : null,
                        'enabled' => $variant->Variant->enabled == 'True' ? 1 : 0,
                        'modify_time' => date("Y-m-d H:i:s", time()),
                        'confirm_status' => 1,
                        'bak_time' => date("Y-m-d H:i:s", time()),
                        'log_id' => $logID,
                    );

                }
            }

            if ($id_array) {
                //2根据已有id删除listing,删除variants
                $flag = WishListing::model()->getDbConnection()->createCommand()->delete(WishListing::model()->tableName(), 'id IN (' . MHelper::simplode($id_array) . ')');
                if (!$flag)
                    throw new Exception(Yii::t('wish', 'delete listing Failure'));
                $flag = WishVariants::model()->getDbConnection()->createCommand()->delete(WishVariants::model()->tableName(), 'listing_id IN (' . MHelper::simplode($id_array) . ')');
                if (!$flag)
                    throw new Exception(Yii::t('wish', 'delete variants Failure'));
            }

            //3批量插入listing
            $table_listing = WishListing::model()->tableName();
            $table_variants = WishVariants::model()->tableName();
            $table_extend = WishListingExtend::model()->tableName();
            $flag = $this->insertBatch($listing_insert, $table_listing);
            if (!$flag)
                throw new Exception(Yii::t('wish', 'insert batch listing Failure'));
            $flag = $this->insertBatch($variants_insert, $table_variants);
            if (!$flag)
                throw new Exception(Yii::t('wish', 'insert batch variants Failure'));
            $flag = $this->insertBatch($extend_insert, $table_extend);
            if (!$flag)
                throw new Exception(Yii::t('wish', 'insert batch e Failure'));
            //$dbTransaction->commit();
        } catch (Exception $e) {
            //$dbTransaction->rollback();
            $this->setExceptionMessage($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * @desc 批量插入数据
     * @param unknown $data
     */
    public function insertBatch($data, $table = null)
    {
        if (!$table) {
            $table = self::tableName();
        }
        if (empty($data)) {
            return true;
        }
        $columns = array();
        $sql = "INSERT INTO {$table} ( ";
        foreach ($data[0] as $column => $value) {
            $columns[] = $column;
            $sql .= '`' . $column . '`,';
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        $sql .= " ) VALUES ";
        foreach ($data as $one) {
            $sql .= "(";
            foreach ($one as $value) {
                $value = (!get_magic_quotes_gpc()) ? addslashes($value) : $value;
                $sql .= '"' . $value . '",';
            }
            $sql = substr($sql, 0, strlen($sql) - 1);
            $sql .= "),";
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        return self::model()->getDbConnection()->createCommand($sql)->query();
    }


    /**
     * 标题搜索 去掉标题中多余空格只保留一个
     * @param  $string
     *
     */
    static public function merge_spaces($string)
    {
        return preg_replace("/\s(?=\s)/", "\\1", $string);
    }

    /**
     * 任意标题搜索 获取sku
     *
     */
    public function getSkuByAllTitle($title)
    {
        if ($title == '') return array();
        $title = explode(' ', $title);
        $newtitle = array();
        foreach ($title as $val) {
            $newtitle[] = '%' . $val . '%';
        }
        $data = $this->getDbConnection()->createCommand()
            ->select('sku')
            ->from(self::tableName())
            ->where(array('like', 'name', $newtitle))
            ->queryAll();
        $list = array();
        if ($data) {
            foreach ($data as $key => $val) {
                $list[] = $val['sku'];
            }
        }
        return $list;
    }

    /**
     * @desc 批量删除
     * @param unknown $ids
     * @return multitype:|boolean
     */
    public function batchDeleteProductByIds($ids)
    {
        if (empty($ids)) return array();
        try {
            $dbTransaction = $this->getDbConnection()->beginTransaction();
            WishListing::model()->deleteAll("id in(" . MHelper::simplode($ids) . ")");
            WishVariants::model()->deleteAll("listing_id in(" . MHelper::simplode($ids) . ")");
            WishListingExtend::model()->deleteAll("listing_id in(" . MHelper::simplode($ids) . ")");
            $dbTransaction->commit();
            return true;
        } catch (Exception $e) {
            $dbTransaction->rollback();
            return false;
        }
    }

    /**
     * @desc 根据条件更新数据
     * @param string $condition
     * @param array $updata
     * @return boolean
     */
    public function updateListByCondition($conditions, $updata)
    {
        if (empty($conditions) || empty($updata)) return false;
        return $this->getDbConnection()->createCommand()->update($this->tableName(), $updata, $conditions);
    }


    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where [description]
     * @param  mixed $order [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getListByCondition($fields = '*', $where = '1', $order = '')
    {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }


    /**
     * @desc 复制刊登
     * @param array $info
     * @param unknown $accountID
     * @param string $addType
     * @return boolean
     */
    public function copyListingToAdd($info, $accountID, $addType = null)
    {
        if (is_null($addType)) $addType = WishProductAdd::ADD_TYPE_DEFAULT;
        $sku = $info['sku'];
        $wishProductAddModel = new WishProductAdd();
        $wishProductVariantsAddModel = new WishProductVariantsAdd();
        $wishAccountModel = new WishAccount();
        $wishListingExtendModel = new WishListingExtend();

        try {
            //首先确认产品是否有权限刊登
            if (!Product::model()->checkCurrentUserAccessToSaleSKUNew($sku, $accountID, Platform::CODE_WISH)) {
                return $this->ReturnMessage(false, "{$sku}:" . Yii::t('system', 'Not Access to Add the SKU'));
            }
            //产品表查找sku信息
            $skuInfo = Product::model()->getProductInfoBySku($sku);
            if (empty($skuInfo)) {
                return $this->ReturnMessage(false, "sku:{$sku}不存在！");
            }
            $name = isset($info['name']) ? $info['name'] : '';

            //通过账号ID判断是那个部门的，进行添加标题前后缀，长沙部门标题加前缀，深圳部门标题加后缀
            $departmentInfo = $wishAccountModel->getAccountInfoById($accountID);
            if ($departmentInfo['department_id'] == WishAccount::DEPARTMENT_SHENZHEN) {
                $name = $name . ' #' . $departmentInfo['account_name'];
            } elseif ($departmentInfo['department_id'] == WishAccount::DEPARTMENT_CHANGSHA) {
                $name = '#' . $departmentInfo['account_name'] . ' ' . $name;
            }

            $description = '';
            $extendInfo = $wishListingExtendModel->getOneByCondition('description', 'listing_id = ' . $info['id']);
            if ($extendInfo) {
                $description = $extendInfo['description'];
            }

            //确定下是否已经在当前账号刊登过
            //1、已刊登成功在线不再刊登
            $checkExists = WishListing::model()->find("account_id=:account_id AND sku=:sku AND enabled=:enabled", array(':account_id' => $accountID, ':sku' => $sku, ':enabled' => 1));
            if ($checkExists) {
                return $this->ReturnMessage(false, "在产品表里已经上传过该SKU");
            }

            //2、已刊登下线的但在刊登记录里面未上传成功的等待上传的不再刊登
            $uploadStatus = array(WishProductAdd::WISH_UPLOAD_PENDING, WishProductAdd::WISH_UPLOAD_SUCCESS, WishProductAdd::WISH_UPLOAD_IMG_FAIL);
            $checkExists = WishProductAdd::model()->find("account_id=:account_id AND parent_sku=:sku AND upload_status in (" . MHelper::simplode($uploadStatus) . ")", array(":account_id" => $accountID, ":sku" => $sku));
            if ($checkExists) {
                return $this->ReturnMessage(false, "在待刊登列表里已经上传过该SKU");
            }

            //3、查询listing variants表
            $wishVariantsModel = new WishVariants();
            $conditions = 'listing_id = :listing_id';
            $params = array(':listing_id' => $info['id']);
            $variationAddList = $wishVariantsModel->getWishProductVarantList($conditions, $params);
            if (!$variationAddList) {
                return $this->ReturnMessage(false, "listing variants表中listing_id为" . $info['id'] . "没有找到");
            }

            //开始组装数据
            $config = ConfigFactory::getConfig('serverKeys');
            //sku图片加载
            $skuImg = array();
            $images = Product::model()->getImgList($sku, 'ft');
            $mainImg = "";
            $extraImg = "";
            foreach ($images as $k => $img) {
                $filename = basename($img);
                if ($filename == $sku . ".jpg") continue;
                if (empty($mainImg)) {
                    $mainImg = $config['oms']['host'] . $img;
                } else {
                    $skuImg[$k] = $config['oms']['host'] . $img;
                }
            }
            $extraImg = implode("|", $skuImg);
            //主表数据
            $encryptSku = new encryptSku();
            $productIsMulti = $skuInfo['product_is_multi'];
            $addData = array(
                'account_id' => $accountID,
                'online_sku' => $encryptSku->getEncryptSku($sku),
                'parent_sku' => $sku,
                'name' => $name,
                'description' => $description,
                'tags' => $info['tags'],
                'brand' => $info['brand'],
                'main_image' => $mainImg,
                'extra_images' => $extraImg,
                'product_is_multi' => $productIsMulti,
                'upload_status' => WishProductAdd::WISH_UPLOAD_PENDING,
                'create_user_id' => intval(Yii::app()->user->id),
                'update_user_id' => intval(Yii::app()->user->id),
                'create_time' => date("Y-m-d H:i:s"),
                'update_time' => date("Y-m-d H:i:s"),
                'add_type' => $addType
            );
            $dbTransaction = $wishProductAddModel->getDbConnection()->beginTransaction();
            try {
                $addID = $wishProductAddModel->saveRecord($addData);
                if (!$addID) {
                    return $this->ReturnMessage(false, '添加主表数据失败！');
                }
                foreach ($variationAddList as $variation) {
                    $onlineSKU = "";
                    if (count($variationAddList) == 1 && $variation['sku'] == $sku) {
                        $onlineSKU = $addData['online_sku'];
                    } else {
                        $onlineSKU = $encryptSku->getEncryptSku($variation['sku']);
                    }

                    //判断价格是否小于等于0
                    if ($variation['price'] <= 0) {
                        return $this->ReturnMessage(false, '产品价格不能小于等于0');
                    }

                    $variationData = array(
                        'add_id' => $addID,
                        'parent_sku' => $sku,
                        'sku' => $variation['sku'],
                        'online_sku' => $onlineSKU,
                        'inventory' => 1000,
                        'price' => $variation['price'],
                        'shipping' => $variation['shipping'],
                        'shipping_time' => $variation['shipping_time'],
                        'msrp' => ceil($variation['price'] / 0.65),
                        'color' => isset($variation['color']) ? $variation['color'] : '',
                        'size' => isset($variation['size']) ? $variation['size'] : '',
                        'main_image' => '',
                        'remote_main_img' => '',
                        'upload_status' => WishProductAdd::WISH_UPLOAD_PENDING,
                        'create_user_id' => intval(Yii::app()->user->id),
                        'update_user_id' => intval(Yii::app()->user->id),
                        'create_time' => date("Y-m-d H:i:s"),
                        'update_time' => date("Y-m-d H:i:s"),
                    );
                    $wishProductVariantsAddModel->getDbConnection()->createCommand()->insert($wishProductVariantsAddModel->tableName(), $variationData);
                }
                $dbTransaction->commit();
            } catch (Exception $e) {
                $dbTransaction->rollback();
                return $this->ReturnMessage(false, $e->getMessage());
            }
            return $this->ReturnMessage(true, '刊登成功');
        } catch (Exception $e) {
            return $this->ReturnMessage(false, $e->getMessage());
        }
    }


    /**
     * 返回的消息数组
     * @param bool $booleans 布尔值
     * @param string $message 提示的消息
     * @return array
     */
    public function ReturnMessage($booleans, $message)
    {
        return array($booleans, $message);
        exit;
    }


    private function preg_replace_call_func($match)
    {
        if (in_array(strtolower($match['TT']), array('</p>', '<p/>', '<br/>', '</br>', '<br />'))) {
            return "\n";
        } else {
            return '';
        }
    }


    /**
     * @desc 页面的跳转链接地址
     */
    public static function getIndexNavTabId()
    {
        return Menu::model()->getIdByUrl('/wish/wishlisting/list');
    }


    public function getListingsWithByVariationIds($variationIds)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName() . ' AS t')
            ->leftJoin(WishVariants::model()->tableName() . ' AS v', 't.id =v.listing_id')
            ->select('*');

        if (is_array($variationIds)) {
            $queryBuilder->where('v.id IN (' . MHelper::simplode($variationIds) . ')');
            return $queryBuilder->queryAll();
        } else {
            $queryBuilder->where('v.id =:id', array(':id' => $variationIds));
            return $queryBuilder->queryRow();
        }
    }

    public function getListingInfoByListingId($listingId)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName() . ' AS t')
            ->leftJoin(WishListingExtend::model()->tableName() . ' AS e', 't.id =e.listing_id')
            ->select('t.*, e.description');
        $queryBuilder->where('t.product_id =:listingId', array(':listingId' => $listingId));
        return $queryBuilder->queryRow();


    }

    public function searchListing($accountId = null, $sku = array(), $warehouseId = null, $limit = 1000)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->from($this->tableName() . ' p')
            ->join(WishVariants::model()->tableName() . ' v', "v.listing_id=p.id")
            // ->where('V.account_id=:accountId',
            //      array(':accountId' => $accountId))
            ->select('v.id, v.variation_product_id, p.id as pid, v.sku as sku, p.parent_sku, p.sku as psku, v.product_id, p.is_varation, p.product_id as listing_id, v.enabled, p.warehouse_id, p.account_id');
        $queryBuilder->where('v.enabled = 1');
        if ($accountId) {
            $queryBuilder->andWhere('v.account_id=:accountId', array(':accountId' => $accountId));
        }
        $sku = (array)$sku;
        if ($sku) {
            $queryBuilder->andWhere('p.sku IN (' . MHelper::simplode($sku) . ')');
        }

        if ($warehouseId) {
            $queryBuilder->andWhere('p.warehouse_id=:warehouseId', array(':warehouseId' => $warehouseId));
        }

        $queryBuilder->setLimit($limit);
        #echo $queryBuilder->getText();
        return $queryBuilder->queryAll();

    }


    public function getListingWithVariantsBySkuForAutoPrice(array $sku, $accountId = null, $warehouseId = null)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('p.id, p.account_id, p.product_id, p.name, p.parent_sku, p.sku, p.num_saves, p.enabled, p.num_sold, p.review_status, v.variation_product_id, v.online_sku, v.sku as sub_sku, v.color, v.size, v.inventory, v.price, v.shipping ')
            ->from($this->tableName() . ' AS p')
            ->join(WishVariants::model()->tableName() . ' AS v', 'p.id = v.listing_id')
            ->where('is_promoted = 0')
            ->andWhere('v.enabled = 1');
        if ($accountId !== null) {
            $queryBuilder->andWhere('account_id = :accountId', array(':accountId' => $accountId));
        }
        if ($warehouseId !== null) {
            $queryBuilder->andWhere('warehouse_id =:warehouseId', array(':warehouseId' => $warehouseId));
        }
        $queryBuilder->andWhere(array('in', 'v.sku', $sku));

        return $queryBuilder->queryAll();

    }
}