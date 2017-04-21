<?php
/**
 * ebay product other Model
 * @author	wx 
 * @since	2015-08-31
 */

class EbayProductOther extends EbayModel {
	
	/** @var 事件名称*/
	const EVENT_NAME = 'get_suggest_product';
	
	/** @var 数据来源*/
	const SOURCE_TYPE = '1'; //表示拉取的是ebay的平台数据
	
	/** @var int 账号ID*/
	public $_accountID = null;
	
	/** @var int 站点ID*/
	public $_siteID = 0;
	
	/** @var string 异常信息*/
	public $_exception = null;
	
	/** @var array 返回的item信息 */
	public $_listing_Response = null;
	
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$this->_accountID = $accountID;
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message){
		$this->_exception = $message;
	}
	
	/**
	 * @desc 设置站点ID
	 */
	public function setSite($site){
		$this->_siteID = $site;
	}
	
	public function tableName() {
		return 'ueb_ebay_product_other';
	}
	
	/**
	 * @desc 获取seller item list
	 * @param array $params
	 */
	public function getSellerItemByCondition ($params = array()) {
		$accountID = $this->_accountID;
		$request = new GetSellerListRequest();
		foreach($params as $col=>$val){
			switch ($col){
				case 'EndTimeFrom':
					$request->setEndTimeFrom($val);
					break;
				case 'EndTimeTo':
					$request->setEndTimeTo($val);
					break;
				case 'IncludeWatchCount':
					$request->setIncludeWatchCount($val);
					break;
				case 'IncludeVariations':
					$request->setIncludeVariations($val);
					break;
				case 'UserId':
					$request->setUserId($val);
					break;
			}
		}
		
		$i = 0;
		while($request->_pageNumber <= $request->_totalPage){
			$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
			if($request->getIfSuccess()){
				$request->setTotalPage($response->PaginationResult->TotalNumberOfPages); //设置总页数
				$request->setPageNumber($request->_pageNumber + 1);
				try {
					//遍历listing数据
					foreach($response->ItemArray->Item as $item){
						$this->_listing_Response = $item;
						//保存listing数据信息
						$this->saveItemInfo( $params['account_id'] );
						$i++;
					}
				}catch (Exception $e) {
					echo $e;
					$this->setExceptionMessage(Yii::t('listing', 'Save listing Information Failed'));
					return false;
				}
			}else {
				$this->setExceptionMessage($request->getErrorMsg());
				return false;
			}
		}
		
		//更新当前账号的item数量
		UebModel::model('EbaySeller')->updateByPk($params['account_id'], array('total_item_num'=>$i,'update_time'=>date('Y-m-d H:i:s')) );
		
		return true;
	}
	
	/**
	 * @desc 保存seller item list
	 * @param sting $userName ,string $accountId
	 * @return Ambigous <boolean, string>
	 */
	public function saveItemInfo( $accountId ){
		$listing = $this->_listing_Response;
		//抓取最低运费
		$shippingPrice = -1;
		$currency = trim( $listing->Currency );
		if($currency=='USD'){//美国站抓国际运费
			foreach($listing->ShippingDetails->InternationalShippingServiceOption as $option) {
				$tempcost = $option->ShippingServiceCost;
				if($shippingPrice==-1 || $tempcost<$shippingPrice){//找到最低的
					$shippingPrice = $tempcost;
				}
			}
		}else{//其它站抓本地
			foreach($listing->ShippingDetails->ShippingServiceOptions as $option) {
				$tempcost = $option->ShippingServiceCost;
				if($shippingPrice==-1 || $tempcost<$shippingPrice){//找到最低的
					$shippingPrice = $tempcost;
				}
			}
		}
		$shippingPrice = floatval($shippingPrice);
		
		//获取一级分类
		$cat = explode(":",$listing->PrimaryCategory->CategoryName);
		$category = $cat['0'];
		$online_time = $listing->ListingDetails->StartTime;
		$sold_num_total = $listing->SellingStatus->QuantitySold;
		$title = $listing->Title;
		$item = trim($listing->ItemID);
		//1.保存到market系统
		$insertData = array(
							'item_id'                    =>$item,
							'account_id'                 =>$accountId,
							'gallery_url'                =>$listing->PictureDetails->GalleryURL,
							'item_url'              	 =>$listing->ListingDetails->ViewItemURL,
							'site_id'                    =>$this->_siteID,
							'en_title'                   =>$title,
							'cn_title'                   =>'',
							'category_name'              =>$category,
							'category_id'	             =>$listing->PrimaryCategory->CategoryID,
							'category_detail'	         =>$listing->PrimaryCategory->CategoryName,
							'sale_price'               	 =>$listing->SellingStatus->CurrentPrice,
							'sale_price_currency'        =>$listing->Currency,
							'ship_price'        		 =>$shippingPrice,
							'online_time'                =>$online_time,
							'sync_time'					 =>date('Y-m-d H:i:s'),
							'last_sync_time'			 =>date('Y-m-d H:i:s'),
							'sold_num_total'			 =>$sold_num_total,
							'watch_count' 				 =>$listing->WatchCount,
					);
		//查询是否已经存在item
		$existItem = UebModel::model('SuggestProductOther')->getItemByItemId( $item );
		
		$flagMarket = $this->saveEbayProductOther( $insertData,$existItem );
		
		//2.保存到oms系统
		$dayNum = ceil((time()-strtotime($online_time))/86400);
		$mthNum = ceil($dayNum/30);
		$sold_num_mth = ceil($sold_num_total/$mthNum); //月均销量
		$sold_num_day = floatval($sold_num_total/$dayNum); //日均销量
		$insertData['sold_num_mth'] = $sold_num_mth;
		$insertData['sold_num_day'] = $sold_num_day;
		$insertData['type'] = self::SOURCE_TYPE;
		
		$flagOms = $this->saveSuggestProduct( $insertData,$existItem );
		
	}
	
	/**
	 * @desc 保存ebay其他卖家item
	 * @param array $params
	 */
	public function saveEbayProductOther($params,$item){
		$tableName = self::tableName();
		if( $item ){
			$params['last_sync_time'] = $item['last_sync_time'];
			$flag = $this->dbConnection->createCommand()->update($tableName, $params ,' item_id = "'.$item['item_id'].'"');
		}else{
			$flag = $this->dbConnection->createCommand()->insert($tableName, $params);
			if($flag) {
				return $this->dbConnection->getLastInsertID();
			}
		}
		
		
		return false;
	}
	
	/**
	 * @desc 保存推荐item 到oms
	 * @param array $params
	 */
	public function saveSuggestProduct($params,$item){
		$tableName = SuggestProductOther::model()->tableName();
		if( $item ){
			$params['status'] = $item['status'];
			$params['last_sync_time'] = $item['last_sync_time'];
			$flag = SuggestProductOther::model()->dbConnection->createCommand()->update($tableName, $params ,' item_id = "'.$item['item_id'].'"');
			//更新前，先删除之前匹配的记录
			$flagDel = SuggestProductMatch::model()->deleteAll('item_id="'.$item['item_id'].'"');
			//计算匹配率，获取匹配率前n名的sku，保存到oms系统
			if( $flagDel ){
				$matchResult = SuggestProductMatch::model()->getSuggestProductMatch( $params['en_title'],5 );
				if( $matchResult && count($matchResult)>0 ){
					foreach( $matchResult as $value ){
						$modelMatch = new SuggestProductMatch();
						$modelMatch->setAttribute('item_id',$item['item_id']);
						$modelMatch->setAttribute('match_rate',$value['rate']);
						$modelMatch->setAttribute('sku',$value['sku']);
						$modelMatch->setAttribute('create_time',date('Y-m-d H:i:s'));
						$modelMatch->setIsNewRecord(true);
						$modelMatch->save();
					}
				}
			}
			
		}else{
			$params['status'] = 0;
			$flag = SuggestProductOther::model()->dbConnection->createCommand()->insert($tableName, $params);
			if($flag) {
				//计算匹配率，获取匹配率前n名的sku，保存到oms系统
				$matchResult = SuggestProductMatch::model()->getSuggestProductMatch( $params['en_title'],5 );
				if( $matchResult && count($matchResult)>0 ){
					foreach( $matchResult as $value ){
						$modelMatch = new SuggestProductMatch();
						$modelMatch->setAttribute('item_id',$params['item_id']);
						$modelMatch->setAttribute('match_rate',$value['rate']);
						$modelMatch->setAttribute('sku',$value['sku']);
						$modelMatch->setAttribute('create_time',date('Y-m-d H:i:s'));
						$modelMatch->setIsNewRecord(true);
						$modelMatch->save();
					}
				}
				return SuggestProductOther::model()->dbConnection->getLastInsertID();
			}
		}
		return false;
	}
	
	/**
	 * @desc 获取卖家产品总数量
	 * @param Integer $accountId
	 */
	public function getTotalItemNum( $accountId ){
		$ret = $this->dbConnection->createCommand()
				->select( 'count(id) as all_item_nums' )
				->from( $this->tableName() )
				->where( 'account_id = "'.$accountId.'"' )
				->queryRow();
		return $ret['all_item_nums'];
	}
	
	/**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->_exception;
	}
	
}