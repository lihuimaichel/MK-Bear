<?php
/**
 * @desc 设置用户(主管、组长、员工)控制器
 * @author hanxy
 */
class PositionController extends UebController {

    CONST USER_STATUS_ON        = 1,        // 用户启用状态值
          USER_STATUS_OFF       = 0,        // 用户关闭状态值
          USER_POST_PERSONNEL   = 0,        // 用户岗位为员工
          USER_POST_LEADER      = 1,        // 用户岗位为组长
          USER_POST_DIRECTOR    = 2;        // 用户岗位为主管

    /** @var object 模型实例 **/
    protected $_model = NULL;
    
    /**
     * (non-PHPdoc)
     * @see CController::init()
     */
    public function init() {
        $this->_model = new UserPosition();
    }


    /**
     * @desc 设置主管
     */
    public function actionBatchsetdirector(){
        //判断用户是否是超级管理员
        $isAdmin = UserSuperSetting::model()->checkSuperPrivilegeByUserId(Yii::app()->user->id);
        if(!$isAdmin){
            echo $this->failureJson(array('message'=>'不是超级管理员，不能进行此操作'));
            exit;
        }

        $id = Yii::app()->request->getParam('id');
        if(empty($id)){
            echo $this->failureJson(array('message'=>'没有选择用户'));
            exit;
        }

        $departmentId = Yii::app()->request->getParam('departmentId');
        if(empty($departmentId)){
            echo $this->failureJson(array('message'=>'没有选择部门'));
            exit;
        }

        // 判断用户是否启用
        $existsUser = User::model()->getUserNameById($id);
        if($existsUser && $existsUser['user_status'] == self::USER_STATUS_OFF){
            echo $this->failureJson(array('message'=>'此用户未启用'));
            exit;
        }

        // 判断用户ID是否在表里已经存在
        $userInfo = $this->_model->getUserPositionInfoByUserID($id);
        if($userInfo && $userInfo['post_id'] == 2){
            echo $this->failureJson(array('message'=>'此用户已经是主管，不能重复设置'));
            exit;
        }

        // 通过岗位ID查询是否已经存在主管
        $DepartmentAllUser = User::model()->getUserNameAllEmpByDeptID($departmentId);
        $userIds = array_keys($DepartmentAllUser);
        
        // 判断设置的用户部门是否已经有主管
        $result = $this->_model->getUserPositionDirectorByUserIDs($userIds);
        if($result){
            echo $this->failureJson(array('message'=>'此部门已设置了主管，不能设置多个主管'));
            exit;
        }   

        try {

            $dbTransaction = UserPosition::model()->getDbConnection()->beginTransaction();     

            $times = date('Y-m-d H:i:s');

            //设置部门用户为员工
            $insertPerData = '';
            $directorIdArray = array($id);
            $personnelDiff = array_diff($userIds,$directorIdArray);

            //取出岗位人员
            $postUserList = $this->_model->getUserPositionInfoByUserIDs($personnelDiff);
            if($postUserList){
                foreach ($postUserList as $r => $t) {
                    $postUserArray[] = $t['user_id'];
                }
                $personnelData = array_diff($personnelDiff, $postUserArray);
                if($personnelData){
                    foreach ($personnelData as $q => $w) {
                        $insertPerData .= ' ('.$w.','.self::USER_POST_PERSONNEL.',0,"'.$times.'","'.$times.'"),';
                    }
                    $insertPerSql = 'INSERT INTO '.$this->_model->tableName().' (user_id,post_id,parent_user_id,create_time,update_time) 
                                    VALUES'.rtrim($insertPerData,',').'ON DUPLICATE KEY UPDATE 
                                    post_id=VALUES(post_id), 
                                    parent_user_id=VALUES(parent_user_id), 
                                    update_time=VALUES(update_time)';
                    $personResult = $this->_model->ReplaceIntoSqlData($insertPerSql);
                    if(!$personResult){
                        echo $this->failureJson(array('message'=>'设置失败'));
                        $dbTransaction->rollback();
                        exit;
                    }
                }
            }else{
                foreach ($personnelDiff as $q => $w) {
                    $insertPerData .= ' ('.$w.','.self::USER_POST_PERSONNEL.',0,"'.$times.'","'.$times.'"),';
                }
                $insertPerSql = 'INSERT INTO '.$this->_model->tableName().' (user_id,post_id,parent_user_id,create_time,update_time) 
                                VALUES'.rtrim($insertPerData,',').'ON DUPLICATE KEY UPDATE 
                                post_id=VALUES(post_id), 
                                parent_user_id=VALUES(parent_user_id), 
                                update_time=VALUES(update_time)';
                $personResult = $this->_model->ReplaceIntoSqlData($insertPerSql);
                if(!$personResult){
                    echo $this->failureJson(array('message'=>'设置失败'));
                    $dbTransaction->rollback();
                    exit;
                }
            }

            $insertData = '('.$id.','.self::USER_POST_DIRECTOR.',0,"'.$times.'","'.$times.'")';

            $insertSql = 'INSERT INTO '.$this->_model->tableName().' (user_id,post_id,parent_user_id,create_time,update_time) 
                            VALUES'.$insertData.'ON DUPLICATE KEY UPDATE 
                            post_id=VALUES(post_id), 
                            parent_user_id=VALUES(parent_user_id), 
                            update_time=VALUES(update_time)';

            $result = $this->_model->ReplaceIntoSqlData($insertSql);
            if(!$result){
                echo $this->failureJson(array('message'=>'设置主管失败'));
                $dbTransaction->rollback();
                exit;
            }

            $dbTransaction->commit();
            echo $this->successJson(array('message'=>'设置主管成功'));
            unset($userIds);

        } catch (Exception $e) {
            $dbTransaction->rollback();
            echo $this->failureJson(array('message'=>$e->getMessage()));
        }
    }


