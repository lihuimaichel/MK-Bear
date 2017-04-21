<?php
class WishListingHoldedOffline extends WishModel {

	public $account_name;
	
	public function tableName(){
		return  'ueb_wish_listing_holded_offline';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	/**
	 * @desc 根据自增ID更新单个数据
	 * @param int $id
	 * @param array $updata
	 * @return boolean
	 */
	public function updateInfoByID($id, $updata){
		if(empty($id) || empty($updata)) return false;
		$conditions = "id = ".$id;
		return $this->getDbConnection()->createCommand()->update($this->tableName(), $updata, $conditions);
	}

	/**
	 * @desc 根据条件更新列表
	 * @param string $condition
	 * @param array $updata
	 * @return boolean
	 */
	public function updateListByCondition($conditions, $updata){
		if(empty($conditions) || empty($updata)) return false;
		return $this->getDbConnection()->createCommand()
				    ->update($this->tableName(), $updata, $conditions);
	}	

	/**
	 * 根据条件获取单条数据
	 */
	public function getInfoByCondition($where) {
		if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where($where)
					->limit(1)
                    ->queryRow();
	}

	/**
	 * 根据条件获取列表
	 */
	public function getListByCondition($where) {
		if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where($where)
                    ->queryAll();
	}

	/**
	 * 更新拦截的数据
	 */
	public function setHoldedInfo($data){
		if(empty($data)) return false;
		$ret = false;

		//待处理的listing数据
		$info = $this->getInfoByCondition("account_id = ".$data['account_id']." AND sku ='".$data['sku']."' AND type = ".$data['type']." AND status = 0");
		if ($info){			
			$ret = $this->updateInfoByID("id = ".$info['id'], array('create_time' => $data['create_time'],'times' => $info['times'] + 1));	//更新拦截时间和拦截次数
		}else{
			$ret = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
		}
		return $ret;
	}	

    
    /**
     * @desc 批量更新
     * @param unknown $ids
     * @return boolean|Ambigous <number, boolean>
     */
    public function updateByIds($ids){
    	if(empty($ids)) return false;
    	if(!is_array($ids)){
    		$ids = array($ids);
    	}
    	return $this->getDbConnection()
    		->createCommand()
    		->update($this->tableName(), array('status' => 1), "id in(". MHelper::simplode($ids) .")");
    }	


   // ============================= search ========================= //
    
    public function search(){
    	$sort = new CSort();
    	$sort->attributes = array('defaultOrder'=>'t.sku');
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
    	$cdbcriteria->select = 't.*';   	
    	
    	return $cdbcriteria;
    }
    
    private function _additions($datas){
    	if(!empty($datas)){
    		$wishAccountList = UebModel::model("WishAccount")->getIdNamePairs();
    		foreach ($datas as $data){
				$data['account_name'] = isset($wishAccountList[$data['account_id']]) ? $wishAccountList[$data['account_id']] : '';
				$data['total']        = ($data['type'] == 1) ? '销量：'.intval($data['total']) : '总金额：$'.$data['total'];
				$data['type']         = ($data['type'] == 1) ? '过去7天销量' : '过去九天订单总额';
				$data['status']       = ($data['status'] == 1) ? '已处理' : '待处理';				
				$data['update_time']  = ($data['update_time'] == '0000-00-00 00:00:00') ? '' : $data['update_time'];
    		}
    	}
    	return $datas;
    }
    
    
    public function filterOptions(){
    	return array(
    			array(
    					'name'		=>	'account_id',
    					'type'		=>	'dropDownList',
    					'search'	=>	'=',
    					'data'		=>	UebModel::model("WishAccount")->getIdNamePairs(),
    					'htmlOption'=>array(
    							'size'=>'22'
    					)
    			),    		
    			array(
    					'name'=>'sku',
    					'type'=>'text',
    					'search'=>'=',
    					'htmlOption' => array(
    							'size' => '22',
    					)
    			),
    			array(
    					'name'=>'product_id',
    					'type'=>'text',
    					'search'=>'=',
    					'htmlOption'=>array(
    							'size'=>'22'
    					)
    			),  
    			array(
    					'name'		=>	'type',
    					'type'		=>	'dropDownList',
    					'data'		=>	array('1' => '过去7天销量>=25', '2' => '过去九天订单总额>=$500'),
    					'search'	=>	'=',
    			
    			),
    			array(
    					'name'		=>	'status',
    					'type'		=>	'dropDownList',
    					'data'		=>	array('0' => '待处理', '1' => '已处理'),
    					'search'	=>	'=',
    			
    			),
    	);
    }
    
    
    public function attributeLabels(){
    	return array(
				'account_id'  =>	'账号',
				'sku'         =>	'SKU',    			 
				'product_id'  =>	'ProductID',		
				'total'       =>	'统计总数',		
				'type'        =>	'类型',				
				'status'      =>	'状态',
				'create_time' =>	'拦截时间',
				'update_time' =>	'处理时间',
				'times'       =>	'拦截次数',    			
    	);
    }	

}

?>