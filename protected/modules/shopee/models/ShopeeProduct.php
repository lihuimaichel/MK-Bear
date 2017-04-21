<?php

/**
 * Created by PhpStorm.
 * User: laigc
 * Date: 2017/3/8
 * Time: 15:24
 */
class ShopeeProduct extends ShopeeModel
{
    const LISTING_STATUS_NORMAL = 1;
    const LISTING_STATUS_DELETED = 2;
    const LISTING_STATUS_BANNED = 3;

    const SHOPEE_LISTING_STATUS_NORMAL = 'NORMAL';
    const SHOPEE_LISTING_STATUS_DELETED = 'DELETED';
    const SHOPEE_LISTING_STATUS_BANNED = 'BANNED';

    private $statusMapper = array(
        self::SHOPEE_LISTING_STATUS_NORMAL => self::LISTING_STATUS_NORMAL,
        self::SHOPEE_LISTING_STATUS_DELETED => self::LISTING_STATUS_DELETED,
        self::SHOPEE_LISTING_STATUS_BANNED => self::LISTING_STATUS_BANNED,
    );

    /*****************************/
    /**
     * @var GridView 使用属性
     */
    public $detail;
    public $accountName;
    public $name;
    public $mainImageUrl;
    public $sub_sku;
    /******************************/


    protected $cachedAccountList = array();

    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return 'ueb_shopee_product';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function getStatusMapper($key = null, $flip = false)
    {
        $statusMapper = $this->statusMapper;
        if ($flip) {
            $statusMapper = array_flip($statusMapper);
        }
        if ($key && isset($this->statusMapper[$key])) {
            return $this->statusMapper[$key];
        }
        return $statusMapper;
    }

    public function chunk($limit = 200, $offset = 0)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()->select('
        p.id, p.sku, p.account_id, p.site_code, p.listing_id, p.name, p.seller_sku, p.main_image_url, p.status,
          p.has_variation,
        v.id as vid, v.variation_id, v.variation_option_name, v.variation_sku, v.sku, v.status, v.stock
        ')
            ->from($this->tableName() . ' AS p ')
            ->join(ShopeeProductVariation::model()->tableName(). ' AS v', 'p.id = v.parent_id')
            ->limit($limit, $offset)
            ->order('p.id ASC');

        return $queryBuilder->queryAll();
    }

    /**
     * @param $item
     * @param $accountId
     * @desc 保存listing信息入库
     */
    public function saveItemInfo($accountId, $item)
    {
        if (isset($this->cachedAccountList[$accountId])) {
            $account = $this->cachedAccountList[$accountId];
        } else {
            $account = ShopeeAccount::model()->getAccountInfoById($accountId);
            $this->cachedAccountList[$accountId] = $account;
        }


        $dateTime = new \DateTime();
        $data = array(
            'account_id' => $accountId,
            'listing_id' => $item->item_id,
            'name' => $item->name,
            //'description' => $item->description,
            'site_code' => $account['site'],
            'seller_sku' => $item->item_sku,
            'sku' => $item->item_sku,
            'status' => $this->statusMapper[$item->status],
            'currency' => $item->currency,
            'price' => $item->price,
            'has_variation' => $item->has_variation ? 1 : 0,
            'landing_page_url' => '',
            // 'stock_status'=> $item->stock <=0 ? 0:1,
            'created_at' => $dateTime->setTimestamp($item->create_time)->format("Y-m-d H:i:s"),
            'updated_at' => $dateTime->setTimestamp($item->update_time)->format("Y-m-d H:i:s"),
            'main_image_url' => $item->images ? array_shift($item->images) : ''
        );

        $listingInfo = $this->getListingInfoByItemId($item->item_id, $accountId);
        if ($listingInfo) {
            $success = $this->getDbConnection()->createCommand()->update($this->tableName(), $data, 'id=:id', array(':id' => $listingInfo['id']));
        } else {
            $success = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
        }

        if (!$success) {
            return null;
        }

        if ($listingInfo) {
            $parentId = $listingInfo['id'];
        } else {
            $parentId = $this->getDbConnection()->getLastInsertID();
        }

        ShopeeProductAdditional::model()->saveItemAdditionalInfo($parentId, $item);

        if ($item->has_variation) {
            ShopeeProductVariation::model()->saveVariationInfo($parentId, $item->variations);
        } else {
            /**
             * 冗余一份到variation....
             */
            $variantStatus = ShopeeProductVariation::model()->getStatusMapper($this->statusMapper[$item->status], true);
            $parentVariantObj = new \stdClass();
            $parentVariantObj->variation_id = $item->item_id;
            $parentVariantObj->name = '';
            $parentVariantObj->status = $variantStatus ? $variantStatus : ShopeeProductVariation::SHOPEE_LISTING_VARIATION_STATUS_DELETED;
            $parentVariantObj->variation_sku = $item->item_sku;
            $parentVariantObj->sku = $item->item_sku;
            $parentVariantObj->price = $item->price;
            $parentVariantObj->stock = $item->stock;
            $parentVariantObj->create_time = $item->create_time;
            $parentVariantObj->update_time = $item->update_time;

            ShopeeProductVariation::model()->saveVariationInfo($parentId, array($parentVariantObj));
        }
        return $parentId;
    }