    /**
     * @desc 设置组长
     */
    public function actionBatchsetleader(){
        //判断用户是否是超级管理员
        $isAdmin = UserSuperSetting::model()->checkSuperPrivilegeByUserId(Yii::app()->user->id);
        if(!$isAdmin){
            echo $this->failureJson(array('message'=>'不是超级管理员，不能进行此操作'));
            exit;
        }

        $ids = rtrim(Yii::app()->request->getParam('ids'),',');
        $departmentId = Yii::app()->request->getParam('departmentId');
        $this->existsUserParam($ids);

        // 通过部门ID查询出此部门的所有用户
        if(!$departmentId){
            echo $this->failureJson(array('message'=>'部门没有选择'));
            exit;
        }

        $DepartmentAllUser = User::model()->getUserNameAllEmpByDeptID($departmentId);
        $userIds = array_keys($DepartmentAllUser);
        $idsArray = explode(',', $ids);

        // 判断用户ID是否在表里已经存在
        $userInfo = $this->_model->getUserPositionInfoByUserIDs($userIds);
        if(!$userInfo){
            echo $this->failureJson(array('message'=>'请先设置主管用户，在设置组长'));
            exit;
        }

        $directorID = '';
        foreach ($userInfo as $userKey => $userValue) {
            if($userValue['post_id'] == self::USER_POST_DIRECTOR){    
                $directorID = $userValue['user_id'];               
                // 判断用户是否已经是主管用户
                if(in_array($userValue['user_id'],$idsArray)){
                    $failure = User::model()->getUserNameScalarById($userValue['user_id']).'用户已经是主管，不能重复设置';
                    echo $this->failureJson(array('message'=>$failure));
                    exit;
                }
            }elseif ($userValue['post_id'] == self::USER_POST_LEADER) {
                // 判断用户是否已经是组长 
                if(in_array($userValue['user_id'],$idsArray)){
                    $failure = User::model()->getUserNameScalarById($userValue['user_id']).'用户已经是组长，不能重复设置';
                    echo $this->failureJson(array('message'=>$failure));
                    exit;
                }
            }
        }

        // 插入数据
        $times = date('Y-m-d H:i:s');
        $idsArray = explode(',', $ids);
        $insertData = '';
        foreach ($idsArray as $k => $v) {
            $datas = '';
            $datas = '('.$v.','.self::USER_POST_LEADER.','.$directorID.',"'.$times.'","'.$times.'"),';
            $insertData .= $datas;
        }

        if(empty($directorID)){
            echo $this->failureJson(array('message'=>'主管用户为空，设置失败'));
            exit;
        }
        $insertSql = 'INSERT INTO '.$this->_model->tableName().' (user_id,post_id,parent_user_id,create_time,update_time) 
                    VALUES'.rtrim($insertData,',').'ON DUPLICATE KEY UPDATE 
                    post_id=VALUES(post_id), 
                    parent_user_id=VALUES(parent_user_id), 
                    update_time=VALUES(update_time)';

        $result = $this->_model->ReplaceIntoSqlData($insertSql);
        if(!$result){
            echo $this->failureJson(array('message'=>'设置失败'));
            exit;
        }

        echo $this->successJson(array('message'=>'设置组长成功'));     

    }


