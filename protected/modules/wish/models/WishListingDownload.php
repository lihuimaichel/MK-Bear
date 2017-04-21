<?php
/**
 * Wish listing拉取
 * @author yangsh
 * @since 2016-06-20
 */
class WishListingDownload extends WishListing {
	
    const EVENT_NAME                     = 'get_product';

    /** @var integer 账号ID */
    public $_accountID                   = '';

    /** @var integer index */
    protected $_startIndex               = 0;     

    /** @var integer limit */
    protected $_limit                    = 250;

    /** @var integer since */
    protected $_since                    = '';        

    /** @var string 错误信息 */
    protected $_errorMessage             = '';
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 设置账号ID
     */
    public function setAccountID($accountID){
        $this->_accountID = $accountID;
        return $this;
    }
    
    public function setStartIndex($startIndex) {
        $this->_startIndex = $startIndex;
        return $this;
    }

    public function setLimit($limit) {
        $this->_limit = $limit;
        return $this;
    }

    public function setSince($since) {
        $this->_since = $since;
        return $this;
    }

    public function setErrorMessage($errMsg) {
        $this->_errorMessage = $errMsg;
        return $this;
    }

    /**
     * @desc 获取账号ID
     */
    public function getAccountID(){
        return $this->_accountID;
    }
    
    public function getStartIndex() {
        return $this->_startIndex;
    }

    public function getLimit() {
        return $this->_limit;
    }

    public function getSince() {
        return $this->_since;
    }

    public function getErrorMessage() {
        return $this->_errorMessage;
    }

    /**
     * 下载listing
     */
    public function startDownWishListing() {
        $accountID  = $this->_accountID;
        $index      = $this->_startIndex;
        $limit      = $this->_limit;
        $since      = $this->_since;

        $hasNextPage    = true;
        $total          = 0;
        $errMsgs        = '';
        while ($hasNextPage) {
            $request = new ListAllProductsRequest();
            $request ->setAccount($accountID);
            $request ->setStartIndex($index);
            $request ->setLimit($limit);
            if ($since != '') {
                $request->setSinceTime($since);
            }
            $response = $request->setRequest()->sendRequest()->getResponse();
            $index++;
            // MHelper::writefilelog('wish/wishlisting/'.date("Ymd").'/'.$accountID.'/response_'.$accountID.'_'.($index-1).'.log',print_r($response,true)."\r\n");
            //返回失败
            if (!$request->getIfSuccess()) {
                $log = $accountID.'#####'.$index.' occur error ##########'.$request->getErrorMsg()."\r\n";
                // MHelper::writefilelog('wish/wishlisting/'.date("Ymd").'/'.$accountID.'/request_occur_err_'.$accountID.'_'.($index-1).'.log', $log."\r\n"); 
                $errMsgs .= $request->getErrorMsg();
                break;
            }
            //保存listing数据
            $datas          = $response->data;
            $wishListing    = new WishListing();
            $wishListing    ->setAccountID($accountID);
            $isOk           = $wishListing->saveWishListing($datas);
            if (!$isOk) {
                $errMsgs .= $accountID.'#####'.$index.' occur error ##########'.$wishListing->getExceptionMessage()."\r\n";
            }
            $total += count($datas);
            unset($datas);
            //无下一页
            if (!isset($response->paging->next) || empty($response->paging->next)){
                break;
            }
            unset($response);
        }
        //echo "pull num:{$total} ,AccountId:{$accountID} finish";
        $logtxt = "pull num:{$total} ,AccountId:{$accountID} finish";
        // MHelper::writefilelog('wish/wishlisting/'.date("Ymd").'/'.$accountID.'/total_'.$accountID.'.log', $logtxt."\r\n");
        $this->setErrorMessage($errMsgs);
        return $errMsgs =='' ? true : false;
    }

    public function pullSingleItem($parentSku, $accountId, $saveToDatabase = true)
    {
        try{
            $request = new RetrieveProductsRequest();
            $response = $request->setAccount($accountId)->setParentSku($parentSku)->setRequest()->sendRequest()->getResponse();

            if (!$request->getIfSuccess()) {
                throw new \Exception(Yii::t('wish', 'Can not pull listing information.') . $request->getErrorMsg());
            }

            if ($saveToDatabase) {
                $wishListing  = new WishListing();
                $wishListing->setAccountID($accountId);
                $saved = $wishListing->saveWishListing(array($response->data));
                if (!$saved) {
                    throw new \Exception(Yii::t('wish', 'Can not save listing information'));
                }
                return $saved;
            }
            return $response->data;
            //return true;
        }catch (\Exception $e) {
            throw  new \Exception($e->getMessage());
        }
    }


}