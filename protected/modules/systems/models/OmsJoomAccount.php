<?php
/**
 * @desc joom账号
 * @author Gordon
 * @since 2015-06-25
 */
class OmsJoomAccount extends SystemsModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_joom_account';
    }

   
}