    /**
     * @desc 设置员工
     */
    public function actionBatchsetpersonnel(){
        //判断用户是否是超级管理员
        $isAdmin = UserSuperSetting::model()->checkSuperPrivilegeByUserId(Yii::app()->user->id);
        if(!$isAdmin){
            echo $this->failureJson(array('message'=>'不是超级管理员，不能进行此操作'));
            exit;
        }

        $ids = rtrim(Yii::app()->request->getParam('ids'),',');
        $departmentId = Yii::app()->request->getParam('departmentId');
        $this->existsUserParam($ids);

        // 通过部门ID查询出此部门的所有用户
        if(!$departmentId){
            echo $this->failureJson(array('message'=>'部门没有选择'));
            exit;
        }

        // 通过弹出框点击传入的参数 0 代表没有选择已经是组长或者主管的用户id  1 代表选择了已经是组长或者主管的用户id
        $ishave = Yii::app()->request->getParam('ishave');
        $ishave = empty($ishave)?0:1;

        $DepartmentAllUser = User::model()->getUserNameAllEmpByDeptID($departmentId);
        $userIds = array_keys($DepartmentAllUser);
        $idsArray = explode(',', $ids);

        // 判断用户ID是否在表里已经存在
        $userInfo = $this->_model->getUserPositionInfoByUserIDs($userIds);
        
        // 按部门取出岗位表里的数据
        $userPostIdArray = array();
        foreach ($userInfo as $key => $value) {
            $userPostIdArray[] = $value['user_id'];
        }
        
        // 选中的用户ID和比较岗位表里的用户ID，看是否有重复
        $doubleUserName = '';
        $doubleDirectorIDs = array();
        $doubleLeaderIDs = array();
        $newIdsArray = array_intersect($idsArray,$userPostIdArray);
        if(count($newIdsArray) > 0 && $ishave == 0){
            $userList = User::model()->getUserListByIDs($newIdsArray);
            foreach ($userList as $k => $v) {
                $doubleUserName[] = $v['user_name'];
            }
            $message = implode(',', $doubleUserName).' 用户已经是组长或者主管，是否设置这些用户为员工';
            echo $this->getStatusCodeJson(array('message'=>$message, 'statusCode'=>600));
            exit;
        }

        // 插入数据
        $times = date('Y-m-d H:i:s');
        $insertData = '';
        foreach ($idsArray as $k => $v) {
            $datas = '';
            $datas = '('.$v.','.self::USER_POST_PERSONNEL.',0,"'.$times.'","'.$times.'"),';
            $insertData .= $datas;
        }

        try {

            $dbTransaction = UserPosition::model()->getDbConnection()->beginTransaction();

            //取出组长数组
            $leaderArray = $this->_model->getUserPositionLeaderByUserIDs($idsArray);

            //去掉组长下属的员工
            $doubleUserIds = array();
            if($leaderArray){
                foreach ($leaderArray as $dkey => $dVal) {
                    $doubleUserIds[] = $dVal['user_id'];
                }

                if(count($doubleUserIds) > 0){
                    $explodeNewIds = implode(',', $doubleUserIds);
                    $newSql = 'UPDATE '.$this->_model->tableName().' SET parent_user_id = 0 WHERE post_id = 0 AND parent_user_id IN('.$explodeNewIds.')';
                    $this->_model->ReplaceIntoSqlData($newSql);
                }
            }

            $sql = 'INSERT INTO '.$this->_model->tableName().' (user_id,post_id,parent_user_id,create_time,update_time) 
                        VALUES'.rtrim($insertData,',').'ON DUPLICATE KEY UPDATE 
                        post_id=VALUES(post_id), 
                        parent_user_id=VALUES(parent_user_id), 
                        update_time=VALUES(update_time)';
            $this->_model->ReplaceIntoSqlData($sql);

            $dbTransaction->commit();
            echo $this->successJson(array('message'=>'设置员工成功'));

        } catch (Exception $e) {
            $dbTransaction->rollback();
            echo $this->failureJson(array('message'=>$e->getMessage()));
        }

        unset($DepartmentAllUser);
    }


