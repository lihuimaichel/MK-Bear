<?php

/**
 * @desc Joom订单拉取
 * @author Gordon
 * @since 2015-06-22
 */
class JoomListing extends JoomModel
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
     * @desc joom 产品审核状态
     * @var unknown
     */
    const REVIEW_STATUS_APPROVED = 'approved';//通过
    const REVIEW_STATUS_REJECTED = 'rejected';//不通过
    const REVIEW_STATUS_PENDING = 'pending';//等待

    public $detail;
    public $sale_property;
    public $status_text;
    public $sku;
    public $review_status_text;
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
    public static $joomAccountPairs;
    public $image;
    public $accountName;

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
        return 'ueb_joom_listing';
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
    public function getAccountListings($params = array())
    {
        if (isset($params['index']) && !empty($params['index'])) {
            $index = (int)$params['index'];
        } else {
            $index = 0;
        }
        if (isset($params['limit']) && !empty($params['limit'])) {
            $limit = (int)$params['limit'];
        } else {
            $limit = 100;  //接口最大支持500，默认50
        }
        $hasflish = false;

        while (!$hasflish) {
            $request = new ListAllProductsRequest();
            $request->setAccount($this->_accountID);
            $request->setStartIndex($index);
            $request->setLimit($limit);
            $response = $request->setRequest()->sendRequest()->getResponse();
            // MHelper::writefilelog('joom/joomlisting/'.date("Ymd").'/'.$accountID.'/response_'.$accountID.'_'.($index-1).'.log',print_r($response,true)."\r\n");
            if ($request->getIfSuccess() && !empty($response)) {
                $datas = $response->data;
                $flag = $this->saveJoomListing($datas);
                if (!isset($response->paging->next) || empty($response->paging->next)) {
                    $hasflish = true;
                }
                if (!$flag)
                    return false;
            } else {
                $hasflish = true;
            }
            $index++;
        }
        return true;
    }

    /**
     * @desc 根据账号获取listing数据
     * @param unknown $datas
     */
    function getAccountListing($accountID)
    {
        $hasflish = false;
        $limit = 500;
        $index = 0;
        $total = 0;
        //$request->setLimit(30);
        $flag = true;
        while (!$hasflish) {
            $request = new ListAllProductsRequest();
            $request->setAccount($accountID);
            $request->setLimit($limit);
            $request->setStartIndex($index);
            $index++;
            $response = $request->setRequest()->sendRequest()->getResponse();
            if ($request->getIfSuccess() && !empty($response) && !empty($response->data)) {
                $datas = $response->data;
                $joomListing = new JoomListing();
                $joomListing->setAccountID($accountID);
                $flag = $joomListing->saveJoomListing($datas);
                $total += count($datas);
                unset($datas);
                if (!isset($response->paging->next) || empty($response->paging->next)) {
                    $hasflish = true;
                }
            } else {
                $hasflish = true;
                if ($request->getIfSuccess() && !empty($response) && empty($response->data)) {
                    //没有数据了
                } else {
                    $this->setExceptionMessage($request->getErrorMsg());
                    return false;
                }
            }
            unset($response);
            if (!$flag) {
                echo $joomListing->getExceptionMessage();
                $this->setExceptionMessage($joomListing->getExceptionMessage());
                return false;
            }
        }
        echo "pull num:{$total} ", "AccountId:{$accountID} finish";
        return true;
    }


    /**
     * @desc 保存listing数据
     * @param unknown $datas
     */
    public function saveJoomListing($datas)
    {
        $encryptSku = new encryptSku();
        foreach ($datas as $data) {
            /* $dbTransaction = $this->getDbConnection()->getCurrentTransaction();
            if (empty($dbTransaction))
                $dbTransaction = $this->getDbConnection()->beginTransaction(); */
            try {
                $productData = $data->Product;
                //检查产品是否已经存在
                $checkExists = $this->getDbConnection()->createCommand()->from(self::model()->tableName())->select('id')->where("product_id = :id", array(':id' => $data->Product->id))->queryRow();


                $params = array(
                    'account_id' => $this->_accountID,
                    'product_id' => $productData->id,
                    'name' => $productData->name,
                    'num_sold' => isset($productData->number_sold) ? $productData->number_sold : '',
                    'num_saves' => isset($productData->number_saves) ? $productData->number_saves : '',
                    'review_status' => isset($productData->review_status) ? $productData->review_status : '',
                    'brand' => isset($productData->brand) ? $productData->brand : '',
                    'landing_page_url' => isset($productData->landing_page_url) ? $productData->landing_page_url : '',
                    'upc' => isset($productData->upc) ? $productData->upc : '',
                    'main_image' => $productData->main_image,
                    'extra_images' => isset($productData->extra_images) ? $productData->extra_images : '',
                    'enabled' => 0,
                    'modify_time' => date("Y-m-d H:i:s", time()),
                    'is_promoted' => (isset($productData->is_promoted) ? $productData->is_promoted : '') == 'True' ? 1 : 0
                );
                if ($productData->date_uploaded) {
                    $uploadDateTime = new \DateTime($productData->date_uploaded);
                    $params['date_uploaded'] = $uploadDateTime->format('Y-m-d H:i:s');
                }
                $variants = $productData->variants;
                $params['is_varation'] = count($variants) > 1 ? 1 : 0;//是否多属性
                $onlineSku = isset($productData->parent_sku) ? $productData->parent_sku : (isset($variants[0]) ? $variants[0]->Variant->sku : '');
                $sku = $encryptSku->getRealSku($onlineSku);
                if (empty($sku))
                    $sku = $onlineSku;
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
                if ($checkExists) {
                    //获取对应的主键id
                    $listingID = $checkExists['id'];
                    $flag = $this->getDbConnection()->createCommand()->update(self::model()->tableName(), $params, "id=:id", array(':id' => $listingID));
                    if (!$flag)
                        throw new Exception(Yii::t('joom', 'Update Product Info Failure'));

                } else {
                    $flag = $this->getDbConnection()->createCommand()->insert(self::model()->tableName(), $params);
                    if (!$flag)
                        throw new Exception(Yii::t('joom', 'Save Product Info Failure'));
                    $listingID = $this->getDbConnection()->getLastInsertID();
                    $flag = $this->getDbConnection()->createCommand()->insert('ueb_joom_listing_extend', array(
                        'listing_id' => $listingID,
                        'description' => $productData->description
                    ));
                    if (!$flag)
                        throw new Exception(Yii::t('joom', 'Save Product Extend Failure'));
                }
                foreach ($variants as $variant) {
                    $variantParams = array();
                    $variantParams['listing_id'] = $listingID;
                    $variantParams['account_id'] = $this->_accountID;
                    $variantOnlineSku = $variant->Variant->sku;
                    $variantSku = $encryptSku->getRealSku($variantOnlineSku);
                    if (empty($variantSku))
                        $variantSku = $variantOnlineSku;
                    $ID = $variantParams['variation_product_id'] = $variant->Variant->id;
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
                    $variantParams['msrp'] = isset($variant->Variant->msrp) ? $variant->Variant->msrp : 0;
                    if (isset($variant->Variant->shipping_time))
                        $variantParams['shipping_time'] = $variant->Variant->shipping_time;
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
                        if (!$flag)
                            throw new Exception(Yii::t('joom', 'Update Product Variation Failure'));
                    } else {
                        $flag = $this->getDbConnection()->createCommand()->insert('ueb_listing_variants', $variantParams);
                        if (!$flag)
                            throw new Exception(Yii::t('joom', 'Save Product Variation Failure'));
                    }
                }
                //	$dbTransaction->commit();
            } catch (Exception $e) {
                //$dbTransaction->rollback();
                $this->setExceptionMessage($e->getMessage());
                return false;
            }
        }
        return true;
    }

    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message)
    {
        $this->exception .= $message;
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
            ->from(JoomVariants::tableName() . ' V')
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
    public function disabledJoomProductByOnlineSku($skus, $accountID)
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
                    array('enabled' => JoomVariants::JOOM_PRODUCT_DISABLED),
                    array('IN', 'id', $listingIds));

            $this->getDbConnection()->createCommand()
                ->update(JoomVariants::tableName(),
                    array('enabled' => JoomVariants::JOOM_PRODUCT_DISABLED),
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

    public function search($model = null, $sort = array(), $with = array(), $CDbCriteria = null)
    {
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'jv.modify_time',
            'defaultOrderDirection'=> 'DESC'
        );


        $criteria = new CDbCriteria();
        $criteria->select = 't.id, t.account_id, t.name, t.parent_sku, t.sku, t.num_sold, t.num_saves, t.review_status, t.main_image, t.enabled,
        t.is_promoted, t.modify_time, t.is_varation, t.date_uploaded, jv.variation_product_id, 
        jv.price, jv.inventory, jv.enabled as sub_enabled, 
        jlp.profit, jlp.profit_rate';
        $criteria->join = 'JOIN '.JoomVariants::model()->tableName(). ' AS jv ON (t.id = jv.listing_id) 
 
        LEFT JOIN ' . JoomListingProfit::model()->tableName(). ' AS jlp ON (jv.variation_product_id = jlp.listing_id)';
        $criteria->group = 't.id';
        $dataProvider = parent::search($this, $sort, $with, $criteria);


        $resultData = $dataProvider->data;
        $additionalData = array();
        $keyIdMapper = array();
        $variationParentIds = array();
        $accountList = JoomAccount::model()->getIdNamePairs();
        $indexer = 1;
        foreach ($resultData as $key => $listingObject) {
            $listingObject->account_name = isset($accountList[$listingObject->account_id]) ?
            $accountList[$listingObject->account_id] : '-';
            $listingObject->image = $this->renderListingImageHtml($listingObject);
            //$listingObject->status =  Yii::t('shopee', $statusMapper[$listingObject->status]);
            $listingObject->name = $this->renderListingTitleHtml($listingObject);
            $listingObject->num_sold_total = $listingObject->num_sold;
            $listingObject->num_saves_total =  $listingObject->num_saves;;
            $listingObject->review_status_text = $this->getJoomProductReviewStatusText($listingObject->review_status);


            //$datas[$key]->account_name = isset(self::$joomAccountPairs[$datas[$key]->account_id]) ?

            $additionalData[$key] = $listingObject;
            $keyIdMapper[$listingObject->id] = $key;

            $variationParentIds[] = $listingObject->id;

        }

        if ($variationParentIds) {
            $condition = array(
                'IN',
                'pv.listing_id',
                $variationParentIds
            );
            $variationsSet = JoomVariants::model()->getJoomProductVarantListWithProfit($condition);

            foreach ($variationsSet as $parentId => $variant) {


                $key = $keyIdMapper[$variant['listing_id']];

                $variant['staus_text'] = $this->getJoomProductVariantStatusText($variant['enabled']);
                $variant['sale_property'] = $this->getJoomProductVariantSalePropertyText($variant['color'], $variant['size']);
                $variant['variants_id'] = $variant['id'];
                $variant['oprator'] = $this->getJoomProductVariantOprator( $additionalData[$key]->review_status,
                    $variant['enabled'], $variant['id']);
                $variant['account_name'] = isset(self::$joomAccountPairs[$variant['account_id']]) ? self::$joomAccountPairs[$variant['account_id']] : '';
                $variant['subsku'] = $variant['sku'];
                $variant['profit_rate'] = $variant['profit_rate'];
                $variant['price'] = $this->renderListingPriceHtml($variant);
                $variant['inventory'] = $this->renderListingStockHtml($variant);



                $additionalData[$key]->detail[] = $variant;

            }
        }
        $dataProvider->data = $additionalData;
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
        $cdbcriteria->select = 't.id,t.sku,t.account_id';
        //$cdbcriteria->group = 't.sku';

        $condition = array();
        $params = array();

        $account_id = '';
        $accountIdArr = array();
        if (isset(Yii::app()->user->id)) {
            $accountIdArr = JoomAccountSeller::model()->getListByCondition('account_id', 'seller_user_id = ' . Yii::app()->user->id);
        }

        if (isset($_REQUEST['account_id']) && !empty($_REQUEST['account_id'])) {
            $account_id = (int)$_REQUEST['account_id'];
        }

        if ($accountIdArr && !in_array($account_id, $accountIdArr)) {
            $account_id = implode(',', $accountIdArr);
        }

        if ($account_id) {
            $cdbcriteria->addCondition("t.account_id IN(" . $account_id . ")");
        }

        if (isset($_REQUEST['enabled']) && $_REQUEST['enabled']) {
            $enabled = (int)$_REQUEST['enabled'];
            if ($enabled == JoomVariants::JOOM_PRODUCT_DISABLED_MAPPING)
                $enabled = JoomVariants::JOOM_PRODUCT_DISABLED;
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
        if ($condition && isset($params[':online_sku'])) {
            $conditions = implode(" AND ", $condition);
            $variantList = $this->getDbConnection()->createCommand()->from(self::model('JoomVariants')->tableName())
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
        set_time_limit(3600);
        if (empty($datas)) return $datas;
        foreach ($datas as $key => $data) {
            //获取当前父级SKU所拥有的变种产品列表
            //notice: 此处代码编程方式是原有模式下的代码，后由业务方改变而致，为保持可能回滚的业务需求，暂不完全修改
            $condition = array('sku=:sku', 'account_id=:account_id', 'id=:id');
            $params = array(':sku' => $data['sku'], ':account_id' => $data['account_id'], ':id' => $data['id']);
            if (isset($_REQUEST['enabled']) && $_REQUEST['enabled']) {
                $condition[] = 'enabled=:enabled';
                $enabled = (int)$_REQUEST['enabled'];
                if ($enabled == JoomVariants::JOOM_PRODUCT_DISABLED_MAPPING)
                    $enabled = JoomVariants::JOOM_PRODUCT_DISABLED;
                $params[':enabled'] = $enabled;
            }
            if (isset($_REQUEST['account_id']) && $_REQUEST['account_id']) {
                $condition[] = "account_id = '" . (int)$_REQUEST['account_id'] . "'";
            }
            if (isset($_REQUEST['parent_sku']) && $_REQUEST['parent_sku']) {
                //$condition[] = "parent_sku = '".addslashes($_REQUEST['parent_sku'])."'";
                $condition[] = "parent_sku like '%" . addslashes($_REQUEST['parent_sku']) . "%'";
            }
            if ($condition)
                $conditions = implode(' AND ', $condition);
            $listings = $this->getDbConnection()->createCommand()->from(self::tableName())
                ->select('id, product_id, parent_sku, sku, name, account_id,num_sold,num_saves,	review_status, enabled, main_image, date_uploaded')
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
            $datas[$key]->name = $this->renderListingTitleHtml($parentInfo);
            $datas[$key]->date_uploaded = $parentInfo['date_uploaded'];
            $datas[$key]->account_id = $parentInfo['account_id'];
            $datas[$key]->num_sold_total = $num_sold_total;
            $datas[$key]->num_saves_total = $num_saves_total;
            $datas[$key]->image = $this->renderListingImageHtml($parentInfo);
            $datas[$key]->review_status = $parentInfo['review_status'];
            $datas[$key]->enabled = $parentInfo['enabled'];
            $datas[$key]->account_name = isset(self::$joomAccountPairs[$datas[$key]->account_id]) ? self::$joomAccountPairs[$datas[$key]->account_id] : '';
            //获取审核状态
            $datas[$key]->review_status_text = $this->getJoomProductReviewStatusText($data['review_status']);

            $variants = $this->filterJoomProductVarantListByListingIds($newListingIds);
            if (empty($variants)) {
                $variant = array(
                    'staus_text' => $this->getJoomProductVariantStatusText($parentInfo['enabled']),
                    'sale_property' => '',
                    'variants_id' => 0,
                    'enabled' => $parentInfo['enabled'],
                    'oprator' => '',
                    'online_sku' => $parentInfo['parent_sku'],
                    'subsku' => $parentInfo['sku'],
                    'msrp' => '', 'shipping' => '', 'price' => '',
                    'inventory' => '',
                    'account_name' => isset(self::$joomAccountPairs[$datas[$key]->account_id]) ? self::$joomAccountPairs[$datas[$key]->account_id] : ''
                );

                $datas[$key]->detail[] = $variant;
                continue;
            }
            $datas[$key]->detail = array();
            foreach ($variants as $variant) {


                $variant['staus_text'] = $this->getJoomProductVariantStatusText($variant['enabled']);
                $variant['sale_property'] = $this->getJoomProductVariantSalePropertyText($variant['color'], $variant['size']);
                $variant['variants_id'] = $variant['id'];
                $variant['oprator'] = $this->getJoomProductVariantOprator($data['review_status'], $variant['enabled'], $variant['id']);
                $variant['account_name'] = isset(self::$joomAccountPairs[$variant['account_id']]) ? self::$joomAccountPairs[$variant['account_id']] : '';
                $variant['subsku'] = $variant['sku'];
                $variant['profit_rate'] = $variant['profit_rate'];

                $variant['price'] = $this->renderListingPriceHtml($variant);
                $variant['inventory'] = $this->renderListingStockHtml($variant);


                //$priceCal->profitRate * 100 .'%';
                $datas[$key]->detail[] = $variant;
            }
        }
        return $datas;
    }

    public function renderListingTitleHtml($listingObject)
    {
        return '<a href="'.Yii::app()->createUrl('joom/joomlisting/updateProductTitleForm', array('id'=>
                $listingObject->id)).'" 
        target="dialog" mask="true">'
        .$listingObject->name
        .'</a>';
    }


    public function renderListingImageHtml($parentInfo)
    {
        return '<img width=100 src="' . $parentInfo['main_image'] . '">';
    }

    public function renderListingPriceHtml($variant)
    {
        return $variant['price'] . '<a class="btnEdit" width="800" height="350" target="dialog" href="'.Yii::app()
                ->createUrl('joom/joomlisting/batchUpdateForm/', array(
            'id'=> $variant['id'],
                'action'=> 'updatePrice'
            )).'"></a>';
    }

    public function renderListingStockHtml($variant)
    {
        return $variant['inventory'] . '<a class="btnEdit" width="800" height="350" target="dialog" href="'.Yii::app()
                ->createUrl('joom/joomlisting/batchUpdateForm/', array(
                    'id'=> $variant['id'],
                    'action'=> 'updateStock'
                )).'"></a>';


    }

    /**
     * @desc 获取joom产品审核状态文本
     */
    public function getJoomProductReviewStatusText($reviewStatus)
    {
        $str = '';
        $color = 'red';
        switch ($reviewStatus) {
            case self::REVIEW_STATUS_REJECTED:
                $str = Yii::t('joom_listing', 'Review Rejected Status');
                break;
            case self::REVIEW_STATUS_PENDING:
                $str = Yii::t('joom_listing', 'Review Pending Status');
                break;
            case self::REVIEW_STATUS_APPROVED:
                $str = Yii::t('joom_listing', 'Review Approved Status');
                $color = 'green';
                break;
        }
        return '<font color="' . $color . '">' . $str . '</font>';
    }

    /**
     * @desc 获取产品变种列表
     * @param unknown $productId
     */
    public function filterJoomProductVarantListByProductId($productId)
    {
        $condition = array();
        $params = array();
        if (isset($_REQUEST['enabled']) && $_REQUEST['enabled']) {
            $condition[] = 'enabled=:enabled';
            $enabled = (int)$_REQUEST['enabled'];
            if ($enabled == JoomVariants::JOOM_PRODUCT_DISABLED_MAPPING)
                $enabled = JoomVariants::JOOM_PRODUCT_DISABLED;
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
        return self::model('JoomVariants')->getJoomProductVarantListByProductId($productId, $conditions, $params);
    }

    /**
     * @desc 根据listingId获取子sku
     * @param unknown $listingIds
     * @return multitype:|Ambigous <multitype:, mixed, NULL, unknown, multitype:unknown Ambigous <unknown, NULL> , CActiveRecord, multitype:unknown Ambigous <CActiveRecord, NULL> , multitype:unknown >
     */
    public function filterJoomProductVarantListByListingIds($listingIds)
    {
        if (empty($listingIds)) return array();
        $condition = array();
        $params = array();
        $conditions = "pv.listing_id IN('" . implode("','", $listingIds) . "') ";
        if (isset($_REQUEST['enabled']) && $_REQUEST['enabled']) {
            $condition[] = 'pv.enabled=:enabled';
            $enabled = (int)$_REQUEST['enabled'];
            if ($enabled == JoomVariants::JOOM_PRODUCT_DISABLED_MAPPING)
                $enabled = JoomVariants::JOOM_PRODUCT_DISABLED;
            $params[':enabled'] = $enabled;
        }
        if (isset($_REQUEST['account_id']) && $_REQUEST['account_id']) {
            $condition[] = "pv.account_id = '" . (int)$_REQUEST['account_id'] . "'";
        }

        if (isset($_REQUEST['online_sku']) && $_REQUEST['online_sku']) {
            $condition[] = "pv.online_sku LIKE '" . addslashes($_REQUEST['online_sku']) . "%'";
        }


        if ($condition)
            $conditions .= " AND " . implode(' AND ', $condition);
        return self::model('JoomVariants')->getJoomProductVarantListWithProfit($conditions, $params);
    }

    /**
     * @desc 获取操作文案
     * @param unknown $status
     * @param unknown $variantId
     * @return string
     */
    public function getJoomProductVariantOprator($reviewStatus, $status, $variantId)
    {

        $str = "<select style='width:75px;' onchange = 'offLine(this," . $variantId . ")' >
				<option>" . Yii::t('system', 'Please Select') . "</option>";
        if ($status == JoomVariants::JOOM_PRODUCT_ENABLED) {
            $str .= '<option value="offline">' . Yii::t('joom_listing', 'Product Disabled') . '</option>';
        }
        $str .= "</select>";
        return $str;
    }

    /**
     * @desc 获取产品变种状态文案
     * @param unknown $enabled
     * @return string
     */
    public function getJoomProductVariantStatusText($enabled)
    {
        $statusText = '';
        $color = 'red';
        switch ($enabled) {
            case JoomVariants::JOOM_PRODUCT_ENABLED:
                $color = 'green';
                $statusText = Yii::t('joom_listing', 'Product Enabled');
                break;
            case JoomVariants::JOOM_PRODUCT_DISABLED:
                $statusText = Yii::t('joom_listing', 'Product Disabled');
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
    public function getJoomProductVariantSalePropertyText($color, $size)
    {
        $saleProperty = '';
        if ($color)
            $saleProperty .= Yii::t('joom_listing', 'Color') . ':' . $color;
        if ($size)
            $saleProperty .= " <br>" . Yii::t('joom_listing', 'Size') . ':' . $size;
        return $saleProperty;
    }

    /**
     * @desc 设置搜索栏内容
     * @return multitype:multitype:string multitype:string   multitype:string NULL
     */
    public function filterOptions()
    {
        return array(
            array(
                'name' => 'sku',
                'type' => 'text',
                'alias' => 't',
                'search' => '=',
                'htmlOption' => array(
                   // 'size' => '22',
                )
            ),
            array(
                'name' => 'online_sku',
                'type' => 'text',
                'alias' => 'jv',
                'search' => '=',
             //   'rel' => true,
                'htmlOption' => array(
                  //  'size' => '22',
                    //'style' => 'width:260px'
                )
            ),
            array(
                'name' => 'parent_sku',
                'type' => 'text',
                'alias' => 't',
                'search' => '=',
                'htmlOption' => array(
                   // 'size' => '22'
                )
            ),
            array(
                'name' => 'review_status',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => $this->getJoomReviewStatusOptions()
            ),
            array(
                'name' => 'account_id',
                'type' => 'dropDownList',
                'alias' => 't',
                'search' => '=',
                'data' => $this->getJoomAccountList()
            ),
            array(
                'name' => 'enabled',
                'type' => 'dropDownList',
                'alias' => 'jv',
                'search' => '=',
              // 'rel' => true,
                'data' => $this->getJoomProductStatusOptions()
            ),
            array(
                'name' => 'profit_rate',
                'type' => 'text',
                'alias' => 'jlp',
                'search' => 'RANGE',


            ),
        );
    }

    /**
     * @desc  获取公司账号
     */
    public function getJoomAccountList()
    {
        if (self::$joomAccountPairs == null) {
            $user_id = isset(Yii::app()->user->id) ? Yii::app()->user->id : 0;
            $idArr = JoomAccountSeller::model()->getListByCondition('account_id', 'seller_user_id = ' . $user_id);
            if ($idArr) {
                self::$joomAccountPairs = self::model('JoomAccount')->getAvailableIdNamePairs($idArr);
            } else {
                self::$joomAccountPairs = self::model('JoomAccount')->getIdNamePairs();
            }
        }
        return self::$joomAccountPairs;
    }

    /**
     * @desc 获取产品状态选线
     * @return multitype:NULL Ambigous <string, string, unknown>
     */
    public function getJoomProductStatusOptions()
    {
        return array(
            JoomVariants::JOOM_PRODUCT_ENABLED => Yii::t('joom_listing', 'Product Enabled'),
            JoomVariants::JOOM_PRODUCT_DISABLED => Yii::t('joom_listing', 'Product Disabled')
        );
    }

    /**
     * @desc 获取产品审核状态选项
     * @return multitype:NULL Ambigous <string, string, unknown>
     */
    public function getJoomReviewStatusOptions()
    {
        return array(
            self::REVIEW_STATUS_REJECTED => Yii::t('joom_listing', 'Review Rejected Status'),
            self::REVIEW_STATUS_PENDING => Yii::t('joom_listing', 'Review Pending Status'),
            self::REVIEW_STATUS_APPROVED => Yii::t('joom_listing', 'Review Approved Status')
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
            'sku' => Yii::t('joom_listing', 'Sku'),
            'subsku' => Yii::t('joom_listing', 'Sub sku'),
            'enabled' => Yii::t('joom_listing', 'Status Text'),
            'parent_sku' => Yii::t('joom_listing', 'Parent Sku'),
            'name' => Yii::t('joom_listing', 'Product Name'),
            'review_status_text' => Yii::t('joom_listing', 'Product Review Status'),
            'review_status' => Yii::t('joom_listing', 'Product Review Status'),
            'online_sku' => Yii::t('joom_listing', 'Online Sku'),
            'sale_property' => Yii::t('joom_listing', 'Sale Property'),
            'inventory' => Yii::t('joom_listing', 'Inventory'),
            'price' => Yii::t('joom_listing', 'Price'),
            'shipping' => Yii::t('joom_listing', 'Shipping'),
            'msrp' => Yii::t('joom_listing', 'Market Recommand Price'),
            'oprator' => Yii::t('system', 'Oprator'),
            'staus_text' => Yii::t('joom_listing', 'Status Text'),
            'account_id' => Yii::t('joom_listing', 'Account Name'),
            'account_name' => Yii::t('joom_listing', 'Account Name'),
            'num_sold' => Yii::t('joom_listing', 'Num Sold'),
            'num_saves' => Yii::t('joom_listing', 'Num Saves'),
            'profit' => Yii::t('joom', 'Profit'),
            'profit_rate' => Yii::t('joom', 'Profit Rate'),
            'date_uploaded' => Yii::t('joom', 'Date uploaded')
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
                ->from(JoomVariants::tableName() . ' V')
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
     * @desc 根据条件获取多条数据
     * @param unknown $fields
     * @param unknown $conditions
     * @param string $param
     * @return mixed
     */
    public function getListByCondition($fields, $conditions)
    {
        return $this->getDbConnection()->createCommand()
            ->select($fields)
            ->from($this->tableName())
            ->where($conditions)
            ->queryAll();
    }

    /**
     * @desc 根据自增ID更新数据
     * @param int $id
     * @param array $updata
     * @return boolean
     */
    public function updateInfoByID($id, $updata)
    {
        if (empty($id) || empty($updata)) return false;
        $conditions = "id = " . $id;
        return $this->getDbConnection()->createCommand()->update($this->tableName(), $updata, $conditions);
    }


    public function getListingWithVariants($limit = 1000, $offset = 0, $groupBy = null)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('p.id, p.account_id, p.product_id, p.name,p.tags, p.parent_sku, p.sku, p.num_saves, p.enabled, p.num_sold, p.review_status, v.variation_product_id, v.online_sku, v.sku as sub_sku, v.color, v.size, v.inventory, v.price, v.shipping ')
            ->from($this->tableName() . ' AS p')
            ->join(JoomVariants::model()->tableName() . ' AS v', 'p.id = v.listing_id')->limit($limit)->offset($offset);

        if ($groupBy) {
            $queryBuilder->group($groupBy);
        }
        #echo $queryBuilder->getText();
        return $queryBuilder->queryAll();
    }


    public function getListingWithVariantsBySkuForAutoPrice(array $sku)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('p.id, p.account_id, p.product_id, p.name, p.parent_sku, p.sku, p.num_saves, p.enabled, p.num_sold, p.review_status, v.variation_product_id, v.online_sku, v.sku as sub_sku, v.color, v.size, v.inventory, v.price, v.shipping ')
            ->from($this->tableName() . ' AS p')
            ->join(JoomVariants::model()->tableName() . ' AS v', 'p.id = v.listing_id')
            ->where('v.enabled = 1')
            ->andWhere(array('in', 'v.sku', $sku));
            return $queryBuilder->queryAll();

    }


    public function getListingWithVariantsBySku($sku)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()
            ->select('p.id, p.account_id, p.product_id, p.name, p.parent_sku, p.sku, p.num_saves, p.enabled, p.num_sold, p.review_status, v.variation_product_id, v.online_sku, v.sku as sub_sku, v.color, v.size, v.inventory, v.price, v.shipping ')
            ->from($this->tableName() . ' AS p')
            ->join(JoomVariants::model()->tableName() . ' AS v', 'p.id = v.listing_id');
        if (is_array($sku)) {
            $queryBuilder->where(array('in', 'v.sku', $sku));
            return $queryBuilder->queryAll();
        } else {
            $queryBuilder->where('v.sku=:sku', array(':sku' => $sku));
            return $queryBuilder->queryRow();
        }
    }


    public function updateOnlineListingData($listingId, $accountId, $listingData)
    {
        $request = new JoomUpdateProductRequest();
        $request
            ->setAccount($accountId)
            ->setListingId($listingId)
            ->setListingData($listingData);
        $request->setRequest()->sendRequest()->getResponse();

        if (!$request->getIfSuccess()) {
            throw new \Exception($request->getErrorMsg());
        }

        $dateTime = new \DateTime();

        $this->getDbConnection()->createCommand()->update(
            $this->tableName(),
            array_merge($listingData, array('modify_time'=> $dateTime->format('Y-m-d H:i:s'))),
                'product_id = :listingId AND account_id = :accountId',
            array(':listingId'=> $listingId, ':accountId'=> $accountId)
        );


    }
}