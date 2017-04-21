<?php

class AliexpressEditSimpleProductFiledMessages extends AliexpressModel{

    public $account_name;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_edit_simple_product_filed_messages';
    }


    /**
     * 插入数据
     */
    public function insertData($Data){
        return $this->getDbConnection()->createCommand()->insert(self::tableName(), $Data);
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
                    'name'   =>  'field_name',
                    'type'   => 'dropDownList',
                    'value'  => Yii::app()->request->getParam('field_name'),
                    'data'   => $this->getFieldName(),
                    'search' =>  '=',
                ),
                array(
                    'name'   => 'account_id',
                    'type'   => 'dropDownList',
                    'search' => '=',
                    'data'   => UebModel::model("AliexpressAccount")->getIdNamePairs(),
                ),
                
                array(
                    'name'   =>  'aliexpress_product_id',
                    'type'   => 'text',
                    'search' =>  '=',
                ),

                array(
                    'name'   =>  'sku',
                    'type'   => 'text',
                    'search' =>  '=',
                ),

                array(
                    'name'   => 'status',
                    'type'   => 'dropDownList',
                    'value'  => Yii::app()->request->getParam('status'),
                    'data'   => $this->getStatus(),
                    'search' => '=',
                ),
        );
    }
    
    
    public function attributeLabels(){
        return array(                
            'account_id'            => '账号',
            'field_name'            => '修改的字段',
            'aliexpress_product_id' => '订单号', 
            'sku'                   => 'sku',
            'create_user_id'        => '修改人',
            'send_msg'              => '发送记录',
            'status'                => '状态',
            'create_time'           => '创建时间'         
        );
    }
    
    // ============================= end search ====================//
    

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
     * 修改的字段
     */
    public function getFieldName($fieldName = null){
        $fieldNameOptions = array(
            'subject' => '商品标题', 
            'detail' => '商品详细', 
            'deliveryTime' => '备货期',
            'groupId' => '产品组',
            'freightTemplateId' => '运费模版',
            'packageLength' => '商品包装长度',
            'packageWidth' => '商品包装宽度',
            'packageHeight' => '商品包装高度',
            'grossWeight' => '商品毛重',
            'wsValidNum' => '商品的有效天数',
            'reduceStrategy' => '库存扣减策略'
        );
        if($fieldName !== null){
            return isset($fieldNameOptions[$fieldName])?$fieldNameOptions[$fieldName]:'';
        }
        return $fieldNameOptions;
    }
}