    /**
     * @desc 组长设置下属员工
     */
    public function actionLeadersetpersonnel(){
        //判断用户是否是超级管理员
        $isAdmin = UserSuperSetting::model()->checkSuperPrivilegeByUserId(Yii::app()->user->id);
        if(!$isAdmin){
            echo $this->failureJson(array('message'=>'不是超级管理员，不能进行此操作'));
            exit;
        }

        $departmentId = Yii::app()->request->getParam('departmentId');
        // 通过部门ID查询出此部门的所有用户
        if(!$departmentId){
            throw new Exception("部门没有选择");
        }

        $userId = Yii::app()->request->getParam('userId');
        if(!$userId){
            throw new Exception("组长ID为空");
        }

        $leaderID = $userId;
        $this->existsUserParamThrow($leaderID);

        $DepartmentAllUser = User::model()->getUserNameAllEmpByDeptID($departmentId);
        $userIds = array_keys($DepartmentAllUser);

        if($_POST){
            $personnelArray = Yii::app()->request->getParam('personnelIDs');
            if(empty($personnelArray)){
                throw new Exception("请选择要设置的员工");
            }
            $this->existsUserParamThrow($personnelArray);

            // 判断主管和组长是否在表里已经存在
            $userInfo = $this->_model->getUserPositionDirectorAndLeaderByUserIDs($userIds);
            if($userInfo){
                // 按部门取出岗位表里的数据
                $userPostIdArray = array();
                foreach ($userInfo as $key => $value) {
                    $userPostIdArray[] = $value['user_id'];
                }
                
                // 选中的用户ID和比较岗位表里的用户ID，看是否有重复
                $doubleUserName = '';
                $newIdsArray = array_intersect($personnelArray,$userPostIdArray);
                if(count($newIdsArray) > 0){
                    $userList = User::model()->getUserListByIDs($newIdsArray);
                    foreach ($userList as $k => $v) {
                        $doubleUserName[] = $v['user_name'];
                    }
                    $message = implode(',', $doubleUserName).' 用户已经是组长或者主管，不能重复设置';
                    throw new Exception($message);
                }

                // 插入数据
                $times = date('Y-m-d H:i:s');
                $insertData = '';
                foreach ($personnelArray as $k => $v) {
                    $datas = '';
                    $datas = '('.$v.','.self::USER_POST_PERSONNEL.','.$leaderID.',"'.$times.'","'.$times.'"),';
                    $insertData .= $datas;
                }

                if(empty($leaderID)){
                    throw new Exception('组长用户为空，设置失败');
                }
                $sql = 'INSERT INTO '.$this->_model->tableName().' (user_id,post_id,parent_user_id,create_time,update_time) 
                        VALUES'.rtrim($insertData,',').'ON DUPLICATE KEY UPDATE 
                        post_id=VALUES(post_id), 
                        parent_user_id=VALUES(parent_user_id), 
                        update_time=VALUES(update_time)';
                $result = $this->_model->ReplaceIntoSqlData($sql);
                if(!$result){
                    throw new Exception('设置失败');
                }

                $jsonData = array(
                    'message' => '更改成功',
                    'forward' =>'/users/users/list',
                    'navTabId'=> 'page' .UserPosition::getIndexNavTabId(),
                    'callbackType'=>'closeCurrent'
                );
                echo $this->successJson($jsonData);
                // echo $this->successJson(array('message'=>'设置组长成功'));
                
            }else{
                throw new Exception('请先设置主管用户，在设置组长');
            } 
        }else{

            // 取出组长
            $leaderList = array();
            $leaderInfo = $this->_model->getUserPositionInfoByPostID(self::USER_POST_LEADER);
            if($leaderInfo){
                $leaderIds = array();
                foreach ($leaderInfo as $key => $users) {
                    $leaderIdArray[] = $users['user_id'];
                }

                $leaderIds = array_intersect($leaderIdArray,$userIds);
                $leaderList = User::model()->getSpecificPairs($leaderIds);
            }

            // 取出员工
            $personnelList = array();
            $selectPersonnelList = array();
            $selectUserIds = array();
            $personnelInfo = $this->_model->getUserPositionInfoByPostID(self::USER_POST_PERSONNEL);
            if($personnelInfo){
                $personnelIds = array();
                foreach ($personnelInfo as $key => $person) {
                    $personnelIdArray[] = $person['user_id'];
                    if($person['parent_user_id'] == $leaderID && $person['post_id'] == 0){
                        $selectUserIds[] = $person['user_id'];
                    }
                }

                //取出该组的组员
                if(count($selectUserIds) > 0){
                    $selectUserList = User::model()->getUserListByIDs($selectUserIds);
                    foreach ($selectUserList as $x => $c) {
                        $selectPersonnelList[$c['id']] = $c['user_name'];
                    }
                }

                // 取出该部门下的所有员工用户
                $personnelAllIds = array_intersect($personnelIdArray,$userIds);

                // 取出该部门下所有未分配的用户
                $personnelZero = $this->_model->getUserPositionPersonnelByUserIDs($personnelAllIds);
                if($personnelZero){
                    foreach ($personnelZero as $n => $m) {
                        $personnelIds[] = $m['user_id'];
                    }
                }
                $personnelList = User::model()->getStatusOneByUids($personnelIds);
            }

            $this->render("leadersetpersonnel", array('model'=>$this->_model, 'leaderList'=>$leaderList, 'personnelList'=>$personnelList, 'departmentId'=>$departmentId, 'selectPersonnelList'=>$selectPersonnelList, 'userId'=>$userId));
        }
    }


