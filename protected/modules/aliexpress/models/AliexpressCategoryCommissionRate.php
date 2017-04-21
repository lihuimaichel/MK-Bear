<?php

class AliexpressCategoryCommissionRate extends AliexpressModel{
   
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_aliexpress_category_commission_rate';
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
     * 显示佣金比例描述
     * @param  integer $categoryId   栏目ID
     * @return string
     */
    public static function getCommissionRateText($categoryId){
        $commissionString = '';
        $aliCategoryCommissionRate = new AliexpressCategoryCommissionRate();
        $info = $aliCategoryCommissionRate->getOneByCondition('commission_rate', 'category_id = '.$categoryId);
        if($info){
            $commissionString = '(佣金比：'.$info['commission_rate'].'%)';
        }

        return $commissionString;
    }


    /**
     * 计算出佣金
     * @param  integer $categoryID   栏目ID
     * @return float
     */
    public static function getCommissionRate($categoryID,$flag=false){
        $commissionRate = 0.05;
        $aliexpressCategoryModel   = new AliexpressCategory();
        $aliCategoryCommissionRate = new AliexpressCategoryCommissionRate();
        $topCategoryID = $aliexpressCategoryModel->getTopCategory($categoryID);
        if($topCategoryID == 36){
            $topCategoryID = $aliexpressCategoryModel->getTwoCategory($categoryID);
        }
		if(!$topCategoryID){
			return null;
		}
        //取出佣金比
        $commissionRateInfo = $aliCategoryCommissionRate->getOneByCondition('commission_rate','category_id = '.$topCategoryID);
        if($commissionRateInfo){
            $commissionRate = round($commissionRateInfo['commission_rate']/100,2);
        } else if ($flag) {
            return null;
        }

        return $commissionRate;
    }   
    
    public function attributeLabels(){
        return array(                
            'category_id'       => '类目ID',
            'commission_rate'   => '佣金比例',
            'create_user_id'    => '创建人',
            'create_time'       => '创建时间'         
        );
    }    
}