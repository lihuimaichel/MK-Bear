<?php
/**
 * @desc lazada类目佣金表
 * @author hanxy
 * @since 2016-12-30
 */ 

class LazadaCategoryCommissionRate extends LazadaModel{
    public $id,$category_name;
	
	public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_category_commission_rate';
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
        $cmd = $this->getDbConnection()->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }


    /**
     * 计算出佣金
     * @param  integer $categoryID   栏目ID
     * @param  integer $siteID       站点ID
     * @return float
     */
    public function getCommissionRate($categoryID,$siteID){
        $commissionRate = '';
        $topCategoryID = '';
        $isOpen = true;
        //ID站点于2017年1月27日开始计算佣金
        if($siteID == 3 && time() < strtotime('2017-01-27 00:00:00')){
            $isOpen = false;
        }

        $lazadaCategoryModel = new LazadaCategory();
        // $topCategoryID = $lazadaCategoryModel->getTopCategory($categoryID);
        $categoryArr = $this->getCategoryArrBySiteID($siteID);
        $info = $lazadaCategoryModel->getCategotyInfoByID($categoryID);
        if($info && $isOpen){
            $level = $info['level'];
            for($i = $level-1; $i > 0; $i--){
                if(in_array($info['parent_category_id'],$categoryArr)){
                    $topCategoryID = $info['parent_category_id'];
                    break;
                }else{
                    $info = $lazadaCategoryModel->getCategotyInfoByID($info['parent_category_id']);
                }
            }

            if($topCategoryID){
                //取出佣金比
                $commissionRateInfo = $this->getOneByCondition('commission_rate','category_id = '.$topCategoryID.' AND site_id = '.$siteID);
                if($commissionRateInfo){
                    $commissionRate = $commissionRateInfo['commission_rate']/100;
                }
            }
        }

        return $commissionRate;
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
            foreach ($datas as &$data){
                $categoryInfo = LazadaCategory::model()->getCategotyInfoByID($data['category_id']);
                $data['category_name'] = isset($categoryInfo['name']) ? $categoryInfo['name'] : '';
                $data['commission_rate'] = $data['commission_rate'].'%';
            }
        }
        return $datas;
    }


    public function filterOptions(){
        return array(
                array(
                    'name'   =>  'category_id',
                    'type'   => 'dropDownList',
                    'value'  => Yii::app()->request->getParam('category_id'),
                    'data'   => LazadaCategory::model()->getCategoryByCondit(),
                    'search' =>  '=',
                ),
                array(
                    'name'   =>  'site_id',
                    'type'   => 'dropDownList',
                    'value'  => Yii::app()->request->getParam('site_id'),
                    'data'   => LazadaSite::model()->getSiteList(),
                    'search' =>  '=',
                ),
        );
    }


    public function attributeLabels(){
        return array(
            'id'                    => 'ID',                
            'category_id'           => '类目',
            'site_id'               => '站点',
            'commission_rate'       => '佣金比例', 
            'create_user_id'        => '创建人',
            'create_time'           => '创建时间'         
        );
    }


    /**
     * 通过站点ID获取栏目ID数组
     * @return array
     */
    public function getCategoryArrBySiteID($site_id) {
        $categoryArr = array();
        $cmd = $this->getDbConnection()->createCommand()
            ->select('category_id')
            ->from(self::tableName())
            ->where('site_id = '.$site_id)
            ->queryAll();
        if($cmd){
            foreach ($cmd as $cmdValue) {
                $categoryArr[] = $cmdValue['category_id'];
            }
        }

        return $categoryArr;
    }
}