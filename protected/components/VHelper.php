<?php

/**
 * View Helper Class
 * @package Application.components
 * @auther Bob <zunfengke@gmail.com>
 */
class VHelper{
	const APPLY_NO 		= 0;//采购未请款
	const APPLY_PART	= 1;//部分请款
	const PAY_NO 		= 2;//全额请款\未付款
	const PAY_HAVE 		= 3;//全付款
	const PAY_PART 		= 4;//部分付款
	
	public $_disable = 0;
	public $_enable = 1;
	
    /**
     * get status lable 
     * 
     * @param type $status
     */
    public static function getStatusLable($status) {
        if ($status == 1) {
            echo '<font color="green" >' . Yii::t('system', 'Enable') . '</font>';
        } else {
            echo '<font color="red" >' . Yii::t('system', 'Disable') . '</font>';
        }
    }
    
    public static function getFinishLabel($status){
    	if ($status == 1) {
    		echo '<font color="green" >' . Yii::t('system', 'Finished') . '</font>';
    	} else {
    		echo '<font color="red" >' . Yii::t('system', 'Unfinish') . '</font>';
    	}	
    }
    
    /**
     * @desc 以红色粗体显示
     * @param string $string
     */
    public static function getRedBoldShow($string){
        return '<font style="color:red;font-weight:bold;">'.$string.'</font>';
    }
    
    /**
     * @desc 以粗体显示
     * @param string $string
     */
    public static function getBoldShow($string){
        return '<font style="font-weight:bold;">'.$string.'</font>';
    }
    
    /**
     * @desc 获取运行状态标签
     * @param tinyint $status
     */
    public static function getRunningStatusLable($status, $label = '') {
        switch ( $status ) {
            case 0:
                return '<font style="font-weight:bold;">'.($label ? $label : Yii::t('system', 'Default')). '</font>';
                break;
            case 1:
            case 2:
            case 3:
                return '<font style="color:#CC9900;font-weight:bold;">'.($label ? $label : Yii::t('system', 'Running')). '</font>';
                break;
            case 4:
            case 6:
                echo '<font style="color:#009933;font-weight:bold;">' . ($label ? $label : Yii::t('system', 'Success')). '</font>';
                break;
            case 5:
            case 7:
                echo '<font style="color:#FF0000;font-weight:bold;">' . ($label ? $label : Yii::t('system', 'Failure')). '</font>';
                break;
        }
    }
    
    
    /**
     * get log status lable 
     * 
     * @param type $status
     */
    public static function getLogStatusLable($status) {
        switch (strtolower($status)) {
            case 'success':          
                echo '<font color="green" >' . Yii::t('system', 'Success') . '</font>';
                break;
            case 'info':
                echo '<font color="green" >' . Yii::t('system', 'Info') . '</font>';
                break;
            case 'failure':    
                echo '<font color="red" >' . Yii::t('system', 'Failure') . '</font>';
                break;
            case 'error':
                echo '<font color="red" >' . Yii::t('system', 'Error') . '</font>';
                break;
            
        }     
    }
    
    /**
     * get all log status list
     *
     * @param type $status
     * @author Nick 2013-10-9
     */
    public static function getAllLogStatusList() {
    	return array(
    			'success' => Yii::t('system', 'Success'),
    			'info'    => Yii::t('system', 'Info'),
    			'failure' => Yii::t('system', 'Failure'),
    			'error'   => Yii::t('system', 'Error')
    	);
    }

    /**
     * get status config;
     * 
     * @return type
     */
    public static function getStatusConfig($type = null) {
        $config = array(
            1 => Yii::t('system', 'Enable'),
            0 => Yii::t('system', 'Disable')
        );
        if($type != null) return $config[$type];
        return $config;
    }
    
    
    public static function getSendTypeConfig() {
        return array(
            1 => Yii::t('system', 'System Message'),           
        );
    }
    
    /**
     * get msg status config
     */
    public static function getMsgStatusConfig() {             
        return array(          
            0  => Yii::t('system', Yii::t('system', 'Unread')),        
            1  => Yii::t('system', Yii::t('system', 'Reading')), 
            2  => Yii::t('system', Yii::t('system', 'Read')),          
        );      
    }
    
