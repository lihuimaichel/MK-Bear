<?php 
/**
 * @desc paypal账号Model
 * @author Gordon
 */
class PaypalAccount extends SystemsModel{
    
	const ACCOUNT_STATUS_ENABLE = 1; 
	const ACCOUNT_STATUS_DISABLE = 0;
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @desc 表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
    	return 'ueb_paypal_account';
    }

    /**
     * [getOneByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getOneByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        $cmd->limit(1);
        return $cmd->queryRow();
    }

    /**
     * [getListByCondition description]
     * @param  string $fields [description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     * @author yangsh
     */
    public function getListByCondition($fields='*', $where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select($fields)
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }      
        
    /**
     * @desc 根据交易号获取交易信息
     * @param string $transactionID  67F935153Y1306106
     * @param string $platformCode
     * @param number $paypalAccountID
     * @return Ambigous <unknown, multitype:mixed >
     */
	public function getPaypalTransactionByTransactionID($transactionID, $platformCode, $paypalAccountID = 0){
        $result     = array();
        $where      = 'platform_code = "'.$platformCode.'" ';
        $where1     = $where. " AND id != '{$paypalAccountID}' AND status=1 ";
        $infos      = array();
        if( $paypalAccountID ){
            $where  .= ' AND id = '.$paypalAccountID;
            $infos = $this->getListByCondition('id',$where);
        }
        if (empty($infos)) {
            $infos = $this->getListByCondition('id',$where1);
        }
        if (!empty($_REQUEST['debug'])) {
            MHelper::printvar($infos,false);
        }
        if (!empty($infos)) {
            $permissionDeniedCount = 0;//统计Permission denied次数
            foreach($infos as $item){
                $paypal     = new GetTransactionDetailsRequest();
                $paypal->setTransactionID($transactionID);
                $response = $paypal->setAccount($item['id'])->setRequest()->sendRequest()->getResponse();
                if (!empty($_REQUEST['debug'])) {
                    MHelper::printvar($response,false);
                }
                if ( isset($response['L_ERRORCODE0']) && trim($response['L_ERRORCODE0']) == '10007' && strtoupper(trim($response['L_SHORTMESSAGE0'])) == 'PERMISSION DENIED') {
                    $permissionDeniedCount++;
                }                
                if( $paypal->getIfSuccess() && $transactionID==$response['TRANSACTIONID'] ){
                    $result = $response;
                    $result['paypal_account_id'] = $item['id'];         
                    break;                  
                }
            }
        }
        //如果与请求次数相等，则设置该交易无效，前提是超过3天的订单
        if (!empty($infos) && count($infos) == $permissionDeniedCount ) {
            return false;
        }

        return $result;
	}

    /**
     * @desc 根据交易号获取交易信息
     * @param string $transactionID  67F935153Y1306106
     * @param string $platformCode
     * @param number $paypalAccountID
     * @return Ambigous <unknown, multitype:mixed >
     */
    public function getPaypalTransactionByCondition($transactionID, $platformCode, $paypalAccount = ''){
        $result     = array();
        $where      = "platform_code = '{$platformCode}' ";
        $where1     = $where. " AND email != '{$paypalAccount}' AND status=1";
        $infos      = array();
        if( $paypalAccount ){
            $where  .= " AND email = '{$paypalAccount}'";
            if (!empty($_REQUEST['debug'])) echo $where."<br>";
            $infos = $this->getListByCondition('id,email',$where);
        }
        if (empty($infos)) {
            if (!empty($_REQUEST['debug'])) echo $where1."<br>";
            $infos = $this->getListByCondition('id,email',$where1);
        }
        if (!empty($_REQUEST['debug'])) {
            MHelper::printvar($infos,false);
        }
        if (!empty($infos)) {
            $permissionDeniedCount = 0;//统计Permission denied次数
            foreach($infos as $key => $item){
                $paypal     = new GetTransactionDetailsRequest();
                $paypal->setTransactionID($transactionID);
                $response   = $paypal->setAccount($item['id'])->setRequest()->sendRequest()->getResponse();
                if (!empty($_REQUEST['debug'])) {
                    MHelper::printvar($response,false);
                }
                if ( isset($response['L_ERRORCODE0']) && trim($response['L_ERRORCODE0']) == '10007' && strtoupper(trim($response['L_SHORTMESSAGE0'])) == 'PERMISSION DENIED') {
                    $permissionDeniedCount++;
                }
                if( $paypal->getIfSuccess() && $transactionID==$response['TRANSACTIONID'] ){
                    $result = $response;
                    $result['paypal_account_id'] = $item['id'];   
                    $result['paypal_account'] = $item['email'];      
                    break;                  
                }
            }
        }
        //如果与请求次数相等，则设置该交易无效，前提是超过3天的订单
        if (!empty($infos) && count($infos) == $permissionDeniedCount ) {
            return false;
        }

        return $result;
    }
	
	/**
	 * @desc 获取paypal账号信息
	 * @param int $id
	 */
	public function getPaypalInfoById($id){
	    static $params = array();
	    if( !isset($params[$id]) ){
	        $params[$id] = $this->dbConnection->createCommand()
	                       ->select('*')
	                       ->from(self::tableName())
	                       ->where('id = '.$id)
	                       ->queryRow();
	    }
	    return $params[$id];
	}
	
	public function getPaypalAccountBySalePrice($salePrice, $currency='USD'){
		$paypalAccount = "";
		//10美金以下的是chinatera 10美金以上的是thebestore
		//汇率转换
		if($currency != 'USD'){
			$rate = CurrencyRate::model()->getRateToOther($currency);
			$salePrice *= $rate;
		}
		//2016-11-18 调整
		if($salePrice > 10){
			//$paypalAccount = "pay@newfrog.com";
			$paypalAccount = "thebestore@126.com";
		}else{
			$paypalAccount = "chinatera@gmail.com";
			//$paypalAccount = "payment@newfrog.com";
		}
		return $paypalAccount;
	}


    /**
     * @desc 获取paypal帐号列表,默认是ebay平台的
     * @return multitype:string
     */
    public static function getPaypalList($platformCode){
       
        $item = array();
        $list = self::getAblePaypalList($platformCode);
        if ($list){
            foreach ($list as $val){
                $item[$val['id']] = $val['email'];
            }
        }
        return $item;
    }

    /**
     * @desc 获取已启用paypal帐号,默认是ebay平台的
     * @return multitype:string
     */
    public static function getAblePaypalList($platformCode=Platform::CODE_EBAY){
        $self = new self();
        return $self->getDbConnection()->createCommand()
                              ->from(self::tableName())
                              ->select("*")
                              ->where("status = 1")
                              ->andWhere("platform_code = '".$platformCode."'")
                              ->queryAll();
    }
}
?>