<?php
class LazadaAccountConfig extends LazadaModel{
	
	/** @var tinyint 自动刊登数量*/
    const PUBLISH_COUNT = 'publish_count';
    
    /** @var tinyint 是否自动推送*/
    const IF_ADJUST_COUNT = 'ifadjust_count';
    
    /** @var tinyint 推送站点*/
    const PUBLISH_SITE_ID = 'publish_site_Id';
	
    public $publish_count = '';
    
    
    public $adjust_count = '';
    
    
    public $publish_site_id = '';
    
	public static function model($className = __CLASS__) {
		return parent::model($className);
	}
	
	/**
	 * @desc 数据库表名
	 * @see CActiveRecord::tableName()
	 */
	public function tableName() {
		return 'ueb_lazada_account_config';
	}
	
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules(){}
	
	/**
	 * @desc 属性翻译
	 */
	public function attributeLabels() {
		return array(
				'account_id'					    => Yii::t('system', 'No.'),
				'publish_count'				 	    => Yii::t('system', 'Publish Count'),
				'ifadjust_count'         		 	=> Yii::t('system', 'IfAdjust Count'),
				'publish_site_id'         		 	=> Yii::t('system', 'Publish Site Id'),
				
		);
	}
	
	
		
	/**
	 * @desc 根据账号ID获取账号信息
	 * @param int $id
	 */
	public static function getAccountconfigInfoById($accountID){
		$flag = false;
		if( !is_array($accountID) ){
			$flag = true;
			$id = array($accountID);
		}
		$sql = LazadaAccount::model()->dbConnection->createCommand()->select('*')->from(self::model()->tableName())->where('account_id IN ('.implode(',', $accountID).')');
		if( $flag ){
			return $sql->queryRow();
		}else{
			return $sql->queryAll();
		}
	}	
		
	
	/**
	 * @desc 获取账号基本信息
	 */
	public function loadModelByaccountID($accountID){
		$model =$this->findByAttributes(array('account_id' => $accountID));
		if($model===false){
			throw new CHttpException ( 404, Yii::t ( 'app', 'The requested page does not exist.' ) );
		}else{
			return $model;
		}
	}
	
	/**
	 * @desc 获取账号基本信息
	 */
	public function loadModel($id){
		$model =$this->findByPk($id);
		if($model===false){
			throw new CHttpException ( 404, Yii::t ( 'app', 'The requested page does not exist.' ) );
		}else{
			return $model;
		}
	}
	
	
	
	
	
}