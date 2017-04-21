<?php
/**
 * @desc Aliexpress 站内信/订单留言
 * @author wx
 * @since 2015-12-03
 */
class AliexpressAddMsg extends AliexpressModel{
    
    const EVENT_ADDMSG = 'addmsg';		//添加订单备注
    
    /**@var 付款到上传的小时数*/
    const HOUR_UPLOAD = 48;
    
    /** @var int 账号ID*/
    public $_accountID = null;
    
    /** @var string 异常信息*/
    public $exception = null;
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 切换数据库连接
     * @see AliexpressModel::getDbKey()
     */
    public function getDbKey() {
        return 'db_oms_order';
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_order';
    }
    
    /**
     * @desc 设置异常信息
     * @param string $message
     */
    public function setExceptionMessage($message){
        $this->exception = $message;
    }
    

    /**
     * @desc 获取异常信息
     * @return string
     */
    public function getExceptionMessage(){
        return $this->exception;
    }
    
    /**
     * @desc 添加订单留言
     * @param array $shippedData
     */
    public function uploadAddMsg( $uploadData ){
    	
    	try {
    		$request = new AddMsgRequest();
    		$request->setChannelId($uploadData['channelId']);
    		$request->setBuyerId($uploadData['buyerId']);
    		$request->setContent($uploadData['content']);
    		$request->setMsgSources($uploadData['msgSources']);
    		$response = $request->setAccount($this->_accountID)->setRequest()->sendRequest()->getResponse();
    		if ( $request->getIfSuccess() ) {
    			return true;
    		} else {
    			$this->setExceptionMessage($request->getErrorMsg());
    			return false;
    		}
    	} catch (Exception $e) {
    		$this->setExceptionMessage($e->getMessage());
    		return false;
    	}
    	return true;
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
    	$this->_accountID = $accountID;
    }
}