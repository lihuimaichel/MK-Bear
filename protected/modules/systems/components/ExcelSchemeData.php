<?php
/**
 *  create the excel scheme data
 *
 * @author Bob <zunfengke@gmail.com> 
 * @package application.components
 */
class ExcelSchemeData {
    
    private static $_instance;
    
    /**
     * @var int scheme id
     */
    protected $_schemeId = null;
        
    /**
     * @var string the conditions
     */
    protected $_conditions = null;
    
    /**
     * @var array scheme config
     */
    protected $_schemeConfig = null;
    
    /**
     *
     * @var string model name
     */
    protected $_modelName = null;
    
    /**
     *
     * @var array map the table names
     */
    protected $_mapTablelNames = array();
    
    /**
     *
     * @var array default column map
     */
    protected $_defaultColumnMap = array();
    
    /**
     *
     * @var array  the addition columns
     */
    protected $_additionColumns = array();
    
    /**
     *
     * @var array sort table names
     */
    protected $_sortTableNames = array();
    /**
     *
     * @var array query columns
     */
    protected $_queryColumns = array();


    public function __construct() {}
    
    /**
	 * @get instance of self
     */
	public static function getInstance() {
		if(! self::$_instance instanceof self){
			self::$_instance = new self();
		}
		
		return self::$_instance; 
	}
    
    /**
     * set scheme ID
     * @param int $schemeId
     * @return \ExcelSchemeData
     */
    public function setSchemeId($schemeId) {
        $this->_schemeId = $schemeId;
        //字段数据 [含非展示但条件字段]	9.19
        $this->_schemeConfig = UebModel::model('ExcelSchemeColumn')
                 ->getSchemeConfigBySchemeId($schemeId);
        //var_dump($this->_schemeConfig);
        //涉及的表
        $this->_mapTablelNames = array_keys($this->_schemeConfig);
        //var_dump($this->_mapTablelNames);
        $this->_defaultColumnMap = UebModel::model('ExcelDefaultColumnMap')
                    ->getMapByTableNames($this->_mapTablelNames,$schemeId);
        //var_dump($this->_defaultColumnMap);
        return $this;
    }
    
    /**
     * set conditions
     * @param string $conditions
     * @return \ExcelSchemeData
     */
    public function setConditions($conditions) {
        $this->_conditions = $conditions;
        
        return $this;
    }
    
    public function getConditions() {
        return  $this->_conditions;
    }
    /**
     * set QueryColumn
     * @param string $conditions
     * @return \ExcelSchemeData
     */
    public function setQueryColumn($queryColumn) {
        $this->_queryColumns = $queryColumn;
        
        return $this;
    }
    
    public function getQueryColumn() {
        return  $this->_queryColumns;
    }
    

        /**
     * set model name
     * @param string $modelName
     * @return \ExcelSchemeData
     */
    public function setModelName($modelName) {
        $this->_modelName = $modelName;
        
        return $this;
    }
    
    /**
     * get the model name 
     * 
     * @return string 
     */
    public function getModelName() {
        return $this->_modelName; 
    }
    
	/**
     * get the scheme join data
     * 
     * @param boolean $isGetTotal:true is count the total of data,false is get data list
     * @staticvar array $row
     * @return array
     */
    public function getSchemeJoinData($isGetTotal = false) {
         $result = $data = array();
         $schemeId = $this->_schemeId ;
         $titles = UebModel::model('ExcelSchemeColumn')->getColumnFieldAndTitleBySchemeId($schemeId,'is_value='.ExcelSchemeColumn::IS_VALUE);

         //more one table
         if ( count($this->_schemeConfig) > 1 ) {
             $sortTableNames = $this->getSortTableNames();
             $data = $this->getListDataByJoin($isGetTotal);
        } else {
            //one table
             $data = $this->getListDataByJoin($isGetTotal);

        }
        //将查询字段与方案设定字段个数对应齐:某些方案定制时会设定固定值之类的字段，其不用从表里面取，这里要取完表里数据后需要把这些固定值类的字段加到取出的数据里面去
        if(count($titles) > count($data[0])){//比较显示个数是否一致，不一致说明需要加，
        	foreach ($data as $key=>$datas){
        		foreach ($titles as $k=>$v){
        			if( in_array($k,array_keys($datas)) ){
        				$result[$key][$k] = $datas[$k];
        			}else{
        				$result[$key][$k] = $v;
        			} 
        		}
        	}
        }elseif (count($titles) < count($data[0])) {
        	foreach ($data as $key=>$val) {
        		foreach ($val as $k=>$v) {
        			if (in_array($k, $titles)) {
        				$result[$key][$k] = $v;
        			}
        		}
        	}	
        }else{//字段个数相同时直接返回
        	return $data;
        }
        
        return $result;
    }
    