    /**
     * get increate type config
     */
    public static function getIncreateTypeConfig() {     
        return array(
            0 => Yii::t('system', 'Default'),        
            1 => Yii::t('system', 'By The Month'), 
            2 => Yii::t('system', 'By The Day'), 
            3 => Yii::t('system', 'By The Hour'),
        );
    }
    
    /**
     * get increate type config label
     */
    public static function getIncreateTypeLabel($status) {
        $config = self::getIncreateTypeConfig();
        
        return $config[$status];
    }
    
    

    /**
     * get messages status label 
     * 
     */
    public static function getMsgStatusLable($status) {
        if ($status == '0') {
            echo '<font color="red" >' . Yii::t('system', 'Unread') . '</font>';
        } else if ( $status == '1') {
            echo '<font color="blue" >' . Yii::t('system', 'Reading') . '</font>';
        } else {
            echo '<font color="green" >' . Yii::t('system', 'Read') . '</font>';
        }
    }
  
    public static function getSexConfig() {
        return array(
            '0' => Yii::t('users', 'Man'),
            '1' => Yii::t('users', 'Female')
        );
    }
 
    
    public static function getYesOrNoConfig($type = null) {
    	$config = array(
    		'0'    => Yii::t('common', 'Yes'),
    		'1'    => Yii::t('common', 'No'),
    	);
    	if ( $type !== null ) {
    		return $config[$type];
    	}
    	return $config;
    }
 
    /**
     * split the date format
     * @param date $date
     * @param $ymd:true or false ,get Y-m-d only
     * @return string
     */
    public function splitDate($date='0000-00-00 00:00:00',$ymd=false){
    	if(empty($date) || $date =='0000-00-00 00:00:00') return '-';
    	$dateArr = explode(' ',$date);
    	return $ymd==true ? $dateArr[0] : $dateArr[0].'<br>'.$dateArr[1];
    }
    
