<?php
/**
 * @desc 速卖通listing下载
 * @author yangsh
 * @since 2016-06-14
 *
 */
class AliexpressProductDownload extends AliexpressProduct {

	const EVENT_NAME              	     = 'get_product';

	/** @var integer 账号ID */
	protected $_accountID           	 = '';

	/** @var string productStatusType */
	protected $_productStatusType   	 = 'onSelling';

	/** @var integer productId */
	protected $_productId           	 = null;

	/** @var array exceptedProductIds */
	protected $_exceptedProductIds		 = array();

	/** @var int intervalMinute */
	protected $_intervalMinute		 	 = 60;//60分钟

	/** @var int offLineTime */
	protected $_offLineTime           	 = array();

	/** @var integer pageSize */
	protected $_pageSize            	 = 100;	

	/** @var string 错误信息 */
	protected $_errorMessage      	     = '';

	public static function model($className = __CLASS__) {
		return parent::model ( $className );
	}

	/**设置账号id*/
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
		return $this;
	}

	public function setProductStatusType($productStatusType) {
		$this->_productStatusType = $productStatusType;
		return $this;
	}

	public function setProductId($productId) {
		$this->_productId = $productId;
		return $this;
	}

	public function setIntervalMinute($intervalMinute) {
		$this->_intervalMinute = $intervalMinute;
		return $this;
	}	

	public function setOffLineTime($offLineTime) {
		$this->_offLineTime = $offLineTime;
		return $this;
	}

	public function setPageSize($pageSize) {
		$this->_pageSize = $pageSize;
		return $this;
	}

	public function setErrorMessage($errMsg) {
		$this->_errorMessage = $errMsg;
		return $this;
	}

	/**获取账号id*/
	public function getAccountID() {
		return $this->_accountID;
	}

	public function getProductStatusType() {
		return $this->_productStatusType;
	}

	public function getProductId() {
		return $this->_productId;
	}

	public function getIntervalMinute() {
		return $this->_intervalMinute;
	}	

	public function getOffLineTime() {
		return $this->_offLineTime;
	}	

	public function getPageSize() {
		return $this->_pageSize;
	}

	public function getErrorMessage() {
		return $this->_errorMessage;
	}

	/**
	 * 返回商品状态类型列表
	 * @return array
	 */
	public static function getProductStatusTypeList() {
		return array(
			FindProductInfoListQueryRequest::PRODUCT_STATUS_ONSELLING,
			FindProductInfoListQueryRequest::PRODUCT_STATUS_OFFLINE,
			FindProductInfoListQueryRequest::PRODUCT_STATUS_AUDITING,
			FindProductInfoListQueryRequest::PRODUCT_STATUS_EDITINGREQUIRED,
		);
	}	

	public function setExceptedProductIds($exceptedProductIds=array()) {
		$accountId   			= $this->_accountID;
		$productStatusType 		= $this->_productStatusType;
		$intervalMinute 		= $this->_intervalMinute;
		if (empty($exceptedProductIds) && !empty($intervalMinute)) {
			$start = date("Y-m-d H:i:s",strtotime('-'.$intervalMinute.' minutes'));
			$where = " account_id='{$accountId}' and product_status_type='{$productStatusType}' and modify_time > '{$start}' ";
			$rows  = AliexpressProduct::model()->getListByCondition('aliexpress_product_id',$where);
			if (!empty($rows)) {
				$exceptedProductIds = array();
				foreach ($rows as $v) {
					$exceptedProductIds[] = $v['aliexpress_product_id'];
				}
			}
		}
		$this->_exceptedProductIds = $exceptedProductIds;
		return $this;
	}	

	public function getExceptedProductIds() {
		return $this->_exceptedProductIds;
	}	
	
	/**
	 * @desc 通过接口获取指定productId的产品数据
	 * @param string $productID
	 * @return boolean|mixed
	 */
	public function findAeProductById($productID) {
		$accountID = $this->_accountID;
		if (empty($accountID)) {
			$this->setErrorMessage(Yii::t('aliexpress_product', 'No Account ID'));
			return false;
		}
		$request = new FindAeProductByIdRequest();
		$request ->setProductId($productID);
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		if (!$request->getIfSuccess()) {
			$this->setErrorMessage($request->getErrorMsg());
			return false;
		}
		return $response;
	}

	/**
	 * @desc 拉取产品数据
	 * @return boolean
	 * @author yangsh
	 * @since 2016-06-13
	 */
	public function getAliProducts() {
		$accountId   			= $this->_accountID;
		$productStatusType 		= $this->_productStatusType;
		$pageSize 				= $this->_pageSize;
		$productId 				= $this->_productId;
		$exceptedProductIds 	= $this->_exceptedProductIds;
		$offLineTime 			= $this->_offLineTime;

		//1. 设置请求参数
		$errMsgs 		= '';
		$currentPage 	= 1;
		$totalPage 		= 1;
		$total 			= 0;
		$request 		= new FindProductInfoListQueryRequest();
		$request->setPageSize($pageSize);
		$request->setPage($currentPage);
		$request->setTotalPage($totalPage);
		/* echo "=======productStatusType======<br/>";
		echo $productStatusType,"<br/>"; */
		while ( $request->getPage() <=  $request->getTotalPage() ) {
			$request->setAccount($accountId);
			$request->setProductStatusType($productStatusType);
			if (!empty($productId)){
				$request->setProductId($productId);
			}
			if (!empty($offLineTime)) {
				$request->setOffLineTime($offLineTime);
			}
			if (!empty($exceptedProductIds)) {
				$request->setExceptedProductIds(json_encode($exceptedProductIds));
			}			
			$response = $request->setRequest()->sendRequest()->getResponse();
			//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/response_'.$currentPage.'.log',print_r($response,true)."\r\n");//

			if (!$request->getIfSuccess()) {
				//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/request_err_'.$currentPage.'.log', '请求接口失败!====='.json_encode(array($accountId,$productStatusType)).'----'. $request->getErrorMsg()."\r\n");
				$errMsgs .= '请求接口失败!====='.$request->getErrorMsg();
				break;
			}

			//分页参数设置
			$totalPage = (int)$response->totalPage;
			$currentPage++;
			$request->setPage($currentPage);
			$request->setTotalPage($totalPage);

			//处理数据
			$productList = isset($response->aeopAEProductDisplayDTOList) ? $response->aeopAEProductDisplayDTOList : array();
			if (empty($productList)) {
				break;
			}
			/* var_dump($totalPage);
			var_dump($currentPage);
			echo "<pre>";
			echo "==============productList ===============<br/>";
			print_r($productList); */
			//循环取出每个产品的产品详情
			foreach ($productList as $product) {
				//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/product_'.($currentPage-1).'.log',print_r($product,true)."\r\n");	
				//判断本地记录更新时间与aliexpress最新更新时间
				$productID          	= $product->productId;
				$gmtModified 			= MHelper::aliexpressTimeToBJTime($product->gmtModified);
				$where 					= "account_id='{$accountId}' and aliexpress_product_id='{$productID}'";
				$aliProductInfo 		= $this->getOneByCondition('id,gmt_modified', $where);
				/* if ($gmtModified == $aliProductInfo['gmt_modified']) {
					continue;//内容未更新跳过
				} */
				//调用api接口获取单个商品信息
				$productInfo			= $this->findAeProductById($productID);
				//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/productInfo_'.($currentPage-1).'.log',print_r(array('product'=>$product,'productInfo'=>$productInfo),true)."\r\n");//
				if ( empty($productInfo) ){
					//throw new Exception($this->_errorMessage);
					continue;
				}
				
				try {
					$dbTransaction   			= $this->getDbConnection()->beginTransaction();
					$aliexpressProduct  	= AliexpressProduct::model();
					$aliProductExtend  		= AliexpressProductExtend::model();
					$aliProductVariation 	= AliexpressProductVariation::model();
					
					//Save ProductInfo
					$aliProductId = $aliexpressProduct->saveAliProductInfo($accountId, $product, $productInfo, $aliProductInfo);
					if (!$aliProductId){
						//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/SaveProductInfo_'.($currentPage-1).'.log','Save ProductInfo Failure ---productID: '.$productID.'---'.$aliexpressProduct->getErrorMessage()."\r\n");//												
						throw new Exception('Save ProductInfo Failure --- '.$aliexpressProduct->getErrorMessage());
                    }

					//save AliexpressProductExtendInfo
                    $flag = $aliProductExtend->saveAliProductExtend($aliProductId, $productInfo);
					if (!$flag){
						//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/SaveProductInfo_'.($currentPage-1).'.log','save saveAliProductExtend Failure ---productID: '.$productID.'---'.$aliProductExtend->getErrorMessage()."\r\n");//						
						throw new Exception('save saveAliProductExtend Failure --- '.$aliProductExtend->getErrorMessage());
                    }

                    //Save AliProductVariation
                    $flag = $aliProductVariation->saveAliProductVariation($aliProductId, $productInfo);
					if (!$flag){
						//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/SaveProductInfo_'.($currentPage-1).'.log','Save saveAliProductVariation Failure---productID: '.$productID.'---'.$aliProductVariation->getErrorMessage()."\r\n");//					
						throw new Exception('Save saveAliProductVariation Failure --- '.$aliProductVariation->getErrorMessage());
                    }

                    $total++;
                    $dbTransaction->commit();
					//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/commit_'.($currentPage-1).'.log','commit ---productID: '.$productID."\r\n");//
				} catch (Exception $e) {
					$errMsgs .= $e->getMessage().'--- productID:'.$productID;
					//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/rollback_'.($currentPage-1).'.log','rollback ---productID: '.$productID.'----'.$e->getMessage()."\r\n");
					$dbTransaction->rollback();
				}
			}//end foreach
		}//end while

		//MHelper::writefilelog('aliexpress/aliexpress_listing/'.date('Ymd').'/'.$accountId.'/total.log', $accountId.' ### '.$productStatusType.'### total:'.$total."\r\n");
		//检查是否出现异常
		if ($errMsgs != '') {
			echo $errMsgs."<hr>";
			$this->setErrorMessage($errMsgs);
			return false;
		}
		return true;
	}

}