    /**
     * get the scheme data
     * 
     * @staticvar array $row
     * @return array
     */
	public function getSchemeData() {
         $data = array();
         //more one table
         if ( count($this->_schemeConfig) > 1 ) {
             $sortTableNames = $this->getSortTableNames();
             $listData = $this->getListData();            
             $schemeColumnList = UebModel::model('ExcelSchemeColumn')
                     ->getBySchemeId($this->_schemeId);
                                               
             foreach ($listData[$sortTableNames[0]] as $val) { 
                static $row = array();
                $row[$sortTableNames[0]] = $val;  
                $this->_schemeRow($row, 0, $data, $sortTableNames, $listData, $schemeColumnList);             
                unset($row);                                      
            }
        } else {
            //one table
             $modelName = $this->getModelName();
             $model = UebModel::model($modelName);
             $tableName = $model->tableName();
             $list = UebModel::model($this->getModelName())
                     ->queryByField($this->getConditions(), array(), $this->_schemeConfig[$tableName]['column_field']);
             $columnFieldPairs = array_flip($this->_schemeConfig[$tableName]['column_field']);
             $schemeColumnList = UebModel::model('ExcelSchemeColumn')
                     ->getBySchemeId($this->_schemeId);
             
             foreach ($list as $key => $row) {                
                 foreach ($schemeColumnList as $schemeColumnRow  ) {
                     if ( $schemeColumnRow->column_id ) {
                         $val = $row[$schemeColumnRow->column_field];
                         if (! empty($schemeColumnRow->column_expression)) {
                             eval($schemeColumnRow->column_expression);                             
                         }
                         $data[$key][$schemeColumnRow->column_order] = $val;
                     } else {
                         $data[$key][$schemeColumnRow->column_order] = $schemeColumnRow->column_field;
                     }                        
                } 
            }
        }
        
        return $data;
    }
    
    /**
     * set the sort table names 
     * @return \ExcelSchemeData
     */
    public function setSortTableNames() {
        $modelName = $this->getModelName();
        
        $tableName = UebModel::model($modelName)->tableName();
        $dbkey = UebModel::model($modelName)->getDbKey();
        $env = new Env();
        $dbName = $env->getDbNameByDbKey($dbkey);

        $sortTableNames = UebModel::model('ExcelDefaultColumnMap')
                    ->getSortTableNames($dbName.'.'.$tableName, $this->_defaultColumnMap);

        foreach ($this->_mapTablelNames as $val) {
            if (! in_array($val, $sortTableNames)) {
                $sortTableNames = UebModel::model('ExcelDefaultColumnMap')->getSortTableNames($val, $this->_defaultColumnMap);
            }
        }
        $this->_sortTableNames = $sortTableNames;
        //var_dump($this->_sortTableNames);
        return $this;
    }
    
    /**
     * get sort table names
     * 
     * @return array;
     */
    public function getSortTableNames() {
        return $this->_sortTableNames;
    }
    
    public function setAdditionColumns() {
        $additionColumns = array();
        $sortTableNames = $this->getSortTableNames();
        $defaultColumnMap = $this->_defaultColumnMap;
        foreach ($sortTableNames as $sortTableName ) {
            if ( isset($defaultColumnMap[0][$sortTableName]) ) {
                 foreach ( $defaultColumnMap[0][$sortTableName] as $mapTableName => $map ) {                        
                    foreach ($map as $key => $val) {                            
                        $additionColumns[$sortTableName][] = $val['column_field'];
                    }                 
                }                
            } 

            if ( isset($defaultColumnMap[1][$sortTableName]) ) {
                foreach ( $defaultColumnMap[1][$sortTableName] as $mapTableName => $map ) {                
                    foreach ($map as $key => $val) {
                        $additionColumns[$sortTableName][] = $val['map_field_name'];
                    }                 
                } 
            }

            if ( isset($additionColumns[$sortTableName]) ) {
                $additionColumns[$sortTableName] = array_unique($additionColumns[$sortTableName]);
            }                            
        }
        
        $this->_additionColumns = $additionColumns;
        //print_r($this->_additionColumns);
        return $this;
    }
    
    public function getAdditionColumns() {
        return $this->_additionColumns;
    }

