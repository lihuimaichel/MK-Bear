<?php
/**
 * @desc Ebay 获取其他卖家产品
 * @author wx 
 * @since 2015-08-31
 */
class EbayproductotherController extends UebController{
    
    /**
     * @desc 获取其它卖家item list http://erp_market.com/ebay/ebayproductother/getothersellerlist/site_id/0/user_id/
     */
    public function actionGetothersellerlist(){
        $siteArr = array();
        $_REQUEST['site_id'] = isset($_REQUEST['site_id'])?$_REQUEST['site_id']:0; //默认取美国站点
        $_REQUEST['user_id'] = isset($_REQUEST['user_id'])?$_REQUEST['user_id']:'';
        //1.设置站点
        if( isset($_REQUEST['site_id']) ){
           	//1.验证站点是否可用 TODO
            $siteArr = array($_REQUEST['site_id']);
        }else{
            $siteArr = EbaySite::model()->getAbleSites();
        }
        
        //2.获取卖家账号列表
        $sellerList = array();
        $where = ' status = '.EbaySeller::STATUS_OPEN;
        if( !empty($_REQUEST['user_id']) ){
        	$where .= ' and user_name = "'.$_REQUEST['user_id'].'"';
        }
        $sellerList = UebModel::model('EbaySeller')->getSellerByCondition( $where,'id,user_name' );
        
        //3.取一个可用的来验证的账号
        $account = EbayAccount::model()->getAbleAccountByOne();
        
        //4.设置拉取请求参数
        $EndTimeFrom = date('Y-m-d\TH:i:s\Z',time()-3600*9);//开始时间
		$EndTimeTo = date('Y-m-d\T00:00:00\Z',time()+86400*7);//结束时间
        
        //5.开始拉取卖家item
        foreach($siteArr as $site){
        	$ebayProductOtherModel = new EbayProductOther();
            $ebayProductOtherModel->setAccountID($account['id']);
            $ebayProductOtherModel->setSite($site);
            foreach( $sellerList as $value ){
            	//6.准备日志
            	$logID = EbayLog::model()->prepareLog($account['id'],EbayProductOther::EVENT_NAME);
            	//7.设置日志为正在运行
            	EbayLog::model()->setRunning($logID);
            	$params = array(
            			'UserId'=>$value['user_name'],
            			'account_id'=>$value['id'],
            			'EndTimeFrom' => $EndTimeFrom,
            			'EndTimeTo' => $EndTimeTo,
            			'IncludeWatchCount' => true,
            			'IncludeVariations' => true
            		);
            	$flag = $ebayProductOtherModel->getSellerItemByCondition( $params );
            	//8.更新日志信息
            	if( $flag ){
            		EbayLog::model()->setSuccess($logID);
            	}else{
            		EbayLog::model()->setFailure($logID, $ebayProductOtherModel->getExceptionMessage());
            	}
            }
            
        }
        
        
    }
} 