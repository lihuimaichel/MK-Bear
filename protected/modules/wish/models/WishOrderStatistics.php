<?php
class WishOrderStatistics extends WishModel {
	
	public function tableName(){
		return  'ueb_wish_order_statistics';
	}
	
	public static function model($className = __CLASS__){
		return parent::model($className);
	}

	/**
	 * @desc 清除表所有数据
	 * @param unknown $data
	 */
	public function delAllData(){
        return $this->deleteAll("1 = 1");
	}	

	/**
	 * @desc 根据自增ID更新单个数据
	 * @param int $id
	 * @param array $updata
	 * @return boolean
	 */
	public function updateInfoByID($id, $updata){
		if(empty($id) || empty($updata)) return false;
		$conditions = "id = ".$id;
		return $this->getDbConnection()->createCommand()->update($this->tableName(), $updata, $conditions);
	}

	/**
	 * @desc 根据条件更新列表
	 * @param string $condition
	 * @param array $updata
	 * @return boolean
	 */
	public function updateListByCondition($conditions, $updata){
		if(empty($conditions) || empty($updata)) return false;
		return $this->getDbConnection()->createCommand()
				    ->update($this->tableName(), $updata, $conditions);
	}	

	/**
	 * 根据条件获取单条数据
	 */
	public function getInfoByCondition($where) {
		if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where($where)
					->limit(1)
                    ->queryRow();
	}

	/**
	 * 根据条件获取列表
	 */
	public function getListByCondition($where) {
		if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
					->select('*')
					->from($this->tableName())
					->where($where)
                    ->queryAll();
	}	

    /**
     * @desc 获取并保存每账号每SKU最近N天销量或总金额统计
     * 1.该账号该SKU过去7天销量≥25个的
     * 2.该账号该SKU过去9天订单总金额（(单个运费+单价)*数量）≥$500的（包括佣金的500美金）
     * @param $type int 类型：1-七天销量；2-九天订单总金额
     */
    public function addLastOrderStatistics(){    	
        $salesNum      = 25; //过去7天销量
        $orderAmount   = 500; //过去9天订单总金额（美金）
        $lastSevenDate = date('Y-m-d H:i:s', time() - 7*24*3600 - 8*3600);    //过去7天（转UTC时间）
        $lastNineDate  = date('Y-m-d H:i:s', time() - 9*24*3600 - 8*3600);    //过去9天（转UTC时间）                 

        //该账号该SKU过去7天销量≥25个的
        $searchSql = "SELECT SUM(quantity) as total,account_id,sys_sku,product_id FROM " .WishOrderMain::model()->tableName(). " WHERE order_time >= '{$lastSevenDate}' GROUP BY account_id,sys_sku HAVING total >= {$salesNum}";            
        $ret = $this->getDbConnection()->createCommand($searchSql)->queryAll();
        if ($ret){
        	$sql = '';
        	$have_insert_sql = 0;
        	$theDate = date('Y-m-d H:i:s');

        	//批量新增
	        $sql = "insert into " .$this->tableName(). "(account_id,sku,product_id,total,type,create_time) values ";
	        foreach($ret as $val){
				$accountID = (int)$val['account_id'];
				$sysSku    = trim(addslashes($val['sys_sku']));
				$productID = trim(addslashes($val['product_id']));
				$total     = intval($val['total']);	//销量
				$type      = 1;
				if ($accountID > 0 && !empty($sysSku)){
	                $sql .="(" .$accountID. ",'" .$sysSku. "','" .$productID. "'," .$total. "," .$type. ",'" .$theDate. "'),";
	                $have_insert_sql = 1;
	            }
	        }    

	        if ($have_insert_sql == 1){
	            $sql = substr($sql,0,strlen($sql)-1);
	            $this->getDbConnection()->createCommand($sql)->execute(); 
	        }
        }

        //该账号该SKU过去9天订单总金额（(单个运费+单价)*数量）≥$500的
        $searchSql = "SELECT SUM((shiping + price) * quantity) as total,account_id,sys_sku,product_id FROM " .WishOrderMain::model()->tableName(). " WHERE order_time >= '{$lastNineDate}' GROUP BY account_id,sys_sku HAVING total >= {$orderAmount}";            
        $ret = $this->getDbConnection()->createCommand($searchSql)->queryAll();
        if ($ret){
        	$sql = '';
        	$have_insert_sql = 0;
        	$theDate = date('Y-m-d H:i:s');

	        $sql = "insert into " .$this->tableName(). "(account_id,sku,product_id,total,type,create_time) values ";
	        foreach($ret as $val){
				$accountID = (int)$val['account_id'];
				$sysSku    = trim(addslashes($val['sys_sku']));
				$productID = trim(addslashes($val['product_id']));
				$total     = floatval($val['total']);	//金额
				$type      = 2;

				if ($accountID > 0 && !empty($sysSku)){
	                $sql .="(" .$accountID. ",'" .$sysSku. "','" .$productID. "'," .$total. "," .$type. ",'" .$theDate. "'),";
	                $have_insert_sql = 1;
	            }
	        }    

	        if ($have_insert_sql == 1){
	            $sql = substr($sql,0,strlen($sql)-1);
	            $this->getDbConnection()->createCommand($sql)->execute(); 
	        }
        }
        return true;
    }	

}

?>