    /**
     * 判断设置组长和设置员工有没有选择用户和判断用户是否启用的公共方法
     * @return json
     */
    private function existsUserParam($ids){
        if(empty($ids)){
            echo $this->failureJson(array('message'=>'没有选择用户'));
            exit;
        }

        // 判断用户是否启用
        $existsUser = User::model()->getUserListByIDs($ids);
        if($existsUser){
            $userName = '';
            foreach ($existsUser as $key => $value) {
                if($value['user_status'] == self::USER_STATUS_OFF){
                    $userName .= $value['user_name'] . ',';
                }
            }

            if(!empty($userName)){
                $userName = rtrim($userName,',').'用户未启用';
                echo $this->failureJson(array('message'=>$userName));
                exit;
            }
        }
    }


    /**
     * 判断设置组长和设置员工有没有选择用户和判断用户是否启用的公共方法
     */
    private function existsUserParamThrow($ids){
        if(empty($ids)){
            throw new Exception("没有选择用户");
        }

        // 判断用户是否启用
        $existsUser = User::model()->getUserListByIDs($ids);
        if($existsUser){
            $userName = '';
            foreach ($existsUser as $key => $value) {
                if($value['user_status'] == self::USER_STATUS_OFF){
                    $userName .= $value['user_name'] . ',';
                }
            }

            if(!empty($userName)){
                $userName = rtrim($userName,',').'用户未启用';
                throw new Exception($userName);
            }
        }
    }

}