    /**
     * get list data
     *
     * @return array $listData
     */
    public function getListData() {
    	$sortTableNames = $this->getSortTableNames();
    	$additionColumns = $this->getAdditionColumns();
    	$additionColumnsKeys = array();
    	$conditions = $this->getConditions();
    	$defaultColumnMap = $this->_defaultColumnMap;
//     	print_r($sortTableNames);
//     	print_r($additionColumns);
    	foreach ( $sortTableNames as $index => $sortTableName ) {
    		$columns = $this->_schemeConfig[$sortTableName]['column_field'];
    		if (! empty($additionColumns[$sortTableName]) ) {
    			$columns = array_merge($additionColumns[$sortTableName], $columns);
    		}
    		$list[$sortTableName] = MHelper::getModelByTableName($sortTableName)
    				->queryByField($conditions, array(), $columns);
    		print_r($list);
    		die('555===');
    		foreach ($list[$sortTableName] as $key => $val) {
    			if ( $index < count($sortTableNames) - 1 ) {
    				foreach ( $additionColumns[$sortTableName] as $additionColumn ) {
    					$additionColumnsKeys[$sortTableName][$additionColumn][] = $val[$additionColumn];
    				}
    			}

    			if ( $index > 0 ) {
    				$_key = '';
    				foreach ( $additionColumns[$sortTableName] as $k => $additionColumn ) {
    					$_key .= '_'. $val[$additionColumn];
    				}
    				$_key = trim($_key, '_');
    				$listData[$sortTableName][$_key][] = $val;
    			} else {
    				$listData[$sortTableName] = $list[$sortTableName];
    			}
    		}
   
    		if ( $index < count($sortTableNames) - 1 ) {
    			$conditions = "";
    			if ( isset($defaultColumnMap[1][$sortTableNames[$index+1]]) ) {
    				$reverseTableMap = $defaultColumnMap[1][$sortTableNames[$index+1]];
    				foreach ($reverseTableMap as $key => $tableMap) {
    					foreach ($tableMap as $key2 => $val2) {
    						$tableName = $val2['table_name'];
    						$columnField = $val2['column_field'];
    						$mapFieldName = $val2['map_field_name'];
    						$columnsValues = $additionColumnsKeys[$tableName][$columnField];
    						$columnsValueStr = implode("','", $columnsValues);
    						if ( empty($conditions) ) {
    							$conditions .= " $mapFieldName IN('".$columnsValueStr."')";
    						} else {
    							$conditions .= " AND $mapFieldName IN('".$columnsValueStr."')";
    						}
    					}
    				}
    			} else {
    				$reverseTableMap = $defaultColumnMap[0][$sortTableNames[$index+1]];
    				foreach ($reverseTableMap as $key => $tableMap) {
    					foreach ($tableMap as $key2 => $val2) {
    						$tableName = $val2['map_table_name'];
    						$columnField = $val2['map_field_name'];
    						$mapFieldName = $val2['column_field'];
    						$columnsValues = $additionColumnsKeys[$tableName][$columnField];
    						$columnsValueStr = implode("','", $columnsValues);
    						if ( empty($conditions) ) {
    							$conditions .= " $mapFieldName IN('".$columnsValueStr."')";
    						} else {
    							$conditions .= " AND $mapFieldName IN('".$columnsValueStr."')";
    						}
    					}
    				}
    			}
    		}
    	}
    	return $listData;
    }
    
