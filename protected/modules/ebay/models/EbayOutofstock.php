<?php
/**
 * @desc ebay 断货设置
 * @author yangsh
 * @since 2016-07-22
 */
class EbayOutofstock extends EbayModel{

    /**
     * [model description]
     * @param  [type] $className [description]
     * @return [type]            [description]
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_outofstock';
    }

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
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
     * @return array
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

// ================= ebay Listing Search =====================

    /**
     * @desc 获取ebay 是否设置断货状态选项
     * @param string $key
     */
    public static function getOutOfStockOption($key = null){
        $outofstockOptions = array('1' => Yii::t('system', 'Yes'), '2' => Yii::t('system', 'No'));
        if($key !== null){
            return $outofstockOptions[$key];
        }
        return $outofstockOptions;
    }

    /**
     * @desc 获取ebay ACK状态选项
     * @param string $key
     */
    public static function getAckOption($key = null){
        $ackOptions = array('1'=>'成功','2'=>'失败');
        if($key !== null){
            return $ackOptions[$key];
        }
        return $ackOptions;
    }

    /**
     * @desc 设置对应的字段标签名称
     * @see CModel::attributeLabels()
     */
    public function attributeLabels(){
    	return array(
            'sku'           => 'SKU',
            'no'            =>  Yii::t('ebay', 'item no.'),
            'is_outofstock' =>	Yii::t('ebay', 'item isOutOfStock'),
            'ack'           =>	Yii::t('ebay', 'ebay ack'),
            'message'       =>	Yii::t('ebay', 'ebay message'),
            'operator'      =>  Yii::t('ebay', 'listing operator'),
            'operate_time'  =>  Yii::t('ebay', 'listing operateTime'),
            'operate_note'  =>	Yii::t('ebay', 'listing note'),
    	);
    }

    /**
     * @return array search filter (name=>label)
     */
    public function filterOptions() {
        $is_outofstock = trim(Yii::app()->request->getParam("is_outofstock",1));//默认显示设置断货列表
        $ack           = trim(Yii::app()->request->getParam("ack",''));
        $operator      = trim(Yii::app()->request->getParam("operator",''));
        $result = array(
                array(
                    'name'           => 'sku',
                    'type'           => 'text',
                    'search'         => '=',
                    'htmlOption'     => array(
                        'size' => '22',
                    ),
                    'alias'          => 'v',
                ),
                array(
                    'name'          => 'is_outofstock',
                    'type'          => 'dropDownList',
                    'data'          => $this->getOutOfStockOption(),
                    'value'         => $is_outofstock,
                    'search'        => '=',
                    'htmlOptions'   => array(),
                    'rel'           => 'selectedTodo',
                ),
                array(
                    'name'          => 'ack',
                    'type'          => 'dropDownList',
                    'data'          => $this->getAckOption(),
                    'value'         => $ack,
                    'search'        => '=',
                    'htmlOptions'   => array(),
                    'rel'           => 'selectedTodo',
                ),  
                array(
                    'name'          => 'operator',
                    'type'          => 'dropDownList',
                    'data'          => User::model()->getEmpByDept(Department::getDepartmentByPlatform(Platform::CODE_EBAY)),
                    'value'         => 1,
                    'search'        => '=',
                    'htmlOptions'   => array(),
                    'rel'           => 'selectedTodo',
                ),    
                array(
                        'name'          => 'operate_time',
                        'type'          => 'text',
                        'search'        => 'RANGE',
                        'alias'         =>  't',
                        'htmlOptions'   => array(
                                'size' => 4,
                                'class'=>'date',
                                'style'=>'width:80px;'
                        ),
                ),

        );
        return $result;
    }    

