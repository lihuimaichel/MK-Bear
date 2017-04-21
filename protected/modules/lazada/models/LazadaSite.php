<?php

/**
 * @desc Lazada站点
 * @author Gordon
 * @since 2015-08-13
 */
class LazadaSite extends LazadaModel
{

    const SITE_MY = 1;    //马来西亚站点
    const SITE_SG = 2;    //新加坡
    const SITE_ID = 3;  //印尼站点
    const SITE_TH = 4;    //泰国站点
    const SITE_PH = 5;    //菲律宾站点
    const SITE_VN = 6;  //越南站点

    public static $siteList = array(
        1 => '马来西亚站点',
        2 => '新加坡',
        3 => '印尼站点',
        4 => '泰国站点',
        5 => '菲律宾站点',
        6 => '越南站点'
    );

    public static $siteShortNameList = array(
        1 => 'my',
        2 => 'sg',
        3 => 'id',
        4 => 'th',
        5 => 'ph',
        6 => 'vn'
    );

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
    }


    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName()
    {
        return 'ueb_lazada_account';
    }

    /**
     * @desc 根据站点获取货币
     * @param int $siteID
     */
    public function getCurrencyBySite($site)
    {
        $config = self::getSiteCurrencyList();
        return isset($config[$site]) ? $config[$site] : '';
    }

    /**
     * @desc 获取站点货币
     * @return multitype:string
     */
    public static function getSiteCurrencyList($key = null)
    {
        $currencyList = array(
            self::SITE_MY => 'MYR',
            self::SITE_SG => 'SGD',
            self::SITE_PH => 'PHP',
            self::SITE_TH => 'THB',
            self::SITE_ID => 'IDR',
            self::SITE_VN => 'VND',
        );
        if (!is_null($key) && array_key_exists($key, $currencyList))
            return $currencyList[$key];
        return $currencyList;
    }

    /**
     * @desc 获取站点简称
     * @param string $key
     */
    public static function getSiteShortName($id = null)
    {
        $list = array(
            self::SITE_MY => 'my',
            self::SITE_SG => 'sg',
            self::SITE_PH => 'ph',
            self::SITE_TH => 'th',
            self::SITE_ID => 'id',
            self::SITE_VN => 'vn',
        );
        if ($id && isset($list[$id])) {
            return $list[$id];
        } else {
            return $list;
        }
    }

    /**
     * 获取lazada平台站点ID与站点简称列表
     * @return array
     */
    public function getSiteShortNameList()
    {
        return array('errorCode' => '0', 'errorMsg' => 'ok', 'data' => self::getSiteShortName());
    }

    /**
     * @desc 获取站点列表
     */
    public static function getSiteList($key = null)
    {
        $siteList = array(
            self::SITE_MY => Yii::t('lazada', 'MY Site'),
            self::SITE_SG => Yii::t('lazada', 'SG Site'),
            self::SITE_ID => Yii::t('lazada', 'ID Site'),
            self::SITE_TH => Yii::t('lazada', 'TH Site'),
            self::SITE_PH => Yii::t('lazada', 'PH Site'),
            self::SITE_VN => Yii::t('lazada', 'VN Site'),
        );
        if (!is_null($key) && array_key_exists($key, $siteList))
            return $siteList[$key];
        return $siteList;
    }

    /**
     * @desc 获取站点id列表
     * @return array
     */
    public static function getSiteIDs()
    {
        $siteList = self::getSiteList();
        $siteIDs = array_flip($siteList);
        return $siteIDs;
    }


    /**
     * @desc 根据站点名称获取站点ID
     * @param unknown $sitename
     * @return Ambigous <>|number
     */
    public static function getSiteIdByName($sitename)
    {
        $siteIDs = self::getSiteIDs();
        if (isset($siteIDs[$sitename])) {
            return $siteIDs[$sitename];
        } else {
            return 0;
        }
    }

    /**
     * @desc 获取站点名称
     * @param unknown $siteid
     * @return Ambigous <string>
     */
    public static function getSiteName($siteid)
    {
        $siteList = self::getSiteList();
        return $siteList[$siteid];
    }

    /**
     * @param null $siteID
     * @return array|mixed|string
     */
    public static function getLanguageBySite($siteID = null)
    {
        $list = array(
            self::SITE_MY => 'english',
        );
        if ($siteID === null) {
            return $list;
        } else {
            return isset($list[$siteID]) ? $list[$siteID] : 'english';
        }
    }


    /**
     * @desc 通过站点ID获取国家名称
     */
    public static function getCountryName($key = null)
    {
        $siteList = array(
            self::SITE_MY => Yii::t('lazada', 'MY NAME'),
            self::SITE_SG => Yii::t('lazada', 'SG NAME'),
            self::SITE_ID => Yii::t('lazada', 'ID NAME'),
            self::SITE_TH => Yii::t('lazada', 'TH NAME'),
            self::SITE_PH => Yii::t('lazada', 'PH NAME'),
            self::SITE_VN => Yii::t('lazada', 'VN NAME'),
        );
        if (!is_null($key) && array_key_exists($key, $siteList)) {
            return $siteList[$key];
        } else {
            return null;
        }
    }


    /**
     * @param null $id
     * @return mixed
     * 获取站点简称
     */
    public static function getLazadaSiteShortName($id = null)
    {
        $list = array(
            self::SITE_MY => 'my',
            self::SITE_SG => 'sg',
            self::SITE_PH => 'ph',
            self::SITE_TH => 'th',
            self::SITE_ID => 'id',
            self::SITE_VN => 'vn',
        );
        if ($id && isset($list[$id])) {
            return $list[$id];
        } else {
            return $list[1];
        }
    }
}