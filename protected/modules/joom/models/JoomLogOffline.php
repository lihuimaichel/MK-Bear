<?php
/**
 * @desc Joom产品下线日志
 * @author hanxy
 * @since 2017-02-09
 */ 

class JoomLogOffline extends JoomModel{

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
		return 'ueb_joom_log_offline';
	}

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


    /**
	 * [getListByCondition description]
	 * @param  string $fields [description]
	 * @param  string $where  [description]
	 * @param  mixed $order  [description]
	 * @return [type]         [description]
	 * @author yangsh
	 */
	public function getListByCondition($fields='*', $where='1',$order='',$group='') {
		$cmd = $this->dbConnection->createCommand();
		$cmd->select($fields)
			->from(self::tableName())
			->where($where);
		$group != '' && $cmd->group($group);
		$order != '' && $cmd->order($order);
		return $cmd->queryAll();
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
		$eventOptions = array('autoofflineproducts'=>'系统自动下架');
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
            $accountLists = JoomAccount::model()->getIdNamePairs();
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
                    'data'      =>  JoomAccount::getIdNamePairs(),
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