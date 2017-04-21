<?php

class AliexpressNonPaymentOrderSendMessages extends AliexpressModel{

    public $account_name;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_non_payment_order_send_messages';
    }
    

    /**
     * [getOneByCondition description]
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
        $cmd->limit(1);
        return $cmd->queryRow();
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
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }


    /**
     * 插入数据
     */
    public function insertData($Data){
        return $this->getDbConnection()->createCommand()->insert(self::tableName(), $Data);
    }


    /**
     * 更新数据
     */
    public function updateData($Data,$wheres){
        return $this->getDbConnection()->createCommand()->update(self::tableName(), $Data, $wheres);
    }


    /**
     * 循环插入数据
     * @param array $msgData
     */
    public function insertPlaceOrder($msgData){
        foreach ($msgData as $oneInfo) {
            $platformOrderId = $oneInfo['platform_order_id'];
            //判断订单是否存在
            $placeInfo = $this->getOneByCondition('platform_order_id','platform_order_id = '.$platformOrderId);
            if($placeInfo){
                continue;
            }
            $dates = $oneInfo['gmt_create'];
            $times = substr($dates, 0, 4).'-'.substr($dates, 4, 2).'-'.substr($dates, 6, 2).' '.substr($dates, 8, 2).':'.substr($dates, 10, 2).':'.substr($dates, 12, 2);

            $insertData = array(
                'account_id'        => $oneInfo['account_id'],
                'buyer_login_id'    => $oneInfo['buyer_login_id'],
                'platform_order_id' => $platformOrderId,
                'gmt_create'        => $times,
                'pay_amount'        => $oneInfo['pay_amount'],
                'receipt_country'   => $oneInfo['receipt_country'],
                'create_time'       => date('Y-m-d H:i:s')
            );
            $this->insertData($insertData);
        }
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
                    'name'   => 'account_id',
                    'type'   => 'dropDownList',
                    'search' => '=',
                    'data'   => UebModel::model("AliexpressAccount")->getIdNamePairs(),
                ),
                
                array(
                    'name'   =>  'buyer_login_id',
                    'type'   => 'text',
                    'search' =>  '=',
                ),

                array(
                    'name'   =>  'platform_order_id',
                    'type'   => 'text',
                    'search' =>  '=',
                ),

                array(
                    'name'   => 'gmt_create',
                    'type'   => 'text',
                    'search' => 'RANGE',
                    'htmlOptions' => array(
                        'class'   => 'date',
                        'dateFmt' => 'yyyy-MM-dd HH:mm:ss',
                    ),
                ),

                array(
                    'name'   => 'pay_amount',
                    'type'   => 'text',
                    'search' => 'RANGE',
                    'htmlOptions' => array(
                        'size'   => 6,
                    ),
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
            'account_id'        => '账号',
            'buyer_login_id'    => '买家id',
            'platform_order_id' => '订单号', 
            'gmt_create'        => '下单时间',
            'pay_amount'        => '订单金额',
            'receipt_country'   => '国家',
            'send_msg'          => '发送记录',
            'status'            => '状态',
            'create_time'       => '创建时间'         
        );
    }
    
    // ============================= end search ====================//
    

    /**
     * 是否成功状态
     */
    public function getStatus($status = null){
        $statusOptions = array(0=>'待处理', 1=>'成功', '2'=>'失败');
        if($status !== null){
            return isset($statusOptions[$status])?$statusOptions[$status]:'';
        }
        return $statusOptions;
    }
}