<?php
class EbayOfflineTask extends EbayModel {
	public function tableName(){
		return 'ueb_ebay_offline_task';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}
        
        /**
	 * @desc 批量插入数据
	 * @param unknown $data
	 */
	public function insertBatch($data){
            $table = self::tableName();
            if(empty($data)) {
                return true;
            }
            $columns = array();
            //$sql = "INSERT INTO `ueb_amazon_offline_task` ( `sku`, `account_id`, `status`, `create_user_id`, `create_time` ) VALUES ";
            $sql = "INSERT INTO {$table} ( ";
            foreach ($data[0] as $column=>$value){
                $columns[] = $column;
                $sql .= '`' . $column . '`,';
            }
            $sql = substr($sql,0,strlen($sql)-1);
            $sql .= " ) VALUES ";
            foreach ($data as $one){
                $sql .= "(";
                foreach ($one as $value){
                    $sql .= " '{$value}',";
                }
                $sql = substr($sql,0,strlen($sql)-1);
                $sql .= "),";
            }
            $sql = substr($sql,0,strlen($sql)-1);
            //$sql .= "('{$sku}', '{$account}', 0,  '{$create_user_id}', '{$create_time}'),";
            return self::model()->getDbConnection()->createCommand($sql)->query();
	}
}

?>