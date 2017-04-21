<?php
/**
 * @desc Lazada刊登属性
 * @author Gordon
 * @since 2015-08-20
 */
class LazadaProductAddAttribute extends LazadaModel{
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_product_add_attribute';
    }
    
    /**
     * @desc 属性存储
     */
    public function saveRecord($addID, $name, $value){
        $newName = '';
        $ps = explode('_', $name);
        foreach($ps as $word){
            $newName .= ucfirst($word);
        }
        $this->dbConnection->createCommand()->insert(self::tableName(),array(
            'add_id'    => $addID,
            'name'      => $newName,
            'value'     => trim(addslashes($value)),
            'is_custom' => 0,
        ));
    }
    
    /**
     * @desc 通过AddID获取属性
     * @param int $addID
     */
    public function getAttributesByAddID($addID){
        return $this->dbConnection->createCommand()
                ->select('*')
                ->from(self::tableName())
                ->where('add_id = '.$addID)
                ->queryAll();
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
}