    /**
     * @desc 设置搜索条件
     * @return CDbCriteria
     */
    public function setSearchDbCriteria(){
        $cdbcriteria = new CDbCriteria();
        $cdbcriteria->select = 'distinct v.sku,t.is_outofstock,t.ack,t.message,t.operator,t.operate_time,t.operate_note';

        //联接ebay多属性表
        $cdbcriteria->join = "right join ueb_ebay_product_variation as v on v.sku = t.sku ";

        $conditions = array("v.sku !='' ");

        if (isset($_REQUEST['sku']) && $_REQUEST['sku']) {
            $conditions[] = " v.sku='{$_REQUEST['sku']}'";
        }

        if(isset($_REQUEST['is_outofstock']) && $_REQUEST['is_outofstock']>0 ){
            $conditions[] = " t.is_outofstock='{$_REQUEST['is_outofstock']}'";
        } else if (isset($_REQUEST['is_outofstock'])) {
            
        } else {
            $conditions[] = " t.is_outofstock=1";//默认显示设置断货列表
        }

        if(isset($_REQUEST['ack']) && $_REQUEST['ack']>0 ){
            $conditions[] = " t.ack='{$_REQUEST['ack']}'";
        }

        if(isset($_REQUEST['operator']) && $_REQUEST['operator']){
            $conditions[] = " t.operator='{$_REQUEST['operator']}'";
        }

        if(isset($_REQUEST['operate_time']) && $_REQUEST['operate_time']){
            $startTime = !empty($_REQUEST['operate_time'][0]) ? trim($_REQUEST['operate_time'][0]) : '';
            $endTime = !empty($_REQUEST['operate_time'][1]) ? trim($_REQUEST['operate_time'][1]) : '';
            $startTime != '' && $conditions[] = " t.operate_time>='{$startTime}'";
            $endTime != '' && $conditions[] = " t.operate_time<'{$endTime}'";
        }        
        
        if($conditions){
            $conditions = implode(" AND ", $conditions);
            $cdbcriteria->addCondition($conditions);
        }
        
        return $cdbcriteria;
    }

    /**
     * @desc 提供数据
     * @see UebModel::search()
     */
    public function search(){
        $sort = new CSort();
        $sort->attributes = array(
            'defaultOrder'      =>'operate_time',
            'defaultDirection'  =>  'DESC'
        );
        $dataProvider = parent::search($this, $sort, '', $this->setSearchDbCriteria());
        $datas = $this->addtions($dataProvider->data);
        $dataProvider->setData($datas);
        return $dataProvider;
    }

    /**
     * @desc 设置格外的数据处理
     * @param unknown $datas
     * @return unknown
     */
    public function addtions($datas){
        if($datas){
            foreach ($datas as &$data){
                //操作备注
                if ($data->operate_note) {
                    $operateNote = json_decode($data->operate_note,true);
                    $tmpArr = $operateNote['data'][$data->is_outofstock];
                    if (!empty($tmpArr)) {
                        $success = $failure = 0;
                        if ($tmpArr['success']) {
                            $success += count($tmpArr['success']);
                        }  
                        if ($tmpArr['failure']) {
                            $failure += count($tmpArr['failure']);
                        }                         
                        if ($data->is_outofstock == 1) {
                            $noteStr = '设置断货处理listing信息:<br>';    
                            $noteStr .= "修改成功".$success."个,失败".$failure."个<br>";                                    
                            foreach ($tmpArr as $ack => $vals) {
                                foreach ($vals as $itemID => $skucount) {
                                    foreach ($skucount as $sku => $count) {
                                        $noteStr .= $itemID.'--'.$sku.'--'.$ack.'<br>';
                                    }
                                }
                            }
                        } else {
                            $noteStr = '取消断货处理listing信息:<br>';
                            $noteStr .= "修改成功".$success."个,失败".$failure."个<br>";
                            foreach ($tmpArr as $ack => $vals) {
                                foreach ($vals as $itemID => $skucount) {
                                    foreach ($skucount as $sku => $count) {
                                        $noteStr .= $itemID.'--'.$sku.'--'.$count.'个--'.$ack.'<br>';
                                    }
                                }
                            }                            
                        }
                    } else {
                        $noteStr = '';
                    }
                    $data->operate_note = $noteStr;
                }
            }
        }
        return $datas;
    }    

// ================= ebay Listing Search =====================


}