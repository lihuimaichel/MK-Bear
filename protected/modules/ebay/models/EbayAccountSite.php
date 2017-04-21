<?php
/**
 * @desc Ebay账号站点关联表
 * @author lihy
 * @since 2015-06-06
 */
class EbayAccountSite extends EbayModel{
    
	
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_account_site';
    }
    /**
     * @desc 保存数据
     * @param unknown $data
     * @return string|boolean
     */
    public function saveData($data){
    	$res = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
    	if($res) return $this->getDbConnection()->getLastInsertID();
    	return false;
    }
    /**
     * @desc 删除数据
     * @param unknown $conditions
     * @param unknown $params
     */
    public function deleteData($conditions, $params = array()){
    	return $this->getDbConnection()->createCommand()->delete($this->tableName(), $conditions, $params);
    }
    
    /**
     * @desc 根据账号ID获取可用列表
     * @author Gordon
     */
    public function getAccountSiteListByAccountID($accountID){
        return $this->dbConnection->createCommand()
                       ->select('*')
                       ->from($this->tableName())
                       ->where('account_id=:account_id', array(':account_id'=>$accountID))
                       ->queryAll();
    }
    
    /**
     * @desc 根据站点获取对应的账号
     * @param unknown $siteID
     * @return mixed
     */
    public function getAccountSiteListBySiteID($siteID){
    	return $this->dbConnection->createCommand()
					    	->select('*')
					    	->from($this->tableName())
					    	->where('site_id=:site_id', array(':site_id'=>$siteID))
					    	->queryAll();
    }
    
    /**
     * @DESC 获取账号列表
     * @return mixed
     */
    public function getAccountSiteList(){
    	return $this->dbConnection->createCommand()
					    	->select('*')
					    	->from(self::model()->tableName())
					    	->queryAll();
    }
    
    /**
     * @desc 获取其中一个有效账号
     * @param unknown $siteID
     * @return boolean
     */
    public function getOneAbleAccountBySiteID($siteID){
    	return $this->dbConnection->createCommand()
				    	->select('a.*')
				    	->from($this->tableName() . " as t ")
				    	->leftJoin(EbayAccount::model()->tableName() . " as a", "a.id=t.account_id")
				    	->where('t.site_id=:site_id', array(':site_id'=>$siteID))
				    	->andWhere('a.status=1')
				    	->queryRow();
    	
    }
    
    /**
     * @desc 获取可用的账号列表
     * @param unknown $siteID
     * @return mixed
     */
    public function getAbleAccountListBySiteID($siteID){
    	return $this->dbConnection->createCommand()
    	->select('a.*')
    	->from(self::model()->tableName() . " as t ")
    	->leftJoin(EbayAccount::model()->tableName() . " as a", "a.id=t.account_id")
    	->where('t.site_id=:site_id', array(':site_id'=>$siteID))
    	->andWhere('a.status=1')
    	->queryAll();
    }
    
    /**
     * @desc 获取可用的账号，排除掉sku的
     * @param unknown $siteID
     * @param unknown $sku
     * @return mixed
     */
    public function getAbleAccountInfoListBySiteIdAndSku($siteID, $sku){
    	$accountTable = EbayAccount::model()->tableName();
    	$accountList = $this->dbConnection->createCommand()
							    	->select('a.*')
							    	->from($this->tableName() . ' as t')
							    	->leftJoin($accountTable . ' as a', 'a.id=t.account_id')
							    	->where('t.site_id=:site_id', array(':site_id'=>$siteID))
							    	->queryAll();
    	//检测sku是否在对应账号下发布过
    	$ebayProductModel = new EbayProduct();
    	foreach ($accountList as $key=> $account){
    		$accountList[$key]['is_upload'] = false;
    		if($ebayProductModel->find("account_id=:account_id AND sku=:sku and site_id=:site_id", array(':account_id'=>$account['id'], ':sku'=>$sku,':site_id'=>$siteID))){
    			$accountList[$key]['is_upload'] = true;
    		}
    	}
    	
    	
    	return $accountList;
    }
    
    /**
     * @desc 根据站点账号获取对应的仓库ID
     * @param unknown $accountID
     * @param unknown $siteID
     * @return Ambigous <string, mixed, unknown>
     */
    public function getWarehouseByAccountSite($accountID, $siteID){
    	return $this->getDbConnection()->createCommand()
    							->select('warehouse_id')
    							->from($this->tableName())
    							->where("account_id=:account_id AND site_id=:site_id", array(':account_id'=>$accountID, ':site_id'=>$siteID))
    							->queryScalar();
    }
    
    // ===================== 站点对应关系 =================
    public function attributeLabels(){
    	return array(
    			'id'             =>Yii::t('system', 'No.'),
    			'email'          =>Yii::t('system', 'Email'),
    			'user_name'      =>Yii::t('system', 'user_name'),
    			'store_name'     =>Yii::t('system', 'store_name'),
    			'short_name'	 =>Yii::t('system', 'short_name'),
    			'use_status'     =>Yii::t('system', 'use_status'),
    			'frozen_status'  =>Yii::t('system', 'frozen_status'),
    			'group_id'       =>Yii::t('system', 'Group Id'),
    			'group_name'     =>Yii::t('system', 'Group Name'),
    			'add_qty'		 =>Yii::t('system', 'Publish Count'),
    			'auto_revise_qty'=>Yii::t('system', 'IfAdjust Count'),
    			'relist_qty'  	 =>	Yii::t('ebay', 'Relist Count'),
    			'is_eub'		 =>	Yii::t('ebay', 'Is EUB'),
    			'is_eub_under5'	 =>	Yii::t('ebay', 'Is EUB Under 5 Dollar'),
    			'update_eub'	 =>	Yii::t('ebay', 'Whether EUB price has been Changed'),
    			'is_restrict'	=>	Yii::t('ebay', 'Whether is limited permanent'),
    			'is_free_shipping'	=>	Yii::t('ebay', 'Whether is free shipping'),
    	);
    }
    
    public function filterOptions(){
    	$result = array(
    			array(
    					'name'      => 'user_name',
    					'type'      => 'text',
    					'search'    => 'LIKE',
    					'alias'     => 't',
    			)
    	);
    	return $result;
    }
    
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array('defaultOrder'=>'t.id');
    	$dataProvider = parent::search("EbayAccount", $sort, array(),$this->_setCDbCriteria());
    	$data = $this->addition($dataProvider->data);
    	$dataProvider->setData($data);
    	return $dataProvider;
    }
    
    protected function _setCDbCriteria(){
    	$criteria = new CDbCriteria;
    	$criteria->order = 't.id';
    	return $criteria;
    }
    
    public function addition($datas){
    	$ebaySiteModel = new EbaySite();
    	$ebayAccountSiteModel = new EbayAccountSite();
    	foreach ($datas as $key=>$data){
    		//获取对应的站点和仓库设置信息
    		$accountSiteList = $ebayAccountSiteModel->getAccountSiteListByAccountID($data['id']);
    		//拼装html
    		$html = "";
    		if($accountSiteList){
    			foreach ($accountSiteList as $accountSite){
    				$html .= '';
    			}
    		}
    		$data['accountSiteList'] = $accountSiteList;
    		$datas[$key] = $data;
    	}
    	return $datas;
    }
    
    // ===================== 站点对应关系 =================
}