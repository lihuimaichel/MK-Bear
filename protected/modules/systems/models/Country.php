<?php
/**
 * @desc 国家Model
 * @author Gordon
 * @since 2015-06-24
 */
class Country extends SystemsModel {
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
	public function tableName(){
		return 'ueb_country';
	}
    
	/**
	 * @desc 根据国家二字码获取国家英文全称
	 * @param string $abbr
	 */
	public function getEnNameByAbbr($abbr){
	    $info = $this->getCountryInfoByAbbr($abbr);
	    if( !empty($info) ){
	        return $info['en_name'];
	    }
	    return '';
	}
	
	/**
	 * @desc 根据国家二字码获取国家信息
	 * @param string $abbr
	 */
	public function getCountryInfoByAbbr($abbr){
		$param = array();
		if( !isset($param[$abbr]) ){
			$param[$abbr] = $this->dbConnection->createCommand()
								->select('*')
								->from(self::tableName())
								->where('en_abbr = "'.$abbr.'"')
								->order('priority asc')
								->limit(1)
								->queryRow();
		}
		return isset($param[$abbr]) ? $param[$abbr] : '';
	}

	
	public function getCountryInfoByshipCountryName($abbr,$shipCountryName){
		$param = array();
		if( !isset($param[$abbr]) ){
			$param[$abbr] = $this->dbConnection->createCommand()->select('*')->from(self::tableName())->where('en_abbr = "'.$abbr.'"')->andWhere('en_name = "'.$shipCountryName.'"')->queryRow();
		}
		if( $param[$abbr] ){
			return $param[$abbr]['en_name'];
		}
		 
		return '';
	}
	
	/**
	 * @desc 根据国家全称获取国家二字码
	 * @param string $ename
	 */
	public function getAbbrByEname($ename){
		if(empty($ename)){
			return "";
		}
	    $info = $this->getCountryInfoByEname($ename);
	    if( !empty($info) ){
	        return $info['en_abbr'];
	    }
	    return '';
	}
	
	/**
	 * @desc 根据国家全称获取国家信息
	 * @param string $ename
	 */
	public function getCountryInfoByEname($ename){
	    /* static $param = array();
	    if( !isset($param[$ename]) ){ */
	        $param[$ename] = $this->dbConnection->createCommand()->select('*')->from(self::tableName())->where('en_name = "'.$ename.'"')->queryRow();
	    //}
	    return $param[$ename];
	}
	
	public function getCountryByCondition( $conditions,$field='*' ){
		empty($conditions) && $conditions = '1';
		$ret = $this->dbConnection->createCommand()
				->select( $field )
				->from($this->tableName())
				->where($conditions)
				->queryAll();
		return $ret;
	}
	
	/**
	 * @desc 根据国家英文全称获取中文名
	 * @param string $ename
	 */
	public function getCountryCnameByEname($ename){
		static $countries = array();
		if( !isset($countries[$ename]) ){
			$countries[$ename] = $this->getDbConnection()->createCommand()->from($this->tableName())->where('en_name=:en_name',array(':en_name' => strtolower($ename)))->queryRow();
			//$countries[$ename] = $this->db_oms_system->find('en_name=:en_name',array(':en_name' => strtolower($ename)));
		}
		return $countries[$ename]['cn_name'];
	}
}