<?php
/**
 * @desc 自动取号
 * @author Gordon
 */
class AutoCode extends SystemsModel {
    
    const INCREATE_TYPE_DEF = 0;
    const INCREATE_TYPE_MON = 1;
    const INCREATE_TYPE_DAY = 2;
    const INCREATE_TYPE_HOUR = 3;
	
    private static $_instance;
    
    public static function getInstance() {
        if( !self::$_instance instanceof self ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_auto_code';
    }
	
      
    /**
     * get page list
     * 
     * @return array
     */
    public function getPageList() {      
        $this->_initCriteria();         
        if (! empty($_REQUEST['code_type']) ) {
            $codeType = trim($_REQUEST['code_type']);
            $this->criteria->addCondition("code_type = '{$codeType}'");  
        }
        $this->_initPagination( $this->criteria);
        $models = $this->findAll($this->criteria);
        
        return array($models, $this->pages);
    }

    /**
     * @desc 自动取号
     * @param  string $type
     * @return string
     * @author yangsh
     * @since 2016-09-23
     */
    public static function getCodeNew($type) {
        $info = self::getByCodeType($type);
        if (empty($info)) {
            return false;
        }
        $platformCode = 'COMMON';//公用平台
        $config = ConfigFactory::getConfig('imageKeys');
        if( !isset($config[ $platformCode ]) ){
            throw new CException(Yii::t('system', 'Server Does Not Exists'));
        }
        $url = $config[ $platformCode ] ['url'] [ __FUNCTION__ ]; 
        $response = Yii::app()->curl->getByRestful($url, array('uebcode'=>$type));
        $res = json_decode($response,true);
        return empty($res) || $res['status'] != 'succ' ||trim($res['result']) == '' ? false : trim($res['result']);
    }
    
    /**
     * @desc 根据类型获取编号
     * @param string $type
     * @throws CException
     * @return mixed
     */   
    public static function getCode($type) {
        $info = self::getByCodeType($type);
        $formate = $info['code_format'];
        $datetime = date('Y-m-d H:i:s');  
        $datetime = str_replace(array(' ', ':'), array('-','-'), $datetime);
        $timeArr = explode("-", $datetime);
        $timeArr[0] = substr($timeArr[0], 2, 2);         
        $search = array('{Y}','{M}','{D}','{H}','{prefix}', '{suffix}');
        $replace = array($timeArr[0], $timeArr[1], $timeArr[2], $timeArr[3],$info['code_prefix'], $info['code_suffix']);
        $formate = str_replace($search, $replace, $formate);     
        $reset = self::reset($timeArr, $info['code_increate_type'], $info['code_increate_tag']);
        if (! empty($info['code_fix_length']) ) {
            $numLen = $info['code_fix_length'] + 5 - strlen($formate);
            if ( $numLen < 1 ) {
                throw new CException(Yii::t('excep','Fixed length setting is not enough.'));
            }
            if ( empty($info['code_increase_num']) || $reset ) {
               $num = $info['code_min_num'];
            } else {
                $num = $info['code_increase_num'] + 1;
                if ( strlen($num) > $numLen ) {
                    throw new CException(Yii::t('excep','Value is beyond the fixed length.'));
                }
            }
            if (! empty( $info['code_max_num']) && $num > $info['code_max_num']) {
                throw new CException(Yii::t('excep','Yii application can only be created once.'));
            }
            $codeNum = str_pad($num, $numLen, '0', STR_PAD_LEFT); 
        } else {
            if ( empty($info['code_increase_num']) || $reset) {
               $num = $info['code_min_num'];
            } else {
                $num = $info['code_increase_num'] + 1;              
            }           
            if ( ! empty( $info['code_max_num']) &&$num > $info['code_max_num']) {
                throw new CException(Yii::t('excep','Yii application can only be created once.'));
            }
            $codeNum = $num;
        }        
        $formate = str_replace('{num}', $codeNum,  $formate);
        $data = array(
            'code_increase_num'  => $num, 
            'code_increate_tag'  => self::getIncreateTag($timeArr,  $info['code_increate_type']),
        );
        $flag = self::model()->getDbConnection()->createCommand()
               ->needRowCount()
               ->update('ueb_system.'.self::tableName(), $data, " id = {$info['id']}");
        if (! $flag ) {           
            return self::getCode($type);
        }
        return $formate;
    }
    
    /**
     * @desc 检测重置
     * @param type $timeArr
     * @param type $increateType
     * @param type $increateTag
     */
    public static function reset($timeArr, $increateType, $increateTag) {      
        switch ($increateType) {
            case self::INCREATE_TYPE_DEF;
                return false;
                break;
            case self::INCREATE_TYPE_MON:
                if ( $timeArr[1] == $increateTag ) {
                    return false;
                }               
                break;
            case self::INCREATE_TYPE_DAY :
                if ( $timeArr[2] == $increateTag ) {
                    return false;
                }               
                break;
            case self::INCREATE_TYPE_HOUR:
                if ( $timeArr[3] == $increateTag ) {
                    return false;
                }               
                break;
            default:
                throw new CException(Yii::t('excep', 'Increate type error'));               
        }
        
        return true;
    }
    
    
    public static function getIncreateTag($timeArr, $increateType) {
        switch ($increateType) {
            case self::INCREATE_TYPE_DEF;
                return null;
                break;
            case self::INCREATE_TYPE_MON:               
                return $timeArr[1];
                break;
            case self::INCREATE_TYPE_DAY :              
                return $timeArr[2];
                break;
            case self::INCREATE_TYPE_HOUR:               
                return $timeArr[3];
                break;
            default:
                throw new CException(Yii::t('excep','Increate type error'));
                
        }
    }
    
    /**
     * @desc 根据编号CODE获得取号规则
     * @param string $codeType
     * @throws CException
     * @return mixed
     */
    public static function getByCodeType($codeType) {
        $info = self::model()->getDbConnection()->createCommand()
                ->select('*')
                ->from('ueb_system.'.self::tableName())
                ->where("code_type = :type", array( ':type' => $codeType))
                ->queryRow();
        if ( empty($info) ) {
            throw new CException(Yii::t('excep', 'No configuration code type information'));
        }
        return $info;
    }
}