<?php
/**
 * @package Ueb.modules.AmazonModel.models
 * 
 * @author wx
 */
class AmazonAsinImport extends AmazonModel { 
	//2016-02-03 add
	public static $accountPairs = array();
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
        return 'ueb_amazon_sku_asin_map';
    }
    /**
     * @return array() column name
     */
    public function columnName() {
    	return MHelper::getColumnsArrByTableName(self::tableName());
    }
    
    /**
	 * @return array validation rules for model attributes.
     */
	public function rules()
	{	      
		return array(
                     
		);
	}
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array( 
            'id'                            => Yii::t('system', 'No.'),
        	'sku'							=>	'SKU',
        	'account_id'					=>	'账号'	
        );
    }
    
    /**
	 * @return array relational rules.
	 */
	public function relations() {
        return array();       
    }
    
    /**
     * get search info
     */

    public function search() {
    	$sort = new CSort();
        $sort->attributes = array(
            'defaultOrder'  => 'id',                     
        );        
      	$dataProvider = parent::search(get_class($this), $sort);
      	$data = $this->addtions($dataProvider->data);
      	$dataProvider->setData($data);
      	return $dataProvider;
    }
    
    public function addtions($datas){
    	if(empty($datas)) return $datas;
    	foreach ($datas as &$data){
    		//账号名称
    		$data['account_id'] = self::$accountPairs[$data['account_id']];
    	}
    	return $datas;
    }
    
    /**
     * @desc  获取公司账号
     */
    public function getAccountList(){
    	if(self::$accountPairs == null)
    		self::$accountPairs = self::model('AmazonAccount')->getIdNamePairs();
    	return self::$accountPairs;
    }
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
        $result = array(       
        		array(
        				'name'=>'sku',
        				'type'=>'text',
        				'search'=>'LIKE',
        				'htmlOption' => array(
        						'size' => '22',
        				)
        		),
        		array(
        				'name'=>'account_id',
        				'type'=>'dropDownList',
        				'search'=>'=',
        				'data'=>$this->getAccountList()
        		),
        );
        return $result;
    }
    
    
    /**
     * order field options
     * @return $array
     */
    public function orderFieldOptions() {     
        return array(
        );
    }
    
    public static function getIndexNavTabId() {
    	return Menu::model()->getIdByUrl('/amazon/amazonasinimport/list');
    }
    
    /**
     * @desc 特殊虚拟仓发货 导入出货后的包裹，更新包裹状态等信息。
     * @param string $excelFile
     * @return string
     */
    public function saveAsinDataByExcel($excelFile){  //$type 仓库id
    	Yii::import('application.vendors.MyExcel.php');
    	
    	$data = new MyExcel();
    	
    	$excelData = $data->get_excel_con($excelFile);
    	
    	//return count($excelData);
    	//无数据 导入的数据$i <2
    	if(count($excelData)<2){
    		return 'no_excel_data';
    	}
    	
    	$result='';
    	$success = 0; //成功数量
    	$exist = 0;   //重复数量
    	$encryptSku = new encryptSku();
    	for($i=2;$i<= count($excelData);$i++){
    		$result= $this->saveAsinData($excelData[$i],$encryptSku);
    		if($result == 'success' ){
    			$success++;
    		}
    		if($result == 'exist' ){
    			$exist++;
    		}
    	}
    	return '成功导入'.$success.'条数据，其中剔除掉重复数据'.$exist.'条';
    }
    
    /**
     * @desc 保存
     * @author wx
     */
    public function saveAsinData( $packageData,$encryptSku ){
    	
    	$accountName = $packageData['A'];
    	$skuEncrypt = $packageData['B'];
    	$asin = $packageData['C'];
    	
    	//账号
    	$accountList = AmazonAccount::getIdNamePairs();
    	$accountList = array_flip($accountList);
    	//解密sku
    	$sku = $encryptSku->getAmazonRealSku2($skuEncrypt);
    	$accountId = isset($accountList[$accountName])?$accountList[$accountName]:0;
    	
    	$newData = array(
    			'account_id' => $accountId,
    			'sku' => $sku,
    			'sku_encrypt' => $skuEncrypt,
    			'asin' => $asin,
    	);
    	if($sku && $skuEncrypt && $asin){
    		$ret = $this->getAsinInfo($sku, $skuEncrypt, $asin, $accountId);
    		if( $ret['id'] ){
    			return 'exist';
    		}
    	}
    	
    	$flag = $this->saveNewData($newData);
    	
    	return 'success';
    }
    
    
    
    /**
     * 检测文件是否存在
     * @param unknown $path
     */
    /* public function createFolder($path){
    	if (!file_exists($path)){
    		createFolder(dirname($path));
    	}
    } */
    
    function createFolder($path)
    {
    	if (!file_exists($path))
    	{
    		createFolder(dirname($path));
    		mkdir($path, 0777);
    	}
    }
    
	public function saveNewData($data) {
		$model = new self();
		foreach($data as $key => $value){
			$model->setAttribute($key,$value);
		}
		$model->setIsNewRecord(true);
		if ($model->save()) {
			return $model->id;
		}
		return false;
	}
	
	/**
	 * @desc 查询一条数据
	 */
	public function getAsinInfo( $sku,$skuEncrypt,$asin, $accountID ){
		
		$ret = $this->dbConnection->createCommand()
				->select('*')
				->from(self::tableName())
				->where('sku="'.$sku.'"')
				->andWhere('sku_encrypt="'.$skuEncrypt.'"')
				->andWhere('asin="'.$asin.'"')
				->andWhere('account_id=:account_id', array(':account_id'=>$accountID))
				->queryRow();
		return $ret;
		
	}
 
	/**
	 * @desc 获取对应的sku列表
	 * @param unknown $conditions
	 * @param unknown $params
	 * @param unknown $limits
	 * @param unknown $select
	 * @return mixed
	 */
	public function getSkuListByCondition($conditions, $params, $limits, $select = "*"){
		$command = $this->getDbConnection()->createCommand()
						->from($this->tableName())
						->where($conditions, $params)
						->select($select);
		if($limits){
			$limitsarr = explode(",", $limits);
			$limit = isset($limitsarr[1]) ? trim($limitsarr[1]) : 0;
			$offset = isset($limitsarr[0]) ? trim($limitsarr[0]) : 0;
			$command->limit($limit, $offset);
		}
		return $command->queryAll();
	}  
   	
}