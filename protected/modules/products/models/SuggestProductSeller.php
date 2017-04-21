<?php
/**
 * @author wx 
 * @since 2015-08-31
 *
 */
class SuggestProductSeller extends ProductsModel{
	
	
	/**
	 * Returns the static model of the specified AR class.
	 * @return CActiveRecord the static model class
	 */
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	public function tableName(){
		return 'ueb_suggest_product_seller';
	}

	public function columnName() {
		return MHelper::getColumnsArrByTableName(self::tableName());
	}
	
	public function rules()
	{
		return array(
			array('account_id,user_name,platform_code,status,create_time,create_user_id','safe')
		);
	}
	
	/**
	 * Declares attribute labels.
	 * @return array
	 */
	public function attributeLabels() {
		return array(
				'id'                    		=> Yii::t('system', 'No.'),
				'account_id'					=> Yii::t('suggestproduct', 'Account Id'),
				'item_id'						=> Yii::t('suggestproduct', 'Item Id')
		);
	}
	
	/**
	 * get search info
	 */
	
	public function search() {
		$sort = new CSort();
		$sort->attributes = array(
				'defaultOrder'  => 't.create_time',
		);
		
		$criteria = null;
		$criteria = $this->_setCDbCriteria();
		
		$dataProvider = parent::search(get_class($this), $sort, array(), $criteria);
		$data = $this->addition($dataProvider->data);
		$dataProvider->setData($data);
		return $dataProvider;
	}
	
	protected function _setCDbCriteria(){
		$criteria = new CDbCriteria;
		$criteria->select = 't.*';
		return $criteria;
	}
	
	/**
	 * addition information
	 * @param type $dataProvider
	 */
	public function addition($data) {
		return $data;
	}
	
	
    /**
     * filter search options
     * @return type
     */
    
    public function filterOptions() {}
    
    /**
     * @desc 保存信息
     * @param unknown $data
     * @return boolean
     */
    public function saveSellerInfo($data) {
    	$model = new self();
    
    	foreach ($data as $key => $value) {
    		$model->$key = $value;
    	}
    	$model->setIsNewRecord(true);
    	if ($model->save()) {
    		return $model->id;
    	}
    	return false;
    }
    
}