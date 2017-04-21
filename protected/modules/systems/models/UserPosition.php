<?php
/**
 * @desc 设置用户(主管、组长、员工)
 * @author hanxy
 * @since 2016-08-25
 */
class UserPosition extends SystemsModel{

    public $personnelIDs;

    public $name;

    public static $positionArray = array('0'=>'员工', '1'=>'组长', '2'=>'主管');
	
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function getDbKey(){
        return 'db';
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_user_position';
    }


    /**
     * @desc 通过用户ID查询表信息
     * @param integer $userID 用户ID
     * @param string  $fields 查询的表字段
     * @return bool or array 
     * @author hanxy
     */
    public function getUserPositionInfoByUserID($userID, $fields='*'){
        $data = false;
        $command = $this->getDbConnection()->createCommand()
                        ->select($fields)
                        ->from($this->tableName())
                        ->where('user_id=:user_id', array(':user_id'=>$userID))
                        ->queryRow();

        if($command) $data = $command;
        return $data;
    }


    /**
     * @desc 通过多个用户ID查询表信息
     * @param array   $userIDs 用户ID
     * @param string  $fields 查询的表字段
     * @return bool or array 
     * @author hanxy
     */
    public function getUserPositionInfoByUserIDs($userIDs, $fields='*'){
        $data = false;
        $command = $this->getDbConnection()->createCommand()
                        ->select($fields)
                        ->from($this->tableName())
                        ->where(array("IN", "user_id", $userIDs))
                        ->queryAll();

        if($command) $data = $command;
        return $data;
    }


    /**
     * 插入数据
     * @param  array $datas 参数和值
     * @return Ambigous <number, boolean>
     */
    public function insertData($datas){
        return $this->getDbConnection()->createCommand()->insert($this->tableName(), $datas);
    }


    /**
     * 通过sql语句插入数据
     * @param string $datas 插入的值
     * @return Ambigous <number, boolean>
     */
    public function insertSqlData($datas){
        $sql = "INSERT INTO ".$this->tableName()." (user_id,post_id,parent_user_id,create_time,update_time) VALUES ".$datas;
        return $this->getDbConnection()->createCommand($sql)->execute();
    }


    /**
     * @desc 通过用户ID查询表岗位名称 (0 员工、1 组长、2 主管)
     * @param integer $userID 用户ID
     * @return string 
     * @author hanxy
     */
    public function getUserPositionNameByUserID($userID){
        $data = false;
        $command = $this->getDbConnection()->createCommand()
                        ->select("(CASE post_id 
                                  WHEN 1 THEN '组长'
                                  WHEN 2 THEN '主管'
                                  ELSE '员工'
                                  END) AS post_name
                                ")
                        ->from($this->tableName())
                        ->where('user_id=:user_id', array(':user_id'=>$userID))
                        ->queryScalar();
                        
        if($command) $data = $command;
        return $data;
    }


    /**
     * 插入或者替换数据
     * @param  string $sql 参数和值
     * @return Ambigous <number, boolean>
     */
    public function ReplaceIntoSqlData($sql){
        return $this->getDbConnection()->createCommand($sql)->execute();
    }


    /**
     * @desc 通过多个用户ID查询表信息
     * @param integer $postID  岗位ID
     * @return bool or array 
     * @author hanxy
     */
    public function getUserPositionInfoByPostID($postID){
        $data = false;
        $command = $this->getDbConnection()->createCommand()
                        ->select('*')
                        ->from($this->tableName())
                        ->where('post_id=:post_id', array(':post_id'=>$postID))
                        ->queryAll();

        if($command) $data = $command;
        return $data;
    }


    /**
     * @desc 取出主管和组长的信息
     * @param array   $userIDs 用户ID
     * @return bool or array 
     * @author hanxy
     */
    public function getUserPositionDirectorAndLeaderByUserIDs($userIDs){
        $data = false;
        $command = $this->getDbConnection()->createCommand()
                        ->select('*')
                        ->from($this->tableName())
                        ->where(array("IN", "user_id", $userIDs))
                        ->andWhere('post_id > 0')
                        ->queryAll();

        if($command) $data = $command;
        return $data;
    }


    /**
     * @desc 取出员工的信息
     * @param array   $userIDs 用户ID
     * @return bool or array 
     * @author hanxy
     */
    public function getUserPositionPersonnelByUserIDs($userIDs){
        $data = false;
        $command = $this->getDbConnection()->createCommand()
                        ->select('user_id')
                        ->from($this->tableName())
                        ->where(array("IN", "user_id", $userIDs))
                        ->andWhere('parent_user_id = 0 and post_id = 0')
                        ->queryAll();

        if($command) $data = $command;
        return $data;
    }


    /**
     * @desc 取出主管的信息
     * @param array   $userIDs 用户ID
     * @return bool or array 
     * @author hanxy
     */
    public function getUserPositionDirectorByUserIDs($userIDs){
        $data = false;
        $command = $this->getDbConnection()->createCommand()
                        ->select('*')
                        ->from($this->tableName())
                        ->where(array("IN", "user_id", $userIDs))
                        ->andWhere('post_id = 2')
                        ->queryRow();

        if($command) $data = $command;
        return $data;
    }


    /**
     * @desc 取出组长的信息
     * @param array   $userIDs 用户ID
     * @return bool or array 
     * @author hanxy
     */
    public function getUserPositionLeaderByUserIDs($userIDs){
        $data = false;
        $command = $this->getDbConnection()->createCommand()
                        ->select('*')
                        ->from($this->tableName())
                        ->where(array("IN", "user_id", $userIDs))
                        ->andWhere('post_id = 1')
                        ->queryAll();

        if($command) $data = $command;
        return $data;
    }


    /**
     * @desc 页面的跳转链接地址
     */
    public static function getIndexNavTabId() {
        return Menu::model()->getIdByUrl('/users/users/list');
    }

}