    public function getListingInfoByItemId($listingId, $accountId = null)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()->select('*')
            ->from($this->tableName())
            ->where('listing_id=:listingId', array(':listingId' => $listingId));
        if (null != $accountId) {
            $queryBuilder->andWhere('account_id =:accountId', array(':accountId' => $accountId));
        }

        return $queryBuilder->queryRow();
    }


    public function getListingsBySku($sku, $accountId = null)
    {
        $queryBuilder = $this->getDbConnection()->createCommand()->select('
        p.id, p.sku, p.account_id, p.site_code, p.listing_id, p.name, p.seller_sku, p.main_image_url, p.status,
        v.id as vid, v.variation_id, v.variation_option_name, v.variation_sku, v.sku as sub_sku, v.status as sub_status
        ')
            ->from($this->tableName() . ' AS p ')
            ->join(ShopeeProductVariation::model()->tableName(). ' AS v', 'p.id = v.parent_id');
        $returnRow = true;
        if (is_array($sku)) {
            $returnRow = false;
            $queryBuilder->where("p.sku IN (". MHelper::simplode($sku). ")");
        } else {
            $queryBuilder->where('p.sku=:sku', array(':sku' => $sku));
        }

        if (null != $accountId) {
            $queryBuilder->andWhere('account_id =:accountId', array(':accountId' => $accountId));
        }
        if (!$returnRow) {
            return $queryBuilder->queryAll();
        }

        return $queryBuilder->queryRow();
    }

    /**
     * 拉取listings
     * @param $accountId
     * @param array $itemList
     * @return int
     */
    public function pullListings($accountId, $itemList = array())
    {
        // 成功拉取的数量
        $listing = 0;
        

        if (!$accountId) {
            //return $listing;
            throw new \Exception(Yii::t('shopee', 'Please special account'));
        }

        if ($itemList) {
            $indexer = 1;
            foreach ($itemList as $itemId) {
                //延时处理
                if ($indexer % 400 == 0) {
                    sleep(60);
                }
                $indexer++;
                //$listings[] =
                try {
                    $this->pullSingleListing($itemId, $accountId);
                    $listing++;
                } catch (\Exception $e) {
                    //echo $e->getMessage();
                    continue;
                    //throw new \Exception($e->getMessage());
                }

            }

            return $listing;
        }

        $limit = 100;
        $offset = 0;
        $request = new GetItemsListRequest();
        $indexer = 1;
        while (true) {
            $response = $request->setAccount($accountId)->setLimit($limit, $offset)->setRequest()->sendRequest()->getResponse();
            if (!$request->getIfSuccess()) {
                throw new \Exception($request->getErrorMsg());
             }
            $offset += $limit;

            foreach ($response->items as $item) {
                //延时处理
                if ($indexer % 400 == 0) {
                    sleep(60);
                }
                $indexer++;
                try {
                    $this->pullSingleListing($item->item_id, $accountId);
                    $listing++;
                } catch (\Exception $e) {
                    //echo $e->getMessage();
                    continue;
                    //throw new \Exception($e->getMessage());
                }
            }
            if (!$response->more) {
                break;
            }
        }


        return $listing;
    }

    private function pullSingleListing($listingId, $accountId)
    {
        $request = new GetItemDetailRequest();

        $response = $request->setAccount($accountId)->setItemId($listingId)->setRequest()->sendRequest()->getResponse();
        if (!$request->getIfSuccess()) {
            //throw  new \Exception(Yii::t('shopee', 'Pull item(:itemId) failed.', array(':itemId' => $listingId)));
            throw  new \Exception(is_scalar($response->error)?$response->error:"unknown error");
        }

        try {
            $this->saveItemInfo($accountId, $response->item);
        }catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * @param $listingId
     * @param $newValue
     * @param $updateType
     * @throws Exception
     * 更新在线listing数据
     */
    public function updateOnlineListing($accountId, $listingId, $newValue, $updateType)
    {

        switch ($updateType) {
            case 'updatePrice':
                $request = new UpdatePriceRequest();
                /**
                 *  comment to test
                 */
                $response = $request->setItemId($listingId)->setPrice($newValue)->setAccount($accountId)->setRequest()->sendRequest()->getResponse();

                if (!$request->getIfSuccess()) {
                    throw new \Exception(Yii::t('shopee', 'Update (:itemId) price failed', array(':itemId' => $listingId)).$response->errors);
                }

                $this->updateListing(array('price' => $newValue), $listingId, $accountId);
                // 更新子表冗余数据
                ShopeeProductVariation::model()->updateListing(
                    array(
                        'price' => $newValue,
                    ),
                    $listingId
                );
                break;
            case 'updateStock':
                $request = new UpdateStockRequest();
                /**
                 *  comment to test
                 */
                $response = $request->setItemId($listingId)->setQty($newValue)->setAccount($accountId)->setRequest()->sendRequest()->getResponse();

                if (!$request->getIfSuccess()) {
                    throw new \Exception(Yii::t('shopee', 'Update (:itemId) stock failed', array(':itemId' => $listingId)).$response->errors);
                }
                $this->updateListing(array('stock' => $newValue), $listingId, $accountId);
                // 更新子表冗余数据
                ShopeeProductVariation::model()->updateListing(
                    array(
                        'stock' => $newValue,
                    ),
                    $listingId
                );
                break;
            default:
                throw new \Exception("Please special update type");
        }


    }

    /***
     * 更新系统listing 表
     */
    public function updateListing($updateData = array(), $listingId, $accountId = null)
    {
        $whereCondition = " listing_id =" . $listingId;
        if ($accountId) {
            $whereCondition .= " AND account_id = " . $accountId;
        }
        return $this->getDbConnection()->createCommand()->update($this->tableName(), $updateData, $whereCondition);
    }

    /**************************************************************/
    /**
     * 列表显示相关函数
     */
    /**************************************************************/

    /**
     * Search Info
     * @param type $model
     * @param type $sort
     * @return \CActiveDataProvider
     */
    public function search($model = null, $sort = array(), $with = array(), $CDbCriteria = null)
    {
        set_time_limit(3600);
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder' => 'id',
            'defaultOrderDirection'=> 'DESC'
        );


        // 有产品属性过滤

        $criteria = new CDbCriteria();
        $criteria->select = 't.id, t.account_id,  IF(pv.stock=0, 1, 2) AS stock_status, t.site_code, t.listing_id, t.name, t.seller_sku, t.sku, t.main_image_url,
         t.has_variation, t.status, t.created_at, t.stock, t.price,
         pv.stock as sub_stock, pv.price as sub_price, pv.sku as sub_sku';
        $criteria->join = 'LEFT JOIN ' . ShopeeProductVariation::model()->tableName() . ' AS pv ON (t.id = pv.parent_id) 
        LEFT JOIN '. ShopeeAccount::model()->tableName(). ' AS a ON (t.account_id = a.id)';


        $selectedAttributeOptions = Yii::app()->request->getParam('product_attribute_filter', array());

        if ($selectedAttributeOptions) {
            $shopeeSkus = ShopeeProductVariation::model()->getDbConnection()->createCommand()
                ->select('sku')
                ->from(ShopeeProductVariation::model()->tableName() . ' as pv')
                ->queryColumn();
            $selectedAttributeModel = ProductSelectAttribute::model();
            $skuHasSelectedOption = $selectedAttributeModel->getDbConnection()->createCommand()
                ->select('sku')
                ->from($selectedAttributeModel->tableName())
                //->where(array('in', 'sku', $skuFilter))
                ->where(array('in', 'attribute_value_id', $selectedAttributeOptions))->queryColumn();

            $shopeeSkusHasSelectedOption = array_intersect($skuHasSelectedOption, $shopeeSkus);

            $shopeeSkusHasSelectedOption = array_unique($shopeeSkusHasSelectedOption);

            if ($shopeeSkusHasSelectedOption) {
                $criteria->addCondition('pv.sku IN ('.MHelper::simplode($shopeeSkusHasSelectedOption).')');
            }
        }
        $criteria->group = 't.id';

        $dataProvider = parent::search($this, $sort, $with, $criteria);






        $variationParentIds = array();

        $resultData = $dataProvider->data;
        $additionalData = array();
        //$accountList = ShopeeAccount::model()->getAvailableIdNamePairs();
        $accountList = ShopeeAccount::model()->getIdNamePairs();

        $keyIdMapper = array();

        $statusMapper = array_flip($this->statusMapper);
        $variationMapper = array();
        foreach ($resultData as $key => $listingObject) {

            $listingObject->accountName = isset($accountList[$listingObject['account_id']]) ? $accountList[$listingObject['account_id']] : '-';
            $listingObject->mainImageUrl = $listingObject->main_image_url ? '<img src="' . $listingObject->main_image_url . '" width=100 height=100>' : "";
            $listingObject->status =  Yii::t('shopee', $statusMapper[$listingObject->status]);
            $keyIdMapper[$listingObject->id] = $key;

            $additionalData[$key] = $listingObject;
            //$variationParentIds[] = $listingObject->id;
           /* if ($listingObject->has_variation) {
                $variationParentIds[] = $listingObject->id;
            } else {
                $additionalData[$key]->detail[] = array(
                    'sub_id' => $listingObject->listing_id,
                    'variation_id' => $listingObject->listing_id,
                    'price' => $listingObject->price,
                    'stock' => $listingObject->stock,
                    'created_at' => $listingObject->created_at,
                    'variation_option_name' => '',
                    'variation_sku' => $listingObject->seller_sku,
                    'status' => Yii::t('shopee', $statusMapper[$listingObject->status]),
                    'action' => $this->generateActionHtml($listingObject->id)
                );
            }*/
           if ($listingObject->has_variation) {
               $variationMapper[] =  $listingObject->id;
           }
            $variationParentIds[] = $listingObject->id;

        }
        if ($variationParentIds) {

            $variationsSet = ShopeeProductVariation::model()->getVariationInfoByParentIds($variationParentIds);
            $warehouseSkuMapper = array();



            foreach ($variationsSet as $variation) {
                $warehouseSkuMapper[] = $variation['variation_sku'];
            }
            $warehouseSkuMapModel = WarehouseSkuMap::model();
            $productData = $warehouseSkuMapModel->getProductDataWithSkuListAndStockFilter(array_values
            ($warehouseSkuMapper));
            $warehouseSkuMapper = array();
            foreach ($productData as $p) {
                $warehouseSkuMapper[$p['sku']] = $p['available_qty'];
            }
            foreach ($variationsSet as $parentId => $variation) {

                $key = $keyIdMapper[$variation['parent_id']];
                $additionalData[$key]->detail[] = array(
                    'sub_id' => $variation['variation_id'],
                    'variation_id' => $variation['variation_id'],
                    'price' => $variation['price'],
                    'stock' => $variation['stock'],
                    'system_stock'=> isset($warehouseSkuMapper[$variation['variation_sku']])?
                        $warehouseSkuMapper[$variation['variation_sku']]:"",
                    'variation_option_name' => $variation['variation_option_name'],
                    'created_at' => $variation['created_at'],
                    'variation_sku' => $variation['variation_sku'],
                    'status' => Yii::t('shopee', ShopeeProductVariation::model()->getStatusMapper($variation['status'], true)),
                    'action' => $this->generateActionHtml($variation['id'], in_array($variation['parent_id'], $variationMapper)?true:false)
                );

            }
        }

        $dataProvider->setData($additionalData);

        return $dataProvider;
    }

    private function generateActionHtml($id, $isVariant = false)
    {
        $html = '';

        $html .= '<a href="' . Yii::app()->createUrl('shopee/shopeeproduct/updateForm', array('action' => 'updatePrice', 'id' => $id, 'isVariant' => $isVariant)) . '"
         target="dialog" mask="true">' . Yii::t('shopee', 'Update Price') . '</a>';
        $html .= '<br>';
        $html .= '<a href="' . Yii::app()->createUrl('shopee/shopeeproduct/updateForm', array('action' => 'updateStock', 'id' => $id, 'isVariant' => $isVariant)) . '"
          target="dialog" mask="true">' . Yii::t('shopee', 'Update Stock') . '</a>';

        return $html;
    }


    /**
     * @return array 列表header
     */
    public function attributeLabels()
    {
        return array(
            'listing_id' => Yii::t("shopee", 'Listing ID'),
            'sku' => Yii::t('shopee', 'SKU'),
            'status' => Yii::t('shopee', 'Status'),
            'name' => Yii::t('shopee', 'Product Name'),
            'seller_sku' => Yii::t('shopee', 'Seller SKU'),
            'account_name' => Yii::t('shopee', 'Account Name'),
            'account_id' => Yii::t('shopee', 'Account'),
            'main_image' => Yii::t('shopee', 'Product Images'),
            'stock' => Yii::t('shopee', 'Online Stock'),
            'system_stock' => Yii::t('shopee', 'System Stock'),
            'price' => Yii::t('shopee', 'Price'),
            'variation_option_name' => Yii::t('shopee', 'Variation Option Name'),
            'created_at' => Yii::t('shopee', 'Created At'),
            'variation_id' => Yii::t('shopee', 'Variation ID'),
            'variation_sku' => Yii::t('shopee', 'Variation SKU'),
            'currency' => Yii::t('shopee', 'Currency'),
            'action' => Yii::t('shopee', 'Action'),
            'site_code' => Yii::t('shopee', 'Site'),
            'stock_status' => Yii::t('shopee', 'Stock Status'),
            'main_status'=> Yii::t('shopee', 'Main Status'),

        );
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
                'alias' => 't',
                'type' => 'text',
                'label' => 'SKU',
                'search' => '=',
                'htmlOption' => array(
                    'size' => '22',
                )
            ),
            array(
                'name' => 'variation_id',
                'type' => 'text',
                'label' => Yii::t("shopee", 'Listing ID'),
                'search' => '=',
                'alias' => 'pv',
                'htmlOption' => array(
                    'size' => '22'
                )
            ),
            array(
                'name' => 'name',
                'type' => 'text',
                'search' => 'LIKE',
                'prefix'=> true,
               // 'rel' => true,
                'alias' => 't',
                'htmlOption' => array(
                    'size' => '200',
                    'style' => 'width:300px;',
                    'width' => '400px'
                ),
            ),
            array(
                'name' => 'account_id',
                'type' => 'dropDownList',
                //'expr' => 'IF(p.account_id=0, 1, 2)',
                'expr'=>" left(account_name,   POSITION('.' IN account_name) - 1)",
                'search' => '=',
                'data' => function (){
                    $accountList = ShopeeAccount::model()->getIdNamePairs();

                    $result = array();

                    foreach($accountList as $id=> $name) {
                        list($account, $site) = explode('.', $name);
                        $result[$account] = $account;
                    }

                    return $result;
                }
            ),
            array(
                'name' => 'site_code',
                'type' => 'dropDownList',
                'search' => '=',
                'data' => ShopeeAccount::model()->getSiteCodeList(true)
            ),

            array(
                'name' => 'status',
                'type' => 'dropDownList',
                'alias' => 't',
                'search' => '=',
                //'data'=> array_flip($this->statusMapper),
                'data' =>  function ()  {
                    $result = array();
                    $status = ShopeeProduct::model()->statusMapper; //ShopeeProductVariation::model()->getStatusMapper(null, true);
                    foreach (array_flip($status) as $key => $value) {
                        $result[$key] = Yii::t('shopee', $value);
                    }
                    return $result;
                }
            ),
            array(
                'name' => 'stock_status',
                'type' => 'dropDownList',
                'expr' => 'IF(pv.stock=0, 1, 2)',
                //'alias'=> 't',
                'search' => '=',
                'data' => array(
                    1 => Yii::t('shopee', 'Out of Stock'),
                    2 => Yii::t('shopee', 'In Stock'),
                )

            ),

            array(
                'name' => 'created_at',
                'type' => 'text',
                'search' => 'RANGE',
                'alias' => 't',
                'htmlOptions' => array(
                    'class' => 'date',
                    'dateFmt' => 'yyyy-MM-dd HH:mm:ss',
                    'style' => 'width:120px;',
                    'width' => '300px'
                ),
            )

        );
    }

}