<?php
/**
 * @desc lazada账号
 * @author hanxy
 * @since 2017-03-11
 */
class OmsLazadaAccount extends SystemsModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_account';
    }

    /**
     * 显示所有账号ID
     */
    public function getOmsLazadaAccountID(){
        $accountArr = array();
        $info = $this->getDbConnection()->createCommand()->select('id')->from(self::tableName())->queryAll();
        if($info){
            foreach ($info as $value) {
                $accountArr[] = $value['id'];
            }
        }

        return $accountArr;
    }


    /**
     * 更新数据
     */
    public function updateData($data, $conditions, $params){
        return $this->getDbConnection()->createCommand()->update(self::tableName(), $data, $conditions, $params);
    }


    /**
     * 插入数据
     */
    public function insertData($data){
        $info = false;
        $result = $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
        if($result){
            $info = $this->getDbConnection()->getLastInsertID();
        }

        return $info;
    }
}