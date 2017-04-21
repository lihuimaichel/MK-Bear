<?php

class AuthAssignment extends UsersModel
{
	const ROLE_PURCHASE 		= 'purchaser';//跟单人
	const EBAY_USER				='ebay_user';	  //市场专员
	const PRODUCT_DEVELOPERS	='product_developers';//产品开发人员
	const PURCHASE_PRICE_USERS	='purchase_price_user';//成本人格化人员	
	const ROLE_WAREHOUSE_PEOPLE = 'inventory_people'; //盘点人
	const ROLE_ACCOUNTING 		= 'accounting'; //会计	
	const ROLE_CASHIER 			= 'cashier'; //出纳	
	const ROLE_RECIEVE_PACK 	= 'recieve_pack_user';//物流部收货包装
	const ROLE_RECIEVE_CONFIRM	= 'recieve_confirm_user';//物流部收货人
    CONST ROLE_AD_CODE          = 'ad_code';              //美工
    CONST ROLE_PH_CODE          = 'ph_code';              //摄影

	
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ueb_auth_assignment';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{	
		return array();
	}
	

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array();
	} 
     
    
    /**
     * get user list
     *
     * @param type $roleId
     * * @$type >0 return mutlit array('userid'=>'user_name');
     * @return type
     */
    public static function getUlist($roleId,$type=0) {
   
    	$data = array();
    	$joinTable = User::model()->tableName();
    	$selectObj = Yii::app()->db->createCommand()
    	->select('*')
    	->from(self::tableName().' a')
    	->join( $joinTable .' u', "a.`userid` = u.id")
    	->where("u.user_status = 1");
    	if ( is_array($roleId) ) {
    		$selectObj->andWhere(array('In', 'itemname', $roleId));
    	} else {
    		$selectObj->andWhere(" itemname = '{$roleId}'");
    	}
    	$data = $selectObj->queryAll();
    	if($type){
    		if($data){
    			$arr = $data;
    			unset($data);
//     			$data = array(''=>Yii::t('system','Please Select'));
    			$data = array();
    			foreach($arr as $key=>$val){
    				$data[$val['userid']] =$val['user_full_name'];
    			}
    			unset($arr);
    		}
    	}
    	return $data;
    }
    
    /**
     * get user list
     * 
     * @param type $roleId
     * @return type
     */
    public static function getUserIdsByRoleId($roleId) {
        $joinTable = User::model()->tableName();
        $selectObj = Yii::app()->db->createCommand() 
			->select('u.id')
			->from(self::tableName().' a')	
            ->join( $joinTable .' u', "a.`userid` = u.id")
            ->where("u.user_status = 1");     
        if ( is_array($roleId) ) {
            $selectObj->andWhere(array('In', 'itemname', $roleId));  
        } else {
            $selectObj->andWhere(" itemname = '{$roleId}'");
        }        
        $list = $selectObj->order("u.user_name Asc")
			->queryColumn();  
        
        return $list;
    }


    /**
     * 通过用户ID查询itemname
     * @param  integer $userid 用户ID
     * @param  string  $platformCode 平台code
     * @return array
     */
    public function checkPlatformByUserIdAndPlatformCode($userid, $platformCode){
        $selectObj = $this->getDbConnection()->createCommand() 
            ->select('itemname')
            ->from(self::tableName())  
            ->where('userid=:userid', array(':userid'=>$userid))
            ->queryAll();

        $result = false;
        if($selectObj){
            foreach ($selectObj as $m => $n) {
            	$itemNameArray[] = strtolower($n['itemname']);
            }
            //ebay转换
            if($platformCode == Platform::CODE_EBAY){
                $platformCode = 'ebay';
            }

            //wish转换
            if ($platformCode == Platform::CODE_WISH) {

                $platformCode = 'wish';
            }

            //joom转换
            if ($platformCode == Platform::CODE_JOOM) {

                $platformCode = 'joom';
            }

            $platformCode = strtolower($platformCode);
            $platformCodeLeader = $platformCode.'_leader';
            $platformCodeGroupLeader = $platformCode.'_groupleader';
            if(in_array($platformCodeLeader, $itemNameArray) || in_array($platformCodeGroupLeader, $itemNameArray)){
                $result = true;
            }
        }
        return $result;
    }
    
    /**
     * @desc 检测当前用户是否主管身份
     * @param unknown $sellerId
     * @param unknown $platformCode
     * @return boolean
     */
    public function checkCurrentUserIsAdminister($sellerId, $platformCode){
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('itemname')
    	->from(self::tableName())
    	->where('userid=:userid', array(':userid'=>$sellerId))
    	->andWhere(empty($platformCode) ? "itemname like '%_leader'" : '1')
    	->queryAll();
    	
    	$result = false;
    	if(empty($platformCode)){
    		return empty($selectObj) ? false : true;
    	}
    	if($selectObj){
    		foreach ($selectObj as $m => $n) {
    			$itemNameArray[] = strtolower($n['itemname']);
    		}
    	
    		//ebay转换
    		if($platformCode == Platform::CODE_EBAY){
    			$platformCode = 'ebay';
    		}
    	
    		//wish转换
    		if ($platformCode == Platform::CODE_WISH) {
    	
    			$platformCode = 'wish';
    		}
    	
    		//joom转换
    		if ($platformCode == Platform::CODE_JOOM) {
    	
    			$platformCode = 'joom';
    		}
    	
    		$platformCode = strtolower($platformCode);
    		$platformCodeLeader = $platformCode.'_leader';
    		if(in_array($platformCodeLeader, $itemNameArray)){
    			$result = true;
    		}
    	}
    	return $result;
    }
    
    /**
     * @desc 检测当前用户是否组长身份
     * @param unknown $sellerId
     * @param unknown $platformCode
     * @return boolean
     */
    public function checkCurrentUserIsGroup($sellerId, $platformCode){
    	$selectObj = $this->getDbConnection()->createCommand()
    	->select('itemname')
    	->from(self::tableName())
    	->where('userid=:userid', array(':userid'=>$sellerId))
    	->andWhere(empty($platformCode) ? "itemname like '%_groupleader'" : '1')
    	->queryAll();
    	 
    	$result = false;
    	if(empty($platformCode)){
    		return empty($selectObj) ? false : true;
    	}
    	 
    	if($selectObj){
    		foreach ($selectObj as $m => $n) {
    			$itemNameArray[] = strtolower($n['itemname']);
    		}
    		 
    		//ebay转换
    		if($platformCode == Platform::CODE_EBAY){
    			$platformCode = 'ebay';
    		}
    		 
    		//wish转换
    		if ($platformCode == Platform::CODE_WISH) {
    			 
    			$platformCode = 'wish';
    		}
    		 
    		//joom转换
    		if ($platformCode == Platform::CODE_JOOM) {
    			 
    			$platformCode = 'joom';
    		}
    		 
    		$platformCode = strtolower($platformCode);
    		$platformCodeGroupLeader = $platformCode.'_groupleader';
    		if(in_array($platformCodeGroupLeader, $itemNameArray)){
    			$result = true;
    		}
    	}
    	return $result;
    }
}