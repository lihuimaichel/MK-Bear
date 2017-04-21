<?php
/**
 * @desc 海外仓订单Model
 * @author hanxy
 * @since 2017-03-08
 */
class OverseasOrder extends OrdersModel {

    public $sku;
    public $quantity;
    public $sale_price;
    public $ori_update_time;
    public $warehouse_id;
    public $warehouse_name;
    public $order_status;
    	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order';
    }


    /**
     * @desc 属性翻译
     */
    public function attributeLabels() {
        return array(
            'order_id'          => 'CO订单号',
            'platform_order_id' => '平台订单号',
            'sku'               => 'SKU',
            'quantity'          => '数量',
            'ori_create_time'   => '下单时间',
            'warehouse_name'    => '发货仓',
            'order_status'      => '订单状态',
            'total_price'       => '订单总额',
            'sale_price'        => 'SKU单价',
            'currency'          => '币种',
            'warehouse_id'      => '发货仓',
            'ori_update_time'   => '更新时间'
        );
    }

    public function addtions($datas){
        if(empty($datas)) return $datas;
        foreach ($datas as &$data){
            $orderInfo = LazadaOrderMain::model()->getOneByCondition('order_status', "platform_order_id = '{$data['platform_order_id']}'");
            if(!$orderInfo){
                $data['order_status'] = '';
            }else{
                $data['order_status'] = $orderInfo['order_status'];
            }
        }
        return $datas;
    }

    /**
     * search SQL
     * @return $array
     */
    protected function _setCDbCriteria() {
        $criteria = new CDbCriteria();
        $criteria->select = "t.order_id,t.platform_order_id,d.sku,d.quantity,t.ori_create_time,t.total_price,d.sale_price,t.currency,d.warehouse_id,w.warehouse_name";
        $criteria->join = " LEFT JOIN ".OrderDetail::model()->tableName() . " as d on d.order_id = t.order_id LEFT JOIN ueb_warehouse.".Warehouse::model()->tableName()." as w on w.id = d.warehouse_id";
        $criteria->addCondition("t.platform_code = 'lazada'");
        $getParams = Yii::app()->request->getParam('ori_update_time');
        if($getParams){
            if($getParams[0]){
                $criteria->addCondition("t.ori_update_time >= '".$getParams[0]."'");
            }

            if($getParams[1]){
                $criteria->addCondition("t.ori_update_time < '".$getParams[1]."'");
            }
        }

        $warehouseID = Yii::app()->request->getParam('warehouse_id');
        if($warehouseID){
            $criteria->addCondition("d.warehouse_id = '{$warehouseID}'");
        }else{
            $warehouseArr = array('71','77','81');
            $criteria->addInCondition("d.warehouse_id", $warehouseArr);
        }
        
        return $criteria;
    }    
    
    /**
     * get search info
     */
    public function search() {
        $sort = new CSort();
        $sort->attributes = array(
                'defaultOrder'  => 'order_id',
        );
        $criteria = $this->_setCDbCriteria();
        $dataProvider = parent::search(get_class($this), $sort, array(), $criteria);
        $data = $this->addtions($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }
    
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        $warehouseId = Yii::app()->request->getParam('warehouse_id');
        $result = array(
            array(
                    'name'=>'warehouse_id',
                    'type'=>'dropDownList',
                    'search'=>'=',
                    'data'=>self::getWarehouse(),
                    'value'=>$warehouseId
            ),
            array(
                'name'          => 'ori_update_time',
                'type'          => 'text',
                'search'        => 'RANGE',
                'htmlOptions'   => array(
                        'class'    => 'date',
                        'dateFmt'  => 'yyyy-MM-dd HH:mm:ss',
                ),
            ),
        );
        return $result;
    }

    /**
     * 获取海外仓
     */
    public static function getWarehouse($warehouse_id = null){
        $warehouseOptions = array(71=>'F-SG FBL', 77=>'LZD 印尼虚拟仓', 81=>'LZD泰国虚拟仓');
        if($warehouse_id !== null){
            return isset($warehouseOptions[$warehouse_id])?$warehouseOptions[$warehouse_id]:'';
        }
        return $warehouseOptions;
    }        
}