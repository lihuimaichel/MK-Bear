<?php
/**
 * @desc Ebay刊登拍卖信息model
 * @author lihy
 * @since 2016-03-28
 */
class EbayProductAddAuction extends EbayModel{
	public $account_id;
	public $site_id;
	public $sku;
	public $account_name;
	public $site_name;
	public $auction_status_text;
	
	const AUCTION_STATUS_ON = 1;
	const AUCTION_STATUS_OFF = 0;
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_add_auction';
    }

    /**
     * @desc 根据刊登ID获取拍卖信息
     * @param unknown $addID
     * @return mixed
     */
    public function getAuctionInfoByAddID($addID){
    	return $this->getDbConnection()->createCommand()
    								->from($this->tableName())
    								->where("add_id=".$addID)
    								->queryRow();
    }
    
    /**
     * @desc 检查是否存在对应的拍卖纪录
     * @param unknown $conditions
     * @param unknown $param
     * @return boolean
     */
    public function checkAuctionExistsByCondition($conditions, $param = array()){
    	$result = $this->getDbConnection()->createCommand()
			    	->select('id')
			    	->from($this->tableName())
			    	->where($conditions, $param)
			    	->queryRow();
    	if($result) return true;
    	return false;
    }
    
    /**
     * @desc 设置拍卖状态
     * @param unknown $id
     * @param unknown $status
     * @return Ambigous <number, boolean>
     */
    public function setAuctionStatus($id, $status){
    	if(!is_array($id)){
    		$ids = array($id);
    	}else{
    		$ids = $id;
    	}
    	$userID = Yii::app()->user->id;
    	$data = array(
    			'auction_status'	=>	$status,
    			'update_time'		=>	date("Y-m-d H:i:s"),
    			'update_user_id'	=>	$userID
    	);
    	return $this->getDbConnection()->createCommand()->update($this->tableName(), $data, "id in(".MHelper::simplode($ids).")");
    }
    
    /**
     * @desc 获取待添加刊登的 每个只能循环刊登三次
     * @return mixed
     */
    public function getProductAuctionPenddingAddList(){
    	$ebayProductAddModel = EbayProductAdd::model();	
    	return $this->getDbConnection()
    						->createCommand()
		    				->from($this->tableName() . " t")
		    				->select('p.site_id, p.account_id, p.sku, p.auction_rule, p.status, t.plan_day, t.start_time, t.end_time, t.update_time, t.id, t.add_id, t.count')
		    				->join($ebayProductAddModel->tableName() . " p", "p.id=t.add_id")
		    				->where("p.status=".EbayProductAdd::STATUS_SUCCESS . " and t.plan_day>0 and t.count<3 and t.pid=0 and t.end_time>now() and t.auction_status=1")
		    				->order("t.update_time asc")
		    				->limit(100)	//max limit 1000
		    				->queryAll();
    }
    
    /**
     * @desc 保存数据
     * @param unknown $data
     * @return string|boolean
     */
    public function saveData($data){
    	$res = $this->getDbConnection()
    				->createCommand()
    				->insert($this->tableName(), $data);
    	if($res){
    		return	$this->getDbConnection()->getLastInsertID();
    	}
    	return false;
    }
    
    /**
     * @desc 根据主键ID更新数据
     * @param unknown $data
     * @param unknown $id
     * @return Ambigous <number, boolean>
     */
    public function updateDataByID($data, $id){
    	return $this->getDbConnection()
    				->createCommand()
    				->update($this->tableName(), $data, "id=".$id);
    }
    
    // ==================== search ==========================//
    
    public function search(){
    	$csort = new CSort();
    	$csort->attributes = array(
    		'defaultOrder'	=>	'id'
    	);
    	$CDbCriteria = new CDbCriteria();
    	$CDbCriteria->select = "t.*,p.sku, p.account_id, p.site_id";
    	$CDbCriteria->join = "inner join ".EbayProductAdd::model()->tableName()." as p on p.id=t.add_id";
    	
    	$dataProvider = parent::search($this, $csort, '', $CDbCriteria);
		$data = $dataProvider->getData();
		$data = $this->additions($data);
		$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    private function additions($datas){
    	if($datas){
    		$accountPairs = EbayAccount::model()->getIdNamePairs();
    		$siteNames = EbaySite::getSiteList();
    		foreach ($datas as &$data){
    			//获取账号
    			$data['account_name'] = isset($accountPairs[$data['account_id']])?$accountPairs[$data['account_id']]:'-';
    			//获取站点
				$data['site_name']	= isset($siteNames[$data['site_id']])?$siteNames[$data['site_id']]:'-'; 			
    			//状态
    			$data['auction_status_text'] = $data['auction_status'] ? '是':'否';
    			
    		}
    	}
    	return $datas;
    }
    
    
    public function attributeLabels(){
    	return array(
    				'account_name'	=>	Yii::t('ebay', 'Account Name'),
    				'site_name'		=>	Yii::t('ebay', 'Site Name'),
    				'sku'			=>	'SKU',
    				'plan_day'		=>	Yii::t('ebay', 'Listing Duration Auction'),
    				'start_time'	=>	Yii::t('ebay', 'Auction Start Time'),
    				'account_id'	=>	Yii::t('ebay', 'Account Name'),
    				'site_id'		=>	Yii::t('ebay', 'Site Name'),
    				'auction_status_text'	=>	Yii::t('ebay', 'Auction Automatic Cycle'),
    				
    	);
    }
    
   	public function filterOptions(){
   		return array(
   					array(
    						'name'		=>	'sku',
    						'type'		=>	'text',
    						'search'	=>	'=',
   							'alis'		=>	'p'
    				),
	   				array(
    					'name' => 'account_id',
    					'type' => 'dropDownList',
    					'data' => EbayAccount::model()->getIdNamePairs(),
    					'search' => '=',
	   					'alis'		=>	'p'
    				),
	   				array(
	   						'name' => 'site_id',
	   						'type' => 'dropDownList',
	   						'data' => EbaySite::getSiteList(),
	   						'search' => '=',
	   						'alis'		=>	'p'
	   				),
   		);
   	}
    // ==================== end search ====================//
    
}