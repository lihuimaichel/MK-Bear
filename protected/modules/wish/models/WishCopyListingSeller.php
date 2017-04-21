<?php
/**
 * @desc 复制刊登权限用户表
 * @author hanxy
 * @since 2016-11-21
 */ 

class WishCopyListingSeller extends WishModel{
	
	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_wish_copy_listing_seller';
    }


    /**
     * [getoneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return [type]         [description]
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $SellerList = $cmd->queryRow();
        
        return $SellerList;
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
		$sellerIdArr = array();
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$order != '' && $cmd->order($order);
		$SellerList = $cmd->queryAll();
		if($SellerList){
			foreach ($SellerList as $seller) {
				$sellerIdArr[] = $seller['seller_user_id'];
			}
		}

		return $sellerIdArr;
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
    	return $datas;
    }
    
    
    public function filterOptions(){
    	return array(    			
    			array(
    					'name'		=>	'seller_user_id',
    					'type'		=>	'dropDownList',
    					'data'		=>	User::model()->getWishUserList(),
    					'search'	=>	'=',
    			),
    	);
    }
    
    
    public function attributeLabels(){
    	return array(    		 
			'seller_user_id' => '销售人员',
			'create_time'	 => '设置时间'			
    	);
    }
    
    // ============================= end search ====================//
    

    /**
     * @desc 页面的跳转链接地址
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/wish/wishcopylistingseller/list');
    }
	
}