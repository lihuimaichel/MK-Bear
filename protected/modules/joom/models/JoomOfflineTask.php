<?php
class JoomOfflineTask extends JoomModel {
	const UPLOAD_STATUS_PENDING = 0;
	const UPLOAD_STATUS_PROCESSING = 1;
	const UPLOAD_STATUS_SUCCESS = 2;
	const UPLOAD_STATUS_FAILURE = -1;
	public function tableName(){
		return  'ueb_joom_offline_task';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	/**
	 * @desc 添加数据
	 * @param unknown $data
	 */
	public function addData($data){
		return $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
	}
	
	/**
	 * @desc 获取任务列表
	 * @param unknown $status
	 * @param number $limit
	 * @return mixed
	 */
	public function getJoomTaskListByStatus($status, $limit = 1000){
		return $this->getDbConnection()
					->createCommand()
					->from(self::tableName())
					->where('status=:status', array(':status'=>$status))
					//->andWhere('account_id=:account_id', array(':account_id'=>$account_id))
					->limit($limit)
					->queryAll();
	}
	/**
	 * @desc 更新
	 * @param unknown $data
	 * @param string $conditions
	 * @param unknown $param
	 * @return Ambigous <number, boolean>
	 */
	public function updateJoomTask($data, $conditions = '', $param = array()){
		return $this->getDbConnection()->createCommand()
					->update(self::tableName(), $data, $conditions, $param);
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