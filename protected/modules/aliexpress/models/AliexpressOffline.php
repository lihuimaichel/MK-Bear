<?php
/**
 * @desc Aliexpress产品下线
 * @author tony
 * @since 2015-09-21
 */ 

class AliexpressOffline extends AliexpressModel{

	public $account_id;
	public $account_name;
	
	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_log_offline';
    }
	
	/** @var int 账号ID*/
	protected $_accountID = null;
	
	/** @var int 产品ID*/
	protected $_productIds = null;
	
	/** @var int 产品ID*/
	protected $_skuId = null;
	
	/** @var int 产品ID*/
	protected $_ipmSkuStock = null;
	
	/**
	 * @desc 设置账号ID
	 */
	public function setAccountID($accountID){
		$this->_accountID = $accountID;
	}
	
	/**
	 * @desc 设置产品ID
	 */
	public function setProductID($productID){
		$this->_productIds = $productID;
	}
	
	/**
	 * @desc 设置账号ID
	 */
	public function setSku($sku){
		$this->$_skuId = $sku;
	}
	
	
	/**
	 * @desc 设置账号ID
	 */
	public function setIpmSkuStock($ipmSkuStock){
		$this->$_ipmSkuStock = $ipmSkuStock;
	}
	
	/**
	 * @desc 产品下架
	 * @return string
	 */
	public function AliexpressProductOffline(){
		//设置下架所需参数
		$accountID = $this->_accountID;
		$productID  = $this->_productIds;
		$request = new GetOfflineProductRequest();
		$request->setPrdouctID($productID);
		//执行下架
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		//根据返回信息更新数据库
		if( $request->getIfSuccess() ){
			$modifyCount = $response->modifyCount;
			$flag = AliexpressProduct::model()->saveOffline($productID);
			return array($modifyCount,$flag);
		}else{
			$this->setExceptionMessage($request->getErrorMsg());
			return false;
		}
		
	}
	
	/**
	 * @desc 调整SKU现在库存使其为0
	 * @return string
	 */
	public function AliexpressProductStockEdit(){
		//设置调整线上库存所需参数
		$accountID = $this->_accountID;
		$sku = $this->_skuId;
		$ipmSkuStock = $this->_ipmSkuStock;
		$productID = $this->_productIds;
		$request = new GetProductStockEditRequest();
		$request->setPrdouctID($productID);
		$request->setSkuID($sku);
		$request->setIpmSkuStock($ipmSkuStock);
		//执行调整库存
		$response = $request->setAccount($accountID)->setRequest()->sendRequest()->getResponse();
		//根据返回信息更新数据库
		if( $request->getIfSuccess() ){
			$modifyCount = $response->modifyCount;
			$flag = AliexpressProduct::model()->saveOffline($productID,$sku);
			return array($modifyCount,$flag);
		}else{
			$this->setExceptionMessage($request->getErrorMsg());
			return false;
		}
	}
	
	/**
	 * @desc 获取异常信息
	 * @return string
	 */
	public function getExceptionMessage(){
		return $this->exception;
	}
	
	/**
	 * @desc 设置异常信息
	 * @param string $message
	 */
	public function setExceptionMessage($message){
		$this->exception = $message;
	}

	/**
	 * 是否成功状态
	 */
	public function getStatus($status = null){
		$statusOptions = array(0=>'失败', 1=>'成功');
		if($status !== null){
			return isset($statusOptions[$status])?$statusOptions[$status]:'';
		}
		return $statusOptions;
	}

	/**
	 * 下架类型
	 */
	public function getEvent($event = null){
		$eventOptions = array('autoshelfproducts'=>'系统自动下架', 'batchoffline'=>'产品管理批量下架', 'offlineimport'=>'产品管理导入下线SKU', 'offline'=>'产品管理下线');
		if($event !== null){
			return isset($eventOptions[$event])?$eventOptions[$event]:'';
		}
		return $eventOptions;
	}

	// ============================= search ========================= //
    
    public function search(){
        $sort = new CSort();
        $sort->attributes = array('defaultOrder'=>'start_time');
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
        if(!empty($datas)){
            $accountLists = AliexpressAccount::model()->getIdNamePairs();
            foreach ($datas as &$data){
                //获取账号名称
                $data['account_name'] = isset($accountLists[$data['account_id']]) ? $accountLists[$data['account_id']] : ''; 
            }
        }
        return $datas;
    }

    
    public function filterOptions(){
        return array(
            array(
                    'name'=>'product_id',
                    'type'=>'text',
                    'search'=>'=',
                    'htmlOption'=>array(
                            'size'=>'22'
                    )
            ),

            array(
                    'name'=>'sku',
                    'type'=>'text',
                    'search'=>'LIKE',
                    'htmlOption' => array(
                            'size' => '22',
                    )
            ),
            
            array(
                    'name'      =>  'status',
                    'type'      =>  'dropDownList',
                    'value'     =>  Yii::app()->request->getParam('status'),
                    'data'      =>  $this->getStatus(),
                    'search'    =>  '=',
            ),

            array(
                    'name'      =>  'event',
                    'type'      =>  'dropDownList',
                    'value'     =>  Yii::app()->request->getParam('event'),
                    'data'      =>  $this->getEvent(),
                    'search'    =>  '=',
            ),

            array(
                    'name'=>'account_id',
                    'type'      =>  'dropDownList',
                    'data'      =>  AliexpressAccount::getIdNamePairs(),
                    'search'    =>  '=',
                    'htmlOption' => array(
                            'size' => '22',
                    )
            ),

            array(
                'name'          => 'start_time',
                'type'          => 'text',
                'search'        => 'RANGE',
                'htmlOptions'   => array(
                        'class'    => 'date',
                        'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
                ),
            ),

            array(
                'name'=>'message',
                'type'=>'text',
                'search'=>'LIKE',
                'htmlOption' => array(
                        'size' => '62',
                )
            ),
        );
    }
    
    public function attributeLabels(){
        return array(
            'sku'               =>  'SKU',
            'product_id'        =>  '产品ID',
            'operation_user_id' =>  '操作人员', 
            'start_time'        =>  '下架时间',
            'account_id'        =>  '账号',
            'message'           =>  '下架信息',
            'status'            =>  '是否成功',
            'event'             =>  '下架类型'
        );
    }
    
    // ============================= end search ====================//
	
}