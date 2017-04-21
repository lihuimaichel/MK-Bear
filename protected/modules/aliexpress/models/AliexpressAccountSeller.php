<?php
/**
 * @desc 速卖通账号和用户关系表
 * @author hanxy
 * @since 2016-11-04
 */ 

class AliexpressAccountSeller extends AliexpressModel{

	public $account_name;
	
	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_account_seller';
    }


    /**
	 * [getListByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return [type]         [description]
	 * @author yangsh
	 */
	public function getListByCondition($fields='*', $where='1',$order='') {
		$accountIdArr = array();
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		$accountSellerList = $cmd->queryAll();
		if($accountSellerList){
			foreach ($accountSellerList as $sellerList) {
				$accountIdArr[] = $sellerList['account_id'];
			}
		}

		return $accountIdArr;
	}


	// ============================= search ========================= //
    
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array('defaultOrder'=>'id');
    	$dataProvider = parent::search($this, $sort, '', $this->_setdbCriteria());
    	$dataProvider->setData($this->_additions($dataProvider->data));
    	return $dataProvider;
    }
    /**
     * @desc  设置条件
     * @return CDbCriteria
     */
    private function _setdbCriteria(){
    	$cdbcriteria = new CDbCriteria();
    	$cdbcriteria->select = '*';
    	
    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if($datas){
    		$aliexpressAccountList = UebModel::model("AliexpressAccount")->getIdNamePairs();
    		foreach ($datas as &$data){
    			$data['account_name'] = isset($aliexpressAccountList[$data['account_id']]) ? $aliexpressAccountList[$data['account_id']] : '';
    		}
    	}
    	return $datas;
    }
    
    
    public function filterOptions(){
    	return array(
    			array(
    					'name'=>'account_id',
    					'type'=>'dropDownList',
    					'search'=>'=',
    					'data'	=>	UebModel::model("AliexpressAccount")->getIdNamePairs(),
    					'htmlOption'=>array(
    							'size'=>'22'
    					)
    			),
    			
    			array(
    					'name'		=>	'seller_user_id',
    					'type'		=>	'dropDownList',
    					'data'		=>	User::model()->getUserNameByDeptID(array(4, 25)),
    					'search'	=>	'=',
    			),
    	);
    }
    
    
    public function attributeLabels(){
    	return array(    			 
			'account_id'	 => '账号',
			'seller_user_id' => '销售人员',
			'create_time'	 => '设置时间'			
    	);
    }
    
    // ============================= end search ====================//
    

    /**
     * @desc 页面的跳转链接地址
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/aliexpress/aliexpressaccountseller/list');
    }
	
}