<?php
/**
 * @desc 仓库模型
 * @author lihy
 *
 */
class Warehouse extends WarehouseModel{

	const WAREHOUSE_TYPE_LOCAL = 1; //仓库本地
	const WAREHOUSE_TYPE_ABORAD = 2; // 海外
	
	const WAREHOUSE_CUSTODY_YES = 1; //是寄存仓库
	const WAREHOUSE_CUSTODY_NO = 0; //不是寄存仓库
	
	public static function model($class = __CLASS__){
		return parent::model($class);
	}
	public function tableName(){
		return "ueb_warehouse";
	}
	/**
	 * @desc 获取可用的海外仓库
	 * @return mixed
	 */
	public function getAbroadWarehouse(){
		return $this->getDbConnection()->createCommand()
								->from($this->tableName())
								->where("warehouse_type=".self::WAREHOUSE_TYPE_ABORAD . ' AND is_custody='.self::WAREHOUSE_CUSTODY_NO . ' AND use_status=1')
								->queryAll();		
	}
	
	/**
	 * @desc 获取仓库id和名称对
	 * @return multitype:unknown
	 */
	public function getAbroadWarehousePairs(){
		$result = $this->getDbConnection()->createCommand()
					->from($this->tableName())
					->where("warehouse_type=".self::WAREHOUSE_TYPE_ABORAD . ' AND is_custody='.self::WAREHOUSE_CUSTODY_NO . ' AND use_status=1')
					->queryAll();
		$newWarehouseList = array();
		if($result){
			foreach ($result as $val){
				$newWarehouseList[$val['id']] = $val['warehouse_name'];
			}
		}
		return $newWarehouseList;
	}

	/**
	 * @desc 获取仓库id和名称对
	 * @return multitype:unknown
	 */
	public function getWarehousePairs(){
		$result = $this->getDbConnection()->createCommand()
					->from($this->tableName())
					->where(' is_custody='.self::WAREHOUSE_CUSTODY_NO . ' AND use_status=1')
					->queryAll();
		$newWarehouseList = array();
		if($result){
			foreach ($result as $val){
				$newWarehouseList[$val['id']] = $val['warehouse_name'];
			}
		}
		return $newWarehouseList;
	}

}