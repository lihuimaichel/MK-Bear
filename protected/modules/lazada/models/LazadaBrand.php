<?php
/**
 * @desc Lazada品牌
 * @author Gordon
 * @since 2015-08-13
 */
class LazadaBrand extends LazadaModel{
    
    const EVENT_NAME = 'get_brands';
    
    /** @var string 异常信息*/
    public $exception = null;
    
    public $operation;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules(){}
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_lazada_brand';
    }
    
    public function attributeLabels(){
        return array(
            'id'                    => Yii::t('system', 'No.'),
            'name'                  => Yii::t('lazada', 'Name'),
        );
    }
    
    /**
     * @desc 生成条件搜索
     */
    public function filterOptions(){
        $result = array(
            array(
                'name'      => 'name',
                'type'      => 'text',
                'search'    => 'LIKE',
                'alias'     => 't',
        	),
        );
        return $result;
    }
    
    /**
     * @desc 更新品牌
     */
    public function updateBrands($accountID=''){
        $request = new GetBrandsRequest();
        if(!$accountID){
            $account = LazadaAccount::getAbleAccountByOne();
            $apiAccountID = $account['account_id'];
            $siteID = $account['site_id'];
        }
        $response = $request->setSiteID($siteID)->setAccount($apiAccountID)->setRequest()->sendRequest()->getResponse();
        if( $request->getIfSuccess() ){
            return $response->Body->Brands->Brand;
        }else{
            $this->setExceptionMessage($request->getErrorMsg());
            return false;
        }
    }
    
    /**
     * @desc 根据code更新品牌信息
     * @param unknown $code
     * @param unknown $params
     */
    public function updateBrandByCode($code, $params){
        return $this->db->createCommand()->update(self::tableName(), $params);
    }
    
    /**
     * @desc 新增品牌记录
     */
    public function saveRecord($params){
        return $this->dbConnection->createCommand()->insert(self::tableName(), $params);
    }
    
    /**
     * @desc 删除品牌数据
     * @return number
     */
    public function deleteBrands(){
        return $this->dbConnection->createCommand()->delete(self::tableName());
    }
    
    /**
     * (non-PHPdoc)
     * @see UebModel::search()
     */
    public function search(){
        $sort = new CSort();
        $sort->attributes = array('defaultOrder'=>'id');
        $dataProvider = parent::search(get_class($this), $sort);
        $data = $this->addition($dataProvider->data);
        $dataProvider->setData($data);
        return $dataProvider;
    }
    
    /**
     * @desc 添加信息
     * @param array $data
     */
    public function addition($data){
        foreach($data as $k=>$item){
            $data[$k]->operation = '<a class="btnSelect" href="javascript:$.bringBack({brand:\''.$item->name.'\'});">'.Yii::t('system','select').'</a>';
        }
        return $data;
    }
    
    /**
     * @desc 获取品牌列表
     */
    public static function getBrandList(){
        $output = array();
        $list = LazadaBrand::model()->dbConnection->createCommand()
                ->select('id,name')
                ->from(self::tableName())
                ->queryAll();
        foreach($list as $item){
            $output[$item['id']] = $item['name'];
        }
        return $output;
    }
    
    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
}