<?php

class AliexpressSite
{
    /**
     * @desc 获取站点列表
     * @return array
     */
    public static function getSiteAllList()
    {
        return array(
            0 => array('CountryCode' => 'ali', 'CountryName' => 'Ali')
        );
    }

    /**
     * @desc 获取站点键值对
     * @return array
     */
    public static function getSiteList()
    {
        $simplelist = array();
        $siteList = self::getSiteAllList();
        foreach ($siteList as $key => $val) {
            $simplelist[$key] = $val['CountryCode'];
        }
        return $simplelist;
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

}