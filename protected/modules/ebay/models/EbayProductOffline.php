<?php
/**
 * @desc Ebay下架操作记录表
 * @author hanxy
 * @since 2016-10-17
 */
class EbayProductOffline extends EbayModel{

    public $account_name;
    public $site_name;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_product_offline_log';
    }

    public function addNewData($data) {
        return $this->getDbConnection()->createCommand()->insert($this->tableName(),$data);
    }

    // ============================= search ========================= //
    
    public function search(){
        $sort = new CSort();
        $sort->attributes = array('defaultOrder'=>'create_time');
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
            $siteLists = UebModel::model('EbaySite')->getSiteList();
            $accountLists = UebModel::model('EbayAccount')->getIdNamePairs();
            foreach ($datas as &$data){
                //获取站点
                $data['site_name'] = isset($siteLists[$data['site_id']]) ?  $siteLists[$data['site_id']] : '';
                //获取账号名称
                $data['account_name'] = isset($accountLists[$data['account_id']]) ? $accountLists[$data['account_id']] : ''; 
            }
        }
        return $datas;
    }

    
    public function filterOptions(){
        return array(
            array(
                    'name'=>'item_id',
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
                    'name'=>'seller_sku',
                    'type'=>'text',
                    'search'=>'=',
                    //'rel'=>true,
                    'htmlOption' => array(
                            'size' => '22',
                            'style' =>  'width:260px'
                    )
            ),
            
            array(
                    'name'      =>  'create_user_id',
                    'type'      =>  'dropDownList',
                    'data'      =>  EbayProductAdd::model()->getCreateUserOptions(),
                    'search'    =>  '=',
            
            ),

            array(
                    'name'=>'account_id',
                    'type'      =>  'dropDownList',
                    'data'      =>  EbayAccount::getIdNamePairs(),
                    'search'    =>  '=',
                    'htmlOption' => array(
                            'size' => '22',
                    )
            ),

            array(
                    'name'=>'site_id',
                    'type'      =>  'dropDownList',
                    'data'      =>  EbaySite::getSiteList(),
                    'value'     =>  Yii::app()->Request->getParam('site_id'),
                    'search'    =>  '=',
                    'htmlOption' => array(
                            'size' => '22',
                    )
            ),

            array(
                'name'          => 'create_time',
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
            'seller_sku'        =>  '在线SKU',
            'item_id'           =>  'Item ID',
            'create_user_id'    =>  '修改人员', 
            'create_time'       =>  '下架时间',
            'account_id'        =>  '账号',
            'site_id'           =>  '站点'
        );
    }
    
    // ============================= end search ====================//

}