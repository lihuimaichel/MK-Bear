<?php
/**
 * @desc Ebay 运输国家屏蔽
 * @author lihy
 * @since 2016-06-30
 */
class EbayExcludeShipingCountry extends EbayModel{
    public $site_name;
    public $account_name;
    public $account_id;
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_exclude_shiping_country';
    }
    
    public function checkExistsBySiteId($siteId, $accountId, $filterId = null){
    	$row = $this->getDbConnection()->createCommand()
    	->from(self::tableName())
    	->select("id")
    	->where("site_id=:site_id and account_id=:account_id", array(":site_id"=>$siteId, ":account_id"=>$accountId))
    	->andWhere($filterId ? "id<>{$filterId}" : "1")
    	->queryRow();
    	if($row){
    		return true;
    	}
		return false;
    }
    
    /**
     * @desc 保存
     * @param Array $params
     */
    public function saveExcludeCountryData($data){
    	$tableName = self::tableName();
    	$flag = $this->dbConnection->createCommand()->insert($tableName, $data);
    	if($flag) {
    		return $this->dbConnection->getLastInsertID();
    	}
    	return false;
    }
    
    /**
     * @desc 更新保存
     * @param unknown $siteId
     * @param unknown $data
     * @return Ambigous <number, boolean>
     */
    public function updateExcludeCountryDataBySiteId($siteId, $accountId, $data){
    	$tableName = self::tableName();
    	return $this->dbConnection->createCommand()->update($tableName, $data, "site_id=:site_id and account_id=:account_id", array(":site_id"=>$siteId, ":account_id"=>$accountId));
    }
    
    
    public function updateExcludeCountryDataById($id, $data){
    	$tableName = self::tableName();
    	return $this->dbConnection->createCommand()->update($tableName, $data, "id=:id", array(":id"=>$id));
    }
    
    /**
     * @desc 根据站点ID获取屏蔽运送国家
     * @param unknown $siteId
     * @return mixed
     */
    public function getExcludeShipingCountry($siteId = 0, $accountId = null){
    	//@todo 如果以后换成每个站点，则不需要此代码了
//     	if($siteId != 77){
//     		$siteId = 0;
//     	}
    	$excludeCountry = 	$this->getDbConnection()->createCommand()
    							->from(self::tableName())
    							->select("country_code as exclude_ship_code, country_name as exclude_ship_name")
    							->where("site_id=:site_id", array(":site_id"=>$siteId))
    							->andWhere($accountId ? "account_id='{$accountId}'" : '1')
    							->queryRow();
    	return $excludeCountry;
    }
    
    /**
     * @desc 获取屏蔽国家
     * @param unknown $id
     * @return mixed
     */
    public function getExcludeShipingCountryByID($id){
    	$excludeCountry = 	$this->getDbConnection()->createCommand()
    	->from(self::tableName())
    	->select("country_code as exclude_ship_code, country_name as exclude_ship_name")
    	->where("id=:id", array(":id"=>$id))
    	->queryRow();
    	return $excludeCountry;
    }
    
    // ================== 前端显示 START ====================
    
    public function filterOptions(){
    	$siteID = Yii::app()->request->getParam('site_id');
    	return array(
    			array(
    					'name'		=>	'site_id',
    					'type'		=>	'dropDownList',
    					'data'		=>	EbaySite::getSiteList(),
    					'search'	=>	'=',
    					'value'		=>	$siteID
    			),
    			array(
    					'name'		=>	'account_id',
    					'type'		=>	'dropDownList',
    					'data'		=>	EbayAccount::getIdNamePairs(),
    					'search'	=>	'='
    			),
    			
    			
    	);
    }
    
    public function search(){
    	$csort = new CSort();
    	$csort->attributes = array(
    			'defaultOrder'=>'site_id'
    	);
    	$cdbCriteria = $this->setCdbCriteria();
    	$dataProvider = parent::search($this, $csort, '', $cdbCriteria);
    	$data = $this->additions($dataProvider->getData());
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    public function setCdbCriteria(){
    	$cdbCriteria = new CDbCriteria();
    	$cdbCriteria->select = "*";
    	return $cdbCriteria;
    }
    
    public function additions($datas){
    	if(empty($datas)) return $datas;
    	$siteList = EbaySite::getSiteList();
    	$accountList = EbayAccount::getIdNamePairs();
    		
    	foreach ($datas as &$data){
    		//获取站点
    		/* if($data['site_id'] == 77){
    			$data['site_name'] = "德国站点";
    		}else{
    			$data['site_name'] = "非德国站点";
    		} */

    		$data['site_name'] = isset($siteList[$data['site_id']]) ? $siteList[$data['site_id']] : '-';
    		$data['account_name'] = isset($accountList[$data['account_id']]) ? $accountList[$data['account_id']] : '-';
    	}
    	return $datas;
    }
    
    public function attributeLabels(){
    	return array(
    			'site_name'	=>	'站点名称',
    			'account_name'	=>	'账号',
    			'country_code'	=>	'国家代码',
    			'country_name'	=>	'国家名称',
    			'account_id'	=>	'账号',
    			'site_id'		=>	'站点'
    	);
    }
    // ================== 前端显示 END ======================
}