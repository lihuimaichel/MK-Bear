<?php
/**
 * @desc 平台账号相关逻辑
 * @author hanxy
 */
class PlatformAccountModel extends UebModel {
    public function getDbKey() {
        return 'db_platform_account';
    }


    /**
     * @desc 获取状态列表
     * @param integer $status
     * @return multitype:
     */
    public static function getStatus($status = null){
        if ($status == 0) {
            echo '<font color="red">失效</font>';
        } else {
            echo '<font color="#33CC00">有效</font>';
        }
    }


    /**
     * @desc 获取同步oms状态列表
     * @param integer $status
     * @return multitype:
     */
    public static function getOmsStatus($status = null){
        if ($status == 0) {
            echo '<font color="red">未同步</font>';
        } else {
            echo '<font color="#33CC00">已同步</font>';
        }
    }


    /**
     * 获取所属部门
     */
    public static function getDeparment($dep_id = null, $deparmentcode = null){
        $deparmentOptions = Department::model()->getDepartmentByKeywords($deparmentcode);
        if($dep_id !== null){
            return isset($deparmentOptions[$dep_id])?$deparmentOptions[$dep_id]:'';
        }
        return $deparmentOptions;
    }


    /**
     * 是否生效状态
     */
    public static function getAccountStatus($status = null){
        $statusOptions = array(0=>'失效', 1=>'有效');
        if($status !== null){
            return isset($statusOptions[$status])?$statusOptions[$status]:'';
        }
        return $statusOptions;
    }
}