<?php
/**
 * @desc Aliexpress修改价格记录表
 * @author hanxy
 * @since 2017-03-08
 */ 

class AliexpressLogUpdatePrice extends AliexpressModel{

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
        return 'ueb_aliexpress_log_update_price';
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
		$eventOptions = array('batcheditprice'=>'按折扣产品批量改价', 'editprice'=>'修改价格');
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
                //折扣
                $data['discount'] = !empty($data['discount'])?$data['discount'].'%':'';
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
            'start_time'        =>  '修改时间',
            'account_id'        =>  '账号',
            'message'           =>  '修改信息',
            'status'            =>  '是否成功',
            'event'             =>  '事件类型',
            'old_price'         =>  '修改前价格',
            'update_price'      =>  '修改后价格',
            'discount'          =>  '价格折扣'
        );
    }
    
    // ============================= end search ====================//
    

    /**
     * @desc 存储日志
     * @param string $eventName
     * @param array $param
     */
    public function savePrepareLog($param){
    	$result = false;
        $flag = $this->dbConnection->createCommand()->insert(self::tableName(), $param);
        if( $flag ){
            $result = $this->dbConnection->getLastInsertID();
        }
        return $result;
    }
	
}