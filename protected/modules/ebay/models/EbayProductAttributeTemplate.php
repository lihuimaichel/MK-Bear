<?php
/**
 * @desc Ebay刊登属性模板
 * @author lihy
 * @since 2016-03-28
 */
class EbayProductAttributeTemplate extends EbayModel{
	
	public $config_type_name = null;
	public $site_name = null;
	public $time_zone_prefix = null;
	public $abroad_warehouse_name;
	public $special_country = array(
			'SPECIAL_AREA_2',
			'SPECIAL_AREA_ukau',
			'SPECIAL_AREA_1',
	);
	
	public function rules(){
		return array(
				array('site_id,name,condition_id,dispatch_time_max,country,location', 'required'),
				array('site_id,name,condition_id,dispatch_time_max,country,location,listing_duration,
						listing_duration_auction,returns_accepted_option,refund_option,returns_within_option,return_description,
						shipping_cost_option,auction_price,auction_hotsell_price,time_zone,abroad_warehouse,config_type,
						opration_id,opration_date', 'safe'),
		);	
	}
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_attribute_template';
    }
    
    /**
     * @desc 根据addID获取ebay刊登产品信息
     * @param unknown $addID
     * @return mixed
     */
    public function getEbayAttributeTemplateBySiteID($siteID, $field = null){
    	$attribute = $this->getDbConnection()->createCommand()->from($this->tableName())
    							->where("site_id=:site_id", array(':site_id'=>$siteID))
    							->queryRow();
    	if($attribute && $field){
    		return isset($attribute[$field]) ? $attribute[$field] : '';
    	}
    	return $attribute;
    }

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from($this->tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }      
   
    /**
     * @desc 获取属性模板
     * @param unknown $conditions
     * @return mixed
     */
    public function getProductAttributeTemplage($conditions){
    	$attribute = $this->getDbConnection()->createCommand()
    										->from($this->tableName())
									    	->where($conditions)
									    	->queryRow();
    	return $attribute;
    }

    /**
     * @desc 批量删除
     * @param unknown $ids
     * @return boolean
     */
    public function batchDelete($ids){
    	if(is_array($ids)){
    		$ids = implode(",", $ids);
    	}
    	try{
    		$transaction = $this->getDbConnection()->beginTransaction();
    		$this->getDbConnection()->createCommand()->delete($this->tableName(), "id in(".$ids.")");
    		//删除下面的对应的运输模板
    		EbayProductShippingTemplate::model()->delShippingTemplateByPIDs($ids);
    		$transaction->commit();
    		return true;
    	}catch(Exception $e){
    		$transaction->rollback();
    		return false;
    	}
    }
    // ========================== 搜索START ==============================
    
    public function search(){
    	$csort = new CSort();
    	$csort->attributes = array('defaultOrder'=>'id');
    	$dataPrivider = parent::search($this, $csort, '', $this->setCDbCriteria());
    	$data = $this->additions($dataPrivider->getData());
    	$dataPrivider->setData($data);
    	return $dataPrivider;
    }
    
    public function setCDbCriteria(){
    	$CDbCriteria = new CDbCriteria();
    	$CDbCriteria->select = "t.*";
    	return $CDbCriteria;
    }
    
    public function additions($datas){
    	$configType = EbayProductAdd::getConfigType();
    	$wareHouseList = Warehouse::model()->getWarehousePairs();
    	if($datas){
    		foreach ($datas as $key=>$data){
    			//获取站点名称
    			$data['site_name'] = EbaySite::getSiteName($data['site_id']);
    			//获取分组名称
    			$data['config_type_name'] = isset($configType[$data['config_type']])? $configType[$data['config_type']] : '默认';
    			$data['abroad_warehouse_name'] = isset($wareHouseList[$data['abroad_warehouse']])? $wareHouseList[$data['abroad_warehouse']] : '默认';
    			$datas[$key] = $data;
    		}
    	}
    	return $datas;
    }
    
    public function filterOptions(){
    	return array(
    				array(
							'name' => 'site_id',
							'type' => 'dropDownList',
							'data' => EbaySite::getSiteList(),
							'search' => '=',
					),
					array(
							'name' => 'name',
							'type' => 'text',
							'search' => '=',
					),
    	);
    }
    
    public function attributeLabels(){
    	return array(
    				'site_id'			=>		Yii::t('ebay', 'Site Name'),
    				'site_name'			=>		Yii::t('ebay', 'Site Name'),
    				'name'				=>		Yii::t('ebay', 'Attribute Template Name'),
    				'config_type'		=>		Yii::t('ebay', 'Config Type Name'),
    				'config_type_name'	=>		Yii::t('ebay', 'Config Type Name'),
    				'abroad_warehouse'	=>		Yii::t('ebay', 'Abroad Warehouse'),
    				'listing_duration'	=>		Yii::t('ebay', 'Listing Duration'),
    				'country'			=>		Yii::t('ebay', 'Country'),
    				'location'			=>		Yii::t('ebay', 'Location'),
    				'dispatch_time_max'	=>		Yii::t('ebay', 'Dispath Time Max'),
    				'listing_duration_auction'	=>	Yii::t('ebay', 'Listing Duration Auction'),
    				'condition_id'		=>		Yii::t('ebay', 'Condition Id'),
    				'returns_accepted_option'	=>	Yii::t('ebay', 'Returns Accepted Option'),
    				'refund_option'		=>		Yii::t('ebay', 'Refund Option'),
    				'returns_within_option'	=>	Yii::t('ebay', 'Returns Within Option'),
    				'shipping_cost_option'	=>	Yii::t('ebay', 'Shipping Cost Option'),
    				'return_description'	=>	Yii::t('ebay', 'Return Description'),
    				'auction_price'			=>	Yii::t('ebay', 'Auction Price'),
    				'auction_hotsell_price'	=>	Yii::t('ebay', 'Auction Hotsell Price'),
    				'time_zone'				=>	Yii::t('ebay', 'Time Zone'),
    				'time_zone_prefix'		=>	Yii::t('ebay', 'time Zone Prefix')
    			
    	);
    }
    // ========================== 搜索END ===========================
}