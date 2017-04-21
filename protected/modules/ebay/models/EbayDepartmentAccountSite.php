<?php
/**
 * @desc 部门与账号和站点关系
 * @author hanxy
 * @since 2016-10-25
 */
class EbayDepartmentAccountSite extends EbayModel{

    /** @var tinyint ebay所属部门ID*/
    const DEPARTMENT_SHENZHEN = 3,
          DEPARTMENT_CHANGSHA = 23,
          DEPARTMENT_OVERSEAS = 19;
          
        
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
        
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_ebay_department_account_site';
    }
    
    
    /**
     * [getListByCondition description]
     * @param  string $where  [description]
     * @param  mixed $order  [description]
     * @return array
     */
    public function getListByCondition($where='1',$order='') {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select('*')
            ->from(self::tableName())
            ->where($where);
        $order != '' && $cmd->order($order);
        return $cmd->queryAll();
    }

    // ========================== 搜索START ==============================

    public function search(){
        $csort = new CSort();
        $csort->attributes = array('defaultOrder'=>'id');
        $dataPrivider = parent::search($this, $csort, '', $this->setCDbCriteria());
        $data = $this->additions($dataPrivider->getData());
        $dataPrivider->setData($data);
        return $dataPrivider;
    }

    public function setCDbCriteria(){
        $CDbCriteria = new CDbCriteria();
        $CDbCriteria->select = "t.*";
        return $CDbCriteria;
    }
    public function additions($datas){

        if($datas){
            $accountLists = EbayAccount::model()->getIdNamePairs();
            $departmentLists = EbayAccount::model()->getDepartment();
            foreach ($datas as &$data){
                $data->department_id = isset($departmentLists[$data['department_id']])?$departmentLists[$data['department_id']]:'-';
                $data->account_id = isset($accountLists[$data['account_id']])?$accountLists[$data['account_id']]:'-';
                $data->site_id = EbaySite::getSiteName($data['site_id']);
            }

        }

        return $datas;
    }


    public function filterOptions(){
        $siteID = Yii::app()->request->getParam("site_id");
        return array(
            array(
                'name' => 'department_id',
                'type' => 'dropDownList',
                'data' => EbayAccount::model()->getDepartment(),
                'search' => '=',
            ),
            array(
                'name' => 'account_id',
                'type' => 'dropDownList',
                'value'=> Yii::app()->request->getParam('account_id'),
                'data' => EbayAccount::model()->getIdNamePairs(),
                'search' => '=',
            ),
            array(
                'name' => 'site_id',
                'type' => 'dropDownList',
                'value'=> Yii::app()->request->getParam('site_id'),
                'data' => EbaySite::getSiteList(),
                'value'=> $siteID,
                'search' => '=',
            ),
        );
    }

    public function attributeLabels(){
        return array(
            'department_id'			=>		'部门',
            'account_id'			=>		'帐号',
            'site_id'		        =>		'站点',

        );
    }

    // ========================== 搜索END ===========================
}