<?php

class ProductRole extends ProductsModel
{
	public $pro_sku='';	
	CONST ROLE_CODE	= 'purchaser';
	CONST PRODCUT_DEVELOPERS='product_developers';	
	CONST RECIEVE_PACK_USER='recieve_pack_user';	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className=__CLASS__) {
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName() {
		return 'ueb_product_role_assign';
	}
    
    public function rules() {
        $rules = array(
            array('user_id,user_name,role_code,sku', 'required'), //pro_id,
        	array('user_id', 'numerical', 'integerOnly' => true),
            array('role_code,user_name,role_name', 'length', 'max' => 50),
        );        
        return $rules;
    }
    
    /**
     * @return array relational rules.
     */
    public function relations()
    {
    	return array();
    	
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
			'id'                    => Yii::t('system', 'No.'),
            'sku'    				=> 'SKU',
            'role_parent_code'		=> Yii::t('users', 'Parent Role Code'),
        	'role_code'				=> Yii::t('users', 'Role Code'),
        	'user_name'            	=> Yii::t('users', 'Assigner'),
        	'create_time'			=> Yii::t('users', 'Allocate time'),
        );
    }
    /**
     * get search info
     */
    public function search() {
    	$sort = new CSort();
    	$sort->attributes = array(
    			'defaultOrder'  => '',
    			'id',
    	);

    	return parent::search(get_class(UebModel::model('Product')), $sort);
		
    }
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
    	return array();
    
    }
    
       
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/products/productrole/prolist');
    } 
    
    /**
     * @desc 根据条件查询指定字段
     */
    public function getProductRoleByCondition( $condition,$field = '*' ){
    	$condition = empty($condition)?'1=1':$condition;
    	$ret = $this->dbConnection->createCommand()
		    	->select( $field )
		    	->from( $this->tableName() )
		    	->where( $condition )
		    	->queryAll();
    
    	return $ret;
    }

    /**
     * 通过sku获取角色用户id
     * @param string $sku
     * @return array
     */
    public function getRoleUserIdBySku($sku){
        $data =  $this->getDbConnection()->createCommand()
                        ->select('role_code,user_id')
                        ->from(self::tableName())
                        ->where("sku ='{$sku}'")
                        ->queryAll();
        $list=array();
        if (!empty($data)) {
            foreach ($data as $val){
                $list[$val['role_code']]=$val['user_id'];
            }
        } 
        return $list;
    } 


    /**
     * 根据SKU获取产品分配人员信息
     * $sku  $rle_code
     */
    public function getProductAssignBySku($sku,$role_code=null){
        $data =  $this->getDbConnection()->createCommand()
        ->select('user_name')
        ->from(self::tableName())
        ->where("sku ='".$sku."' and role_code='".$role_code."' ")
        ->order("id asc ")
        ->queryRow();
        if($data){
            return $data['user_name'];
        }else{
            return '-';
        }
    }    
    
}