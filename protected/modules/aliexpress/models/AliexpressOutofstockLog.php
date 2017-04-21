<?php
/**
 * @desc aliexpress 断货设置
 * @author hanxy
 * @since 2016-12-07
 */
class AliexpressOutofstockLog extends AliexpressModel{

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
        return 'ueb_aliexpress_outofstock_log';
    }
}