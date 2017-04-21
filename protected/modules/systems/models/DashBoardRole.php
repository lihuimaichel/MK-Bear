<?php
/**
 * @desc 控制台角色配置 model
 * @author guoll
 * 2015-9-14
 */
class DashBoardRole extends SystemsModel
{
    public $new_password = null;

    public static $resources = array();
    public static $exists=array();

    /**
     * Returns the static model of the specified AR class.
     * @return CActiveRecord the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }
    public function getDbKey(){
    	return 'db';
    }
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'ueb_dashboard_role';
    }


    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
//            'dash' => array(self::BELONGS_TO, 'DashBoard', array('dashboard_id'=>'id')),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
        );
    }


    public function filterOptions() {
        return array(
            array(
            ),
        );
    }

    /**
     * check if it is a super user
     *
     * @return bool
     */
    public static function isAdmin() {
        return Yii::app()->user->name == 'admin';
    }

    /**
     * get all resources by role id
     */
    public static function getDashboardByRoleId($roleId) {
        if(!is_array($roleId)){
            $rows=array();
            if ( empty($roleId) ) {
                return null;
            }
            $rows = self::model()->findAll('itemname=:itemname',array(':itemname'=>$roleId));
            foreach ($rows as $val){
                if(!in_array($val['dashboard_id'], self::$exists)){;
                    array_push(self::$resources, $val);
                    array_push(self::$exists, $val['dashboard_id']);
                }
            }
        }else{
            foreach($roleId as $rid){
                self::getDashboardByRoleId($rid);
            }
        }
        return self::$resources;
    }

    /**
     * get admin dashboard
     * @return array|null
     */
    public static function getDashboardByAdmin() {
        $rows=array();
        $rows = DashBoard::model()->findAll('status=:status',array(':status'=>DashBoard::DASHBOARD_STATUS_YES));
        foreach ($rows as $val){
            $val['dashboard_id'] = $val['id'];
            if(!in_array($val['dashboard_id'], self::$exists)){;
                array_push(self::$exists, $val['dashboard_id']);
                array_push(self::$resources, $val);
            }
        }
        return self::$resources;
    }

    /**
     * add role dashboard
     *
     * @param type $roleId
     * @param type $resources
     * @return boolean
     */
    public function addRoleDashboard($roleId, $resources) {
        if (empty($resources)) {
            return true;
        }
        foreach ($resources as $val) {
            if ( $val == 'treeDashboard_0') { continue;}
            $id = substr($val, 10);
            Yii::app()->db->createCommand()
                ->insert(self::tableName(), array(
                    'itemname'=>$roleId,
                    'dashboard_id'=>$id,
                ));
        }
        return true;
    }

    /**
     * del role dashboard
     *
     * @param type $roleId
     * @param type $resources
     * @return boolean
     */
    public function delRoleDashboard($roleId) {
        if (empty($roleId)) {
            return false;
        }
        Yii::app()->db->createCommand()->delete(self::tableName(), 'itemname=:itemname',
            array( ':itemname' => $roleId));
        return true;
    }

    /**
     * save user config from dashboard page request
     */
    public function saveUserDashboardConfig($userConfig) {
//        echo '<pre>a';print_r($userConfig);
        $dashboard_id = $userConfig['dashboard_id'];
        $todo = isset($userConfig['todo']) ? $userConfig['todo'] : '';
        unset($userConfig['dashboard_id'], $userConfig['todo']);

        $oldConfig = self::getUserDashboardConfig();
        $newConfig = $oldConfig;
        if ($todo){
            if ($todo=='hidd'){
                $newConfig['areaConfig'][$todo][] = $dashboard_id; //add a dashboard to 'show' or 'hidd'
            }
            if ($todo=='show'){
                foreach ($newConfig['areaConfig']['hidd'] as $k=>$v){
                    if($v==$dashboard_id){
                        unset($newConfig['areaConfig']['hidd'][$k]);
                    }
                }
            }
            $newConfig['areaConfig']['hidd'] = array_unique($newConfig['areaConfig']['hidd']);    //remove repeat ids
        }
        if (isset($userConfig['sort'])){
            $ids = $userConfig['sort']['ids'];
            $pos = $userConfig['sort']['pos'];
            unset($userConfig['sort']);

            foreach($ids as $k=>$v){
                $newPos['top'] = ceil($pos[$k]['top']);
                $newPos['left'] = ceil($pos[$k]['left']);

                $userConfig['sort'][$v] = $newPos;
            }
            $newConfig['areaConfig']['sort'] = $userConfig['sort']; //set a dashboard's sort config
            unset($userConfig['sort']);
        }
        !empty($userConfig) ? $newConfig['toolBarConfig'][$dashboard_id] = $userConfig : '';    //set a dashboard's config

//echo '<pre>a';print_r($newConfig);

        $data['UserConfig']['dashboard_config'] = serialize($newConfig);

        $model = new UserConfig();
        $model->attributes = $data['UserConfig'];
        $flag = $model->batchSave($data['UserConfig']);
        if ( $flag ) {
            $jsonData = array(
                'message' => Yii::t('system', 'Save successful'),
            );
            echo $this->successJson($jsonData);
        } else {
            echo $this->failureJson(array(
                'message' => Yii::t('system', 'Save failure')));
        }
        Yii::app()->end();
    }


    /**
     * get user dashboard config by uid and dashboard_id
     */
    public  static function getUserDashboardConfig(){
        try{
            $userAllConfig = UserConfig::getPairByUserId(Yii::app()->user->id);
            $userThisConfig = isset($userAllConfig['dashboard_config'])        ?
                unserialize($userAllConfig['dashboard_config'])   :
                array();
        } catch (Exception $e) {
        }
        return $userThisConfig;
    }
    /**
     * 角色添加的次数
     */
    public function checkUserRecord(){
    	$user=Yii::app()->user->user_name;
    	$data=$this->getDbConnection()->createCommand()
    	->select('*')
    	->from($this->tableName())
    	->where("itemname = '{$user}'")
    	->queryAll();
    	return count($data);
    }
    /**
     * insert
     */
    public function insertInfo($id){
    	$user=Yii::app()->user->user_name;
    	$model = new self();
    	$model->setAttribute('itemname', $user);
    	$model->setAttribute('dashboard_id', $id);
    	if($model->save()){
    		return true;
    	}else{
    		return false;
    	}
    }
    
}