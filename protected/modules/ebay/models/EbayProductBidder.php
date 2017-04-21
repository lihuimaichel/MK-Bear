<?php
/**
 * @desc Ebay拍卖产品竞拍
 * @author wangrui
 * @since 2015-08-14
 */
class EbayProductBidder extends EbayModel{
	/** @var string 事件名称*/
	const EVENT_NAME = 'get_bidder_list';
	
	/** @var int 帐号ID */
	public $_accountID = null;
	
	/** @var int 站点ID */
	public $_siteID = 0;
	
	/** @var string 异常信息 */
	public $_exception = null;
	
	/** @var int 日志编号*/
	public $_logID = 0;
	
	/** @var object 拉listing返回信息*/
	public $_listing_Response = null;
	
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName(){
		return 'ueb_ebay_product_bidder';
	}
	/**
	 * @desc 设置帐号ID
	 */
	public function setAccountID($accountID) {
		$this->_accountID = $accountID;
	}
	/**
	 * @desc 设置站点ID
	 */
	public function setSite($site){
		$this->_siteID  = $site;
	}
	/**
	 * @desc 设置异常信息
	 */
	public function setExceptionMessage($message){
		$this->_exception = $message;
	}
	/**
	 * @desc 设置日志编号
	 */
	public function setLogID($logID){
		$this->_logID = $logID;
	}
	
	/**
	 * @desc 获取拉产品listing时间段
	 * @since 2015/08/14
	 */
	public function getTimeArr($accountID){
		$lastLog = EbayLog::model()->getLastLogByCondition(array(
				'account_id'    => $accountID,
				'event'         => self::EVENT_NAME,
				'status'        => EbayLog::STATUS_SUCCESS,
		));
		return array(
				//上次有成功,则将上次结束时间往前推15分钟，避免漏单，若不存在已成功的，则从1天前开始拉(需换算成格林威治时间)
		//     			'start_time'    => !empty($lastLog) ? date('Y-m-d\TH:i:s\Z',strtotime($lastLog['end_time']) - 15*60) : date('Y-m-d\TH:i:s\Z',time() - 86400*1 - 8*3600),
				'start_time'    => !empty($lastLog) ? date('Y-m-d\TH:i:s\Z',strtotime($lastLog['end_time']) - 15*60 - 8*3600) : date('Y-m-d\TH:i:s\Z',time() - 86400*7*4 - 8*3600),
				'end_time'      => date('Y-m-d\TH:i:s\Z',time() - 8*3600),
		);
	}
	
	/**
	 * @desc 依据条件获取指定的拍卖产品竞拍
	 * @param Array $date
	 */
	public function getListingByDate($date){
		return $this->getListingByCondition(array(
			'StartTimeFrom'  => $date['start_time'],
			'StartTimeTo'	 => $date['end_time'],
		));
	}
	
	/**
	 * @desc 获取拍卖产品竞拍
	 * @param array $params
	 */
	public function getListingByCondition( $params = array()){
		$accountID = $this->_accountID;
		$request   = new GetBidderListRequest();
		foreach($params as $col=>$val){
			switch ($col) {
				case 'StartTimeFrom':
					  $request->setEndTimeFrom($val);
					  break;
				case 'StartTimeTo':
					  $request->setEndTimeTo($val);
					  break;
			}
		}
		while($request->_pageNumber <= $request->_totalPage){
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			if($request->getIfSuccess()){
				$request->setTotalPage($response->PaginationResult->TotalNumberOfPages); // 设置总页数
				$request->setPageNumber($request->_pageNumber + 1); //设置当前页码
				try{
					foreach ($response->ItemArray->Item as $item){
						$this->_listing_Response = $item;
						$this->saveBidderProduct();
					}
					
				}catch(Exception $e){
					$this->setExceptionMessage(Yii::t('listing', 'Save Bidder Information failed'));
					return false;
				}
			}else{
				$this->setExceptionMessage($request->getErrorMsg());
				return false;
			}
		}
		return true;
	}
	
	/**
	 * @desc 保存相关的拍卖产品竞拍
	 * @since 2015-08-14
	 */
	public function saveBidderProduct(){
		$listing =  $this->_listing_Response;
		$flag = $this->dbConnection->createCommand()->insert(self::tableName(), array(
				'item_id'				=> $listing->ItemID,
				'start_time'			=> $listing->ListingDetails->StartTime,
				'end_time'				=> $listing->ListingDetails->EndTime,
				'view_item_url'			=> $listing->ListingDetails->ViewItemURL,
				'listing_duration'		=> $listing->ListingDuration,
				'listing_type'			=> $listing->ListingType,
				'location'				=> $listing->Location,
				'payment_method'		=> $listing->PaymentMethods,
				'paypal_address'		=> $listing->PayPalEmailAddress,
				'gallery_url'			=> $listing->PictureDetails->GalleryURL,
				'post_code'				=> $listing->PostalCode,
				'category_id'			=> $listing->PrimaryCategory->CategoryID,
				'category_name'			=> $listing->PrimaryCategory->CategoryName,
				'copy_right'			=> $listing->ProductListingDetails->Copyright,
				'productID'				=> $listing->ProductListingDetails->ProductID,
				'quantity'				=> $listing->Quantity,
				'quantity_sold'			=> $listing->SellingStatus->QuantitySold,
				'site'					=> $listing->Site,
				'sku'					=> $listing->SKU,
				'title'					=> $listing->Title,
				'time_left'				=> $listing->TimeLeft,
				'subtitle'				=> $listing->SubTitle,
				'country'				=> $listing->Country,
				'currency'				=> $listing->Currency
		));
		if($flag){
			return $this->dbConnection->getLastInsertID();
		}
		return false;
	}
	
	/**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->_exception;
	}
	
}