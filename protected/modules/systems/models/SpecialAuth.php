<?php
/**
 * @package Ueb.modules.SpecialAuth.models
 * @author Gordon
 * @since 2014-06-09
 */
class SpecialAuth extends SystemsModel {

    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    
    /**
     * @return string the associated database table name
     */
    public function tableName() {	
    	return 'ueb_special_auth_config';
    }
    
    /**
     * 根据model和请求的action找出相关的特殊权限配置
     * @param string $model
     * @param string $action
     * @return array
     */
    public function getAllConfig($modelName='',$action=''){
    	$where = '1=1';
    	if( $action ){
    		$where .= ' AND (control_action IN ("'.$action.'","*") )';	
    	}
    	if( $modelName ){
	    	$model = UebModel::model($modelName);
    	}
    	$list =$this->dbConnection->createCommand()
    				->select('*')
    				->from(self::tableName())
    				->where($where)
    				->queryAll();	
    	$specialAuthList = array();
    	$result = array();
    	foreach($list as $item){
    		if( $item['control_action'] ){//筛去没有action配置的
    			if( isset($model) && ( in_array($item['related_column'], $model->attributeNames()) || property_exists($model,$item['related_column']) )  ){//如果传递了model，则检查该model是否有特殊权限属性
		    		$specialAuthList[$item['from_table']][$item['related_column']][$item['control_action']] = $item;
    			}elseif( !$modelName ){
    				$specialAuthList[$item['from_table']][$item['related_column']][$item['control_action']] = $item;
    			}
    		}
    	}
    	foreach($specialAuthList as $fromTable=>$arr1){
    		foreach($arr1 as $relatedColumn=>$arr2){
    			if( isset($arr2['*']) ){//如果是作用于所有action,则其他action数据不需要
    				$arr2 = array(
    					'*' => $arr2['*'],
    				);		
    			}
    			foreach($arr2 as $controlAction=>$arr3){
    				$result[$fromTable][$relatedColumn][$controlAction] = $arr3;
    			}
    		}	
    	}
    	return $result;
    }
    
    /**
     * 检测当前登录人的特殊权限
     * @param object $auth
     * @return array
     */
    public function checkSpecialAuth($auth=null){
    	$specialAuth = $this->getAllConfig();
    	$getAuth = array();
    	if( !$auth ){
    		$auth = Yii::app()->authManager;
    	}
    	foreach($specialAuth as $table=>$arr1){
    		foreach($arr1 as $column=>$arr2){
    			foreach($arr2 as $effactAction=>$arr3){
    				if( $arr3['from_column'] ){
    					$valueArr=Yii::app()->db->createCommand()
    					->select($arr3['from_column'])
    					->from($arr3['from_table'])
    					->queryColumn();
    					 
    					$getAuth[$arr3['related_column']] = array('');//默认送空值进去
    					foreach($valueArr as $value){
    						$authAction = 'resource_auth_specialAuth_'.str_replace('_', '-', $arr3['related_column']).'@'.$value;
    						if( !AuthItem::model()->exists("name = '{$authAction}'") ){
    							$auth->createOperation($authAction, $authAction);
    						}
    						if( UebModel::checkAccess($authAction) ){//检测该属性值的权限
    							array_push($getAuth[$arr3['related_column']], $value);
    						}
    					}
    				}
    			}
    		}
    	}
    	return $getAuth;
    }
}