    /**
     * get list data
     * 
     * @param boolean $isGetTotal
     * @return array $listData
     */
    public function getListDataByJoin($isGetTotal) {
        $sortTableNames = $this->getSortTableNames();

        $additionColumns = $this->getAdditionColumns();
        $additionColumnsKeys = array();        
        $conditions = $this->getConditions();

        $defaultColumnMap = $this->_defaultColumnMap;
        //$queryColumns = $this->_queryColumns;
        $columns = '';
        $defaultTable = '';
        $joinTable = array();
        //获取select的字段：将is_value设置的字段连起来作为查询字段(没包含固定值等手动设置的值)
		$queryColumns = !empty($this->_queryColumns) ? $this->_queryColumns : $this->_schemeConfig;
		foreach ($queryColumns as $tableName=>$columnField){
			$fieldArr = $columnField['column_field'];
			$titleArr = $columnField['column_title'];
			//这里注意看下要不要给设置column_field别名为column_title,防column_field字段名相同时不好限数据
			foreach ($fieldArr as $key=>$column){//$sortTableName.'.'.
				$columns .= $column.',';//没用别名时,2014.7.16注释	9.16放开
				//$columns .= $column.' as '.$titleArr[$key].',';
			}
		}

		foreach ( $sortTableNames as $index => $sortTableName ) {
			if ($index==0) {//设置主表
				$arr = explode('.', $sortTableName);
				$defaultTable = $arr[1];
			}
            /**
            if ($index==0) {
            	$primaryKey = MHelper::getModelByTableName($sortTableName)
            			->getMetaData()->tableSchema->primaryKey;
            	$columnArr = array_merge(array($primaryKey), $columnArr);
            }
            
            if (! empty($additionColumns[$sortTableName]) ) {
                $columnArr = array_merge($additionColumns[$sortTableName], $columnArr);
            }

			if ($_POST['is_sum'][$sortTableName]){
				foreach ($_POST['is_sum'][$sortTableName] as $key=>$val){
					$columns .= 'sum('.$sortTableName.'.'.$val.') as '.$val.',';
					$columns = str_replace($sortTableName.'.'.$val.',', '', $columns);
				}
			}
			if ($_POST['is_count'][$sortTableName]){
				foreach ($_POST['is_count'][$sortTableName] as $key=>$val){
					$columns .= 'count('.$sortTableName.'.'.$val.') as '.$val.',';
					$columns = str_replace($sortTableName.'.'.$val.',', '', $columns);
				}
			}

			if ($_POST['is_avg'][$sortTableName]){
				foreach ($_POST['is_avg'][$sortTableName] as $key=>$val){
					$columns .= 'avg('.$sortTableName.'.'.$val.') as '.$val.',';
					$columns = str_replace($sortTableName.'.'.$val.',', '', $columns);
				}
			}
			*/

			//var_dump($defaultColumnMap);
			if ($index>0) {
				$tables = isset($defaultColumnMap[0][$sortTableName]) ? $defaultColumnMap[0][$sortTableName] : $defaultColumnMap[1][$sortTableName];
				if (isset($defaultColumnMap[0][$sortTableName])) {
					foreach ($defaultColumnMap[0][$sortTableName] as $table=>$tableMap){
						foreach ($tableMap as $key=>$val){
							$leftColumn = $val['db_name'].'.'.$val['table_name'].'.'.$val['column_field'];
							$rightColumn = $val['map_db_name'].'.'.$val['map_table_name'].'.'.$val['map_field_name'];
					
							if ($table==$sortTableNames[0]) {
								$joinTable[0][$sortTableName] = $leftColumn.'='.$rightColumn;
							}else{
								$joinTable[$index][$sortTableName] = $leftColumn.'='.$rightColumn;
							}
						}
					}
				}
			}
        }
        ksort($joinTable);
        $listData = MHelper::getModelByTableName($defaultTable)
        		->getDataByJoin($conditions, array(), $columns,$joinTable,$isGetTotal);
        return $listData;
    }
    
    /**
     * get scheme row 
     * 
     * @param array $row
     * @param integer $index
     * @param array $data    
     * @param array $sortTableNames
     * @param array $listData
     * @param array $schemeColumnList
     * 
     * return void
     */
     protected function _schemeRow(&$row, $index, &$data, $sortTableNames, $listData, $schemeColumnList) {      
         $defaultColumnMap = $this->_defaultColumnMap;
//          print_r($defaultColumnMap[1][$sortTableNames[$index+1]]);
//          print_r($row);
         $_key = '';
         if ( isset($defaultColumnMap[1][$sortTableNames[$index+1]])) {
             foreach ($defaultColumnMap[1][$sortTableNames[$index+1]] as $val) { 
                 foreach ($val as $val2) {//echo 'aaa';
                       //$_key .= $row[$val2['db_name'].'.'.$val2['table_name']][$val2['column_field']];
                       $_key .= $row[$val2['db_name'].'.'.$val2['table_name']];
                       //echo $_key.'===';
                 }                         
             }
         } else {
             foreach ($defaultColumnMap[0][$sortTableNames[$index+1]] as $val) {
                 foreach ($val as $val2) {
                     $_key .= $row[$val2['map_table_name']][$val2['map_field_name']];
                     //echo $_key.'===';
                 }                
             }
        }
        
        $_key = trim($_key, "_");
        
        print_r($sortTableNames[$index+1]);
//         print_r($listData[$sortTableNames[$index+1]][$_key]);
        foreach ($listData[$sortTableNames[$index+1]][$_key] as $val2) {
            
            $row[$sortTableNames[$index+1]] = $val2;            
            if ( $index == count($sortTableNames) - 2 ) {                
                $schemeRow = array();
                foreach ($schemeColumnList as $schemeColumnRow  ) {
                     if ( $schemeColumnRow->column_id ) {
                         $val =  $row[$schemeColumnRow->table_name][$schemeColumnRow->column_field];
                         if (! empty($schemeColumnRow->column_expression)) {
                             eval($schemeColumnRow->column_expression);                             
                         }
                         $schemeRow[$schemeColumnRow->column_order] = $val;
                     } else {
                         $schemeRow[$schemeColumnRow->column_order] = $schemeColumnRow->column_field;
                     }                        
                }               
                $data[] = $schemeRow;
            } else {               
                $index++;
                $this->_schemeRow($row, $index, $data, $sortTableNames, $listData, $schemeColumnList);
            }           
        }
    }        
   
}
?>
