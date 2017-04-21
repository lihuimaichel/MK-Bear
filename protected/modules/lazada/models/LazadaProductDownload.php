<?php
/**
 * @desc lazada listing model
 * @author yangsh
 * @since  2016-06-22
 *
 */
class LazadaProductDownload extends LazadaProduct {
    
    const EVENT_NAME                    = 'getproduct';
    const EVENT_NAME_CHECK              = 'check_products';

    /** @var integer  账号分组ID **/
    protected $_accountGroupID               = null;

    /** @var integer  账号autoID **/
    protected $_accountAutoID           = null;   
    
    /** @var integer **/
    protected $_siteID                  = null;

    /** @var string 产品状态**/
    protected $_productState            = 'all';

    /** @var integer 索引号 */
    protected $_startIndex              = 0;     

    /** @var integer limit */
    protected $_limit                   = 500;

    /** @var array sellerSkuList */
    protected $_sellerSkuList           = null;

    /** @var string search */
    protected $_search                  = null;    

    /** @var array created */
    protected $_created                 = null;

    /** @var array updated */
    protected $_updated                 = null;

    /** @var string 错误信息 */
    protected $_errorMessage            = '';

    /** @var integer  老系统帐号ID **/
    protected $_oldAccountID            = null;  

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
        $accountInfo = LazadaAccount::model()->getOneByCondition('*',"id={$accountID}");
        $this->_siteID = $accountInfo['site_id'];
        $this->_accountAutoID = $accountInfo['id'];
        $this->_accountGroupID = $accountInfo['account_id'];
        $this->_oldAccountID = $accountInfo['old_account_id'];
        return $this;
    }

    public function setProductState($productState){
        $this->_productState = $productState;
        return $this;
    }

    public function setStartIndex($startIndex){
        $this->_startIndex = $startIndex;
        return $this;
    }

    public function setLimit($limit){
        $this->_limit = $limit;
        return $this;
    }

    /**
     * [setSellerSkuList description]
     * @param array $sellerSkuList [description]
     */
    public function setSellerSkuList($sellerSkuList){
        $this->_sellerSkuList = $sellerSkuList;
        return $this;
    }

    public function setSearch($search){
        $this->_search = $search;
        return $this;
    }

    public function setCreated($created){
        $this->_created = $created;
        return $this;
    }

    public function setUpdated($updated){
        $this->_updated = $updated;
        return $this;
    }     

    public function setExceptionMessage($errorMessage){
        $this->_errorMessage = $errorMessage;
        return $this;
    } 

    public function getExceptionMessage(){
        return $this->_errorMessage;
    }

    /**
     * @desc 拉取listing--new
     */
    public function startDownloadProductsNew() {
        $path = 'lazada/lazadalistingnew/'.date("Ymd").'/'.$this->_accountAutoID.'/'.date("His");
        $index              = $this->_startIndex;//当前页数
        $limit              = $this->_limit;//每次拉取条数            
        $errMsgs            = '';
        $total              = 0;//总记录数
        $hasNextPage        = true;//是否有下一页
        while ($hasNextPage) {
            $getProductRequest  = new GetProductsRequestNew();
            //产品状态
            if (!empty($this->_productState)) {
                $getProductRequest->setFilter($this->_productState);
            }
            //设置listing发布时间 -- start
            if (!empty($this->_created[0])) {
                $getProductRequest->setCreatedAfter($this->_created[0]);
            }
            //设置listing发布时间 -- end
            if (!empty($this->_created[1])) {
                $getProductRequest->setCreatedBefore($this->_created[1]);
            }
            //设置listing更新时间 -- start
            if (!empty($this->_updated[0])) {
                $getProductRequest->setUpdatedAfter($this->_updated[0]);
            }
            //设置listing更新时间 -- end
            if (!empty($this->_updated[1])) {
                $getProductRequest->setUpdatedBefore($this->_updated[1]);
            }
            //设置搜索产品关键字
            if (!empty($this->_search)) {
                $getProductRequest->setSearch($this->_search);
            }
            //指定要拉取的seller sku
            if (!empty($this->_sellerSkuList)) {
                $getProductRequest->setSkuSellerList(json_encode($this->_sellerSkuList));
            }            
            //设置一次搜索多少条
            $getProductRequest->setLimit($limit);
            //设置offset
            $offset = $index * $limit;
            $getProductRequest->setOffset($offset);

            //get response
            $response = $getProductRequest ->setApiAccount($this->_accountAutoID)
                                           ->setRequest()
                                           ->sendRequest()
                                           ->getResponse();                                          
            $index++;     

            if (!empty($_REQUEST['debug'])) {
                MHelper::printvar($response,false);
            }

            MHelper::writefilelog($path.'/response_'.($index-1).'.txt', print_r($response,true)."\r\n");
            if (!$getProductRequest->getIfSuccess()) {
                $logtxt = '请求失败:'.$getProductRequest->getErrorMsg();
                //MHelper::writefilelog($path.'/requestErr_'.($index-1).'.txt', $logtxt."\r\n");
                $errMsgs .= $logtxt;
                break;
            }
            
            //数据为空
            if (empty($response->Body->Products->Product)) {
                break;
            }

            //保存产品信息
            foreach ($response->Body->Products->Product as $product) {
                try {
                    $recordId = $this->saveProductInfoNew($product);     
                    $total++;          
                } catch (Exception $e) {
                    $errMsgs .= $e->getMessage();
                }
            } 

            if (!empty($this->_sellerSkuList)) {//指定sku仅返回一页
                break;
            }                
        }
        $this->setExceptionMessage($errMsgs);
        return $errMsgs == ''? true : false;
    }

    /**
     * @desc 保存product信息     
     * @param  object $product       
     * @return boolean
     * @author yangsh
     * @since 2016-10-07
     */
    public function saveProductInfoNew($product) {
        try {
            $lazadaProduct = new LazadaProduct();
            $accountAutoID = $this->_accountAutoID;
            $accountGroupID = $this->_accountGroupID;
            $oldAccountID   = $this->_oldAccountID;
            $siteID = $this->_siteID;        
            $name = isset($product->Attributes->name) ? trim($product->Attributes->name) : '';
            $brand = isset($product->Attributes->brand) ? trim($product->Attributes->brand) : '';
            $primaryCategory = isset($product->PrimaryCategory) ? trim($product->PrimaryCategory) : '';         
            foreach ($product->Skus->Sku as $v) {
                $onlineSku = trim($v->SellerSku);
                $onlineSku = mb_strlen($onlineSku)>25 ? mb_substr($onlineSku,0,25) : $onlineSku;
                $sku = $onlineSku == '' ? 'unknown' : encryptSku::getRealSku($onlineSku);
                $productID = implode('-',array($siteID,$oldAccountID,$onlineSku));
                $shopSku = isset($v->_shop_sku_) ? trim($v->_shop_sku_) : trim($v->ShopSku);
                $productData = array(
                    'account_auto_id'   => $accountAutoID,
                    'account_id'        => $accountGroupID,
                    'site_id'           => $siteID,
                    'seller_sku'        => $onlineSku,
                    'sku'               => $sku,
                    'shop_sku'          => $shopSku,
                    'name'              => $name,
                    'parent_sku'        => $onlineSku,
                    'variation'         => isset($v->_compatible_variation_) ? trim($v->_compatible_variation_) : '',
                    'quantity'          => (int)$v->quantity,
                    'available'         => (int)$v->Available,
                    'price'             => floatval($v->price),
                    'sale_price'        => isset($v->special_price) ? floatval($v->special_price) : 0,
                    'sale_start_date'   => trim($v->special_from_date) == '' ? '0000-00-00 00:00:00' : trim($v->special_from_date),
                    'sale_end_date'     => trim($v->special_to_date) == '' ? '0000-00-00 00:00:00' : trim($v->special_to_date),
                    'status'            => LazadaProduct::getProductStatusByStatusText(trim($v->Status)),
                    'status_text'       => trim($v->Status),
                    'product_id'        => $productID,
                    'url'               => trim($v->Url),
                    'main_image'        => isset($v->Images->Image)? trim($v->Images->Image[0]) : '',
                    'tax_class'         => 'default',
                    'brand'             => $brand,
                    'primary_category'  => $primaryCategory,
                );
                //检查账号listing是否存在，存在则更新记录，不存在则插入新纪录
                $listingID = LazadaProduct::IfListingExists($siteID, $accountGroupID, $onlineSku);
                if ($listingID) {
                    $productData['modify_time'] = date('Y-m-d H:i:s');
                    $lazadaProduct->dbConnection->createCommand()->update($lazadaProduct->tableName(), $productData, "id = $listingID");
                } else {
                    $productData['create_time'] = date('Y-m-d H:i:s');
                    $lazadaProduct->dbConnection->createCommand()->insert($lazadaProduct->tableName(), $productData);
                    $listingID = $lazadaProduct->dbConnection->getLastInsertID();
                }

                $extentdModel = new LazadaProductExtend();
                $extentdModel->setProductId($listingID)->saveProductExtendInfoNew($product);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    /**
     * @desc 拉取listing
     */
    public function startDownloadProducts() {
        $path = 'lazada/lazadalisting/'.date("Ymd").'/'.$this->_accountAutoID.'/'.date("His");
        $index              = $this->_startIndex;//当前页数
        $limit              = $this->_limit;//每次拉取条数            
        $hasNextPage        = true;//是否有下一页
        $errMsgs            = '';
        $total              = 0;//总记录数
        while ($hasNextPage) {
            $getProductRequest  = new GetProductsRequest();
            //产品状态
            if (!empty($this->_productState)) {
                $getProductRequest->setFilter($this->_productState);
            }
            //设置listing发布时间 -- start
            if (!empty($this->_created[0])) {
                $getProductRequest->setCreatedAfter($this->_created[0]);
            }
            //设置listing发布时间 -- end
            if (!empty($this->_created[1])) {
                $getProductRequest->setCreatedBefore($this->_created[1]);
            }
            //设置listing更新时间 -- start
            if (!empty($this->_updated[0])) {
                $getProductRequest->setUpdatedAfter($this->_updated[0]);
            }
            //设置listing更新时间 -- end
            if (!empty($this->_updated[1])) {
                $getProductRequest->setUpdatedBefore($this->_updated[1]);
            }
            //设置搜索产品关键字
            if (!empty($this->_search)) {
                $getProductRequest->setSearch($this->_search);
            }
            //指定要拉取的seller sku
            if (!empty($this->_sellerSkuList)) {
                $getProductRequest->setSkuSellerList(json_encode($this->_sellerSkuList));
            }                     
            //设置一次搜索多少条
            $getProductRequest->setLimit($limit);
            //设置offset
            $offset = $index * $limit;
            $getProductRequest->setOffset($offset);
            //get response
            $response = $getProductRequest->setApiAccount($this->_accountAutoID)
                                          ->setRequest()
                                          ->sendRequest()
                                          ->getResponse();    
            $index++;      
            if (!empty($_REQUEST['debug'])) {
                MHelper::printvar($response,false);
            }
            //MHelper::writefilelog($path.'/response_'.($index-1).'.txt', print_r($response,true)."\r\n");
            //返回结果失败
            if (!$getProductRequest->getIfSuccess()) {
                $logtxt = '请求失败:'.$getProductRequest->getErrorMsg();
                //MHelper::writefilelog($path.'/requestErr_'.($index-1).'.txt', $logtxt."\r\n");
                $errMsgs .= $logtxt;
                break;
            }
            //数据为空
            if (empty($response->Body->Products->Product)) {
                break;
            }
            //保存产品信息
            foreach ($response->Body->Products->Product as $product) {
                try {
                    $recordId = $this->saveProductInfo($product);
                    $total++;
                } catch (Exception $e) {
                    $errMsgs .= $e->getMessage();
                }
            }      
        }
        $this->setExceptionMessage($errMsgs);
        return $errMsgs == ''? true : false;
    }

    /**
     * @desc 保存product信息
     * @param  object $product
     * @return boolean
     * @author yangsh
     * @since 2016-06-23
     */
    public function saveProductInfo($product) {
        try {
            $lazadaProduct  = new LazadaProduct();
            $onlineSku      = trim($product->SellerSku);
            $onlineSku      = mb_strlen($onlineSku)>25 ? mb_substr($onlineSku,0,25) : $onlineSku;
            $sku            = encryptSku::getRealSku($onlineSku);
            $sku            = $sku ? $sku : $onlineSku;
            $productID      = implode('-',array($this->_siteID,$this->_accountGroupID,$onlineSku));
            $productData    = array(
                'account_auto_id'   => $this->_accountAutoID,
                'account_id'        => $this->_accountGroupID,
                'site_id'           => $this->_siteID,
                'seller_sku'        => $onlineSku,
                'sku'               => $sku,
                'shop_sku'          => trim($product->ShopSku),
                'name'              => trim($product->Name),
                'parent_sku'        => trim($product->ParentSku),
                'variation'         => trim($product->Variation),
                'quantity'          => (int)$product->Quantity,
                'available'         => (int)$product->Available,
                'price'             => floatval($product->Price),
                'sale_price'        => isset($product->SalePrice) ? floatval($product->SalePrice) : 0,
                'sale_start_date'   => trim($product->SaleStartDate) == '' ? '0000-00-00 00:00:00' : trim($product->SaleStartDate),
                'sale_end_date'     => trim($product->SaleEndDate) == '' ? '0000-00-00 00:00:00' : trim($product->SaleEndDate),
                'status'            => LazadaProduct::getProductStatusByStatusText(trim($product->Status)),
                'status_text'       => trim($product->Status),
                'product_id'        => $productID,
                'url'               => trim($product->Url),
                'main_image'        => trim($product->MainImage),
                'tax_class'         => isset($product->TaxClass) ? trim($product->TaxClass) : '',
                'brand'             => isset($product->Brand) ? trim($product->Brand) : '',
                'primary_category'  => isset($product->PrimaryCategory) ? trim($product->PrimaryCategory) : '',
            );
            //检查账号listing是否存在，存在则更新记录，不存在则插入新纪录
            $listingID = LazadaProduct::IfListingExists($this->_siteID, $this->_accountGroupID, $onlineSku);
            if ($listingID) {
                $productData['modify_time'] = date('Y-m-d H:i:s');
                $lazadaProduct->dbConnection->createCommand()->update($lazadaProduct->tableName(), $productData, "id = $listingID");
            } else {
                $productData['create_time'] = date('Y-m-d H:i:s');
                $lazadaProduct->dbConnection->createCommand()->insert($lazadaProduct->tableName(), $productData);
                $listingID = $lazadaProduct->dbConnection->getLastInsertID();
            }
            return $listingID;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }    

}