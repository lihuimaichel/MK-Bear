<?php
class LazadaOfflineTask extends LazadaModel {
	public function tableName(){
		return 'ueb_lazada_offline_task';
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
            return self::model()->getDbConnection()->createCommand($sql)->query();
	}
}

?>