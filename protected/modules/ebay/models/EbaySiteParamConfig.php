<?php

class EbaySiteParamConfig extends EbayModel {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'ueb_ebay_site_param_config';
    }

    /**
     * @desc 获取站点配置名称
     * @return string
     */
    public function getList(){
        return $this->getDbConnection()->createCommand()
            ->from($this->tableName())
            ->select("*")
            ->queryAll();
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

        return $datas;
    }


    public function filterOptions(){
        return array();
    }

    public function attributeLabels(){
        return array(
            'config_name'			=>		'站点配置名称',
            'create_time'			=>		'创建时间',
            'create_user_id'		=>		'创建人',
            'update_time'	        =>		'修改时间',
            'update_user_id'	    =>		'修改人',

        );
    }

    public function rules(){
        return array(
            array('config_name', 'safe'),
            array('config_name', 'required')
        );
    }
    // ========================== 搜索END ===========================
}