    /**
     * 
     * @param arrat $val
     * @return string
     */
    public function showHtmlByDataType($val=array()){
    	$html = '';
    	$html .= CHtml::label($val['column_title'].': ',$val['column_field'],array('style'=>'width:auto;text-align:right;font-weight:bold;'));
    	switch ($val['data_type']){
    		case ExcelSchemeColumn::_DATETIME:
    			if ($val['default_date']>0){
    				$default_date = MHelper::getDateDiff($val['default_date']);
    			}
    			$default_date_s = $_REQUEST['is_condition'][$val['db_name'].'.'.$val['table_name']][$val['column_field']][0] 
    							? $_REQUEST['is_condition'][$val['db_name'].'.'.$val['table_name']][$val['column_field']][0] : $default_date[0];
    			$default_date_e = $_REQUEST['is_condition'][$val['db_name'].'.'.$val['table_name']][$val['column_field']][1] 
    							? $_REQUEST['is_condition'][$val['db_name'].'.'.$val['table_name']][$val['column_field']][1] : $default_date[1];
    			$html .= CHtml::textField('is_condition['.$val['db_name'].'.'.$val['table_name'].']['.$val['column_field'].']'.'[0]', $default_date_s,
    			array('class'=>'date textInput','dateFmt'=>'yyyy-MM-dd HH:mm:ss'));
    			$html .= '<span style="padding:4px;">-</span>';
    			$html .= CHtml::textField('is_condition['.$val['db_name'].'.'.$val['table_name'].']['.$val['column_field'].']'.'[1]', $default_date_e,
    					array('class'=>'date textInput','dateFmt'=>'yyyy-MM-dd HH:mm:ss'));
    			$html .= CHtml::hiddenField('is_condition['.$val['db_name'].'.'.$val['table_name'].']['.$val['column_field'].']'.'[2]', 'timestamp',
    					array('class'=>'textInput','readonly'=>'readonly'));
    			break;
    		case ExcelSchemeColumn::_CHECKBOX:
    			$curModel = MHelper::getModelByTableName($val['table_name']);
    			$data = $curModel->queryPairs(array($val['column_field'],$val['column_field']));
    			if($data){
    				foreach ($data as $k=>$v){
    					$flag = isset($_REQUEST['is_checkbox'][$val['db_name'].'.'.$val['table_name']][$val['column_field']][$k]) ? true :false;
    					$html .= CHtml::checkBox( 'is_checkbox['.$val['db_name'].'.'.$val['table_name'].']['.$val['column_field'].']['.$k.']', $flag,
    							array('value' =>$v,'id' =>$val['column_field'].$k) );	//tan 9.19
    					$html .= $v.'&nbsp;&nbsp;';
    				}
    			}
    			break;
    		case ExcelSchemeColumn::_SELECT:
    			$curModel = MHelper::getModelByTableName($val['table_name']);
    			//$primaryKey = $curModel->getMetaData()->tableSchema->primaryKey;
    			$data = $curModel->queryPairs(array($val['column_field'],$val['column_field']));
    			$html .= CHtml::dropDownList('is_condition['.$val['db_name'].'.'.$val['table_name'].']['.$val['column_field'].']', 
    					$_REQUEST['is_condition'][$val['db_name'].'.'.$val['table_name']][$val['column_field']],
    					$data,array('empty' => Yii::t('system','Please Select')));
    			break;
    		case ExcelSchemeColumn::_NUMS:
    			$html .= CHtml::textField('is_condition['.$val['table_name'].']['.$val['column_field'].']'.'[0]',
    					$_REQUEST['is_condition'][$val['db_name'].'.'.$val['table_name']][$val['column_field']][0],array('class'=>'textInput'));
    			$html .= '<span style="padding:4px;">-</span>';
    			$html .= CHtml::textField('is_condition['.$val['db_name'].'.'.$val['table_name'].']['.$val['column_field'].']'.'[1]',
    					$_REQUEST['is_condition'][$val['db_name'].'.'.$val['db_name'].'.'.$val['table_name']][$val['column_field']][1],array('class'=>'textInput'));
    			break;
    		case ExcelSchemeColumn::_INPUT:
    			$html .= CHtml::textField('is_condition['.$val['db_name'].'.'.$val['table_name'].']['.$val['column_field'].']',
    			$_REQUEST['is_condition'][$val['db_name'].'.'.$val['table_name']][$val['column_field']],array('class'=>'textInput'));
    			break;
    		default:
    			$html = '';
    	}
    	return $html;
    }
           
    
    /**
     * 显示供应商id 隐藏域,purchases/views/purchaserequire/index.php
     * $columnName:input text name
     * $value :    input value
     * $id : key
     * $options :other option
     */
    public function showTextHidden($columnName,$value,$id,$options){
    	echo CHtml::hiddenField($columnName."[$id]",$value, $options);
    }
    	
    	
    	/**
    	 * 检测字符串是否包含某语言字符
    	 * @author Gordon
    	 * 2013-11-07
    	 */
    	public static function checkLang($string,$lang='China'){ 
    		$pattern = self::getLangPreg($lang);//获取正则表达式
    		if(!$pattern){
    			return false;   			
    		}   		
    		preg_match($pattern,$string,$arr);
    		if(empty($arr)){//如果数组为空，则没匹配到
    			return false;
    		}else{
    			return true;
    		}
    	}
    	
    	/**
    	 * 根据Unicode范围获取语言的正则表达式
    	 * 西里尔文(俄文):0400-052f;中文:u4e00-u9fa5;日文:u0800-u4e00;
    	 * @author Gordon
    	 * 2013-11-07
    	 */
    	public static function getLangPreg($lang){
    		$preg_arr = array(
    				'Russia' => '/[\x{0400}-\x{052f}]+/siu',
    				//'China' => '/^[\x{4e00}-\x{9fa5}]+$/u',
    				'China' => '/[\x{4e00}-\x{9fa5}]+/u',
    		);
    		return $preg_arr[$lang];
    	}

    	/**
    	 * get product status label
    	 */
    	public static function getProductStatusLabel($status) {
    		$config = UebModel::model('Product')->getProductStatusConfig();
    		return isset($config[$status]) ? $config[$status] : 'unknow';
    	}
    
}

?>