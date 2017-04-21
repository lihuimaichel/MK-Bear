<?php
/**
 * @desc Ebay站点
 * @author Michael
 * @since 2015-08-04
 */

class EbaySite extends EbayModel {
	const EBAY_MOTOR_SITEID = 100;
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_site_config';
    }

    /**
     * @desc 根据货币获取站点ID
     * @param unknown $code
     * @return Ambigous <string>
     */
/*    public static function getSiteIDByCurrency($code){
    	$siteIDs = array(
    			'USD' => '0',
    			'GBP' => '3',
    			'AUD' => '15',
    			'CAD' => '2',
    			'EUR' => '77',
    			'FRA' => '71',
    			'ES_' => '186',
    	);
    	return $siteIDs[$code];
    }*/
    
    /**
     * @desc 根据站点ID获取货币单位
     * @param unknown $siteID
     * @return Ambigous <string>
     */
    public static function getCurrencyBySiteID($siteID){
/*    	$currencies = array(
    			'0'		=> 'USD',
    			'3'		=> 'GBP',
    			'15'	=> 'AUD',
    			'2' 	=> 'CAD',
    			'77' 	=> 'EUR',
    			'71'	=> 'FRA',
    			'186' 	=> 'ES_',
    	);
*/
        $item = array();
        $list = self::getAbleSiteList();
        if ($list){
            foreach ($list as $val){
                $item[$val['site_id']] = $val['currency'];
            }
        }

    	return $item[$siteID];
    }
    
    /**
     * @desc 特殊语言的站点
     * @return multitype:string
     */
    public static function getSpecialLanguageSiteIDs(){
    	$siteIDs = array(
    			'71', '77','186'
    	);
    	return $siteIDs;
    }
    
    /**
     * @desc 根据站点获取特殊语言
     * @param unknown $siteid
     * @return Ambigous <string>
     */
    public static function getLanguageBySiteIDs($siteid){
    	$languages = array(
            '77'    => 'German',
            '71'    => 'French',
            '186'   => 'Spain',
//    		'0'		=>	'english',
  //  		'101'	=>	'Italy',
    	);
    	return isset($languages[$siteid]) ? $languages[$siteid] : '';
    }
    /**
     * @desc 获取全部的语言
     */
    public static function getAllLanguage(){
    	//@TODO
    }
    
    /**
     * @desc 获取站点id列表
     * @return multitype:
     */
    public static function getSiteIDs(){
    	$siteList = self::getSiteList();
    	$siteIDs = array_flip($siteList);
    	return $siteIDs;
    }
    /**
     * @desc 获取站点列表
     * @return multitype:string
     */
    public static function getSiteList(){
        /*
        return array(
                '0'=> 'US',
                '2'=> 'Canada',
                '3'=> 'UK',
                '15'=> 'Australia',
                '77'=> 'Germany',
                '71'=> 'France',
                '186' => 'Spain',
        );
        */
        $item = array();
        $list = self::getAbleSiteList();
        if ($list){
            foreach ($list as $val){
                $item[$val['site_id']] = $val['site_name'];
            }
        }
        return $item;
    }

    /**
     * 获取ebay平台站点ID与站点名称列表
     * @return array
     */
    public function getSiteCodeList() {
        return array('errorCode'=>'0','errorMsg'=>'ok','data'=>self::getSiteList());
    }

    /**
     * @desc 获取已启用站点列表
     * @return multitype:string
     */
    public static function getAbleSiteList(){
        $self = new self();
        return $self->getDbConnection()->createCommand()
                              ->from(self::tableName())
                              ->select("*")
                              ->where("status = 1")
                              ->queryAll();
    }

    /**
     * @desc 根据站点名称获取站点ID
     * @param unknown $sitename
     * @return Ambigous <>|number
     */
    public static function getSiteIdByName($sitename){
    	$siteIDs = self::getSiteIDs();
    	if(isset($siteIDs[$sitename])){
    		return $siteIDs[$sitename];
    	}else{
    		return -1;
    	}
    }
    
    /**
     * @desc 获取站点名称
     * @param unknown $siteid
     * @return Ambigous <string>
     */
    public static function getSiteName($siteid){
    	$siteList = self::getSiteList();
    	return isset($siteList[$siteid]) ? $siteList[$siteid] : '';
    }
    
    /**
     * @desc 检测账号和站点是否存在
     * @param unknown $accountID
     * @param unknown $siteID
     * @return boolean
     */
    public static function checkAccountSiteExist($accountID, $siteID){
    	$ebayAccountSite = new EbayAccountSite;
    	$info = $ebayAccountSite->find("account_id=:account_id AND site_id=:site_id", array(':account_id'=>$accountID, ':site_id'=>$siteID));
    	return $info ? true : false;
    }
    
    /**
     * @desc 获取站点账号信息
     * @param unknown $accountid
     * @param unknown $siteid
     * @return Ambigous <mixed, CActiveRecord, NULL, multitype:, multitype:unknown Ambigous <CActiveRecord, NULL> , unknown, multitype:unknown Ambigous <unknown, NULL> , multitype:unknown >
     */
    public static function getAccountSiteInfo($accountid,$siteid){
    	$ebayAccountSite = new EbayAccountSite;
    	$info = $ebayAccountSite->find("account_id=:account_id AND site_id=:site_id", array(':account_id'=>$accountID, ':site_id'=>$siteID));
    	return $info;
    }
    
    public static function getAllAccountSites(){
    	//@TODO
    }
    
    //按分组显示帐号
    public static function getGroupAllAccountSites(){
    	//@TODO
    }
    
    
    /**
     * @desc 允许发多张主图站点(暂时美国 ，加拿大，澳大利亚，英国，德国，西班牙站点)
     * @author Gordon
     * @since 2013-06-24
     */
    public static function getMoreZtSite($siteid){
    	$allowMoreZt = array(0, 2, 15, 3, 77, 71, 186);//允许发多张主图的站点id
    	if(in_array($siteid, $allowMoreZt)){
    		return true;
    	}else{
    		return false;
    	}
    }
    
    /**
     * @desc 允许附图多张
     * @param unknown $siteid
     * @return boolean
     */
    public static function getMoreFtSite($siteid){
    	$allowMoreZt = array(0, 2, 15, 3, 77, 71, 186);//允许发多张副图的站点id
    	if(in_array($siteid, $allowMoreZt)){
    		return true;
    	}else{
    		return false;
    	}
    }
    /**
     * @desc 获取币种列表
     * @return multitype:string
     */
    public static function getCurrencyList(){
    	return array(
    			'USD'	=> 'USD',
    			'GBP'	=> 'GBP',
    			'AUD'	=> 'AUD',
    			'CAD'	=> 'CAD',
    			'EUR'	=> 'EUR',
    	);
    }

    /**
     * @desc 根据所属国家(territory)获取站点列表
     * @return multitype:string
     */
    public static function getTerritorySiteList(){
        $item = array();
        $list = self::getAbleSiteList();
        if ($list){
            foreach ($list as $val){
                $item[$val['site_id']] = $val['territory'];
            }
        }
        return $item;
    }
    
    /**
     * @desc 获取站点名称和站点简称对
     * @return multitype:Ambigous <>
     */
    public static function getTerritoryNameList(){
    	$item = array();
    	$list = self::getAbleSiteList();
    	if ($list){
    		foreach ($list as $val){
    			$item[$val['site_name']] = $val['territory'];
    		}
    	}
    	return $item;
    }


    /**
     * @desc 获取status=1的站点ID
     * @return array
     */
    public static function getSiteIdArr(){
        $item = array();
        $list = self::getSiteList();
        if ($list){
            $item = array_keys($list);
        }
        return $item;
    }


    /**
     * @desc 获取站点名称
     * @param unknown $siteIds
     * @return Ambigous <string, unknown, mixed>
     */
    public function getSiteNameByIds($siteIds) {
        $siteInfo = self::model()->getDbConnection()
                ->createCommand()
                ->select("site_id, site_name")
                ->from(self::tableName())
                ->where(array('IN', 'site_id', $siteIds))
                ->queryAll();
        if(!$siteInfo){
            return array();
        }
        $return = array();
        foreach ($siteInfo as $site){
            $return[$site['site_id']] = $site['site_name'];
        }
        return $return;
    }
}