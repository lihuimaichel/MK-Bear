<?php
/**
 * @desc amazon产品下线
 * @author hanxy
 * @since 2016-11-03
 */ 

class AmazonOffline extends AmazonModel{

	public $account_name;
	
	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_amazon_log_offline';
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
            $accountLists = AmazonAccount::model()->getIdNamePairs();
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
                    'name'=>'listing_id',
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
                    'name'=>'account_id',
                    'type'      =>  'dropDownList',
                    'data'      =>  AmazonAccount::getIdNamePairs(),
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
        );
    }
    
    public function attributeLabels(){
        return array(
            'sku'               =>  'SKU',
            'listing_id'        =>  '产品ID',
            'operation_user_id' =>  '操作人员', 
            'start_time'        =>  '下架时间',
            'account_id'        =>  '账号',
            'message'           =>  '下架信息',
            'status'            =>  '是否成功'
        );
    }
    
    // ============================= end search ====================//
	
}