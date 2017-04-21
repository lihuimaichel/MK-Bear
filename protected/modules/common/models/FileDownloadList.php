<?php
/**
 * @desc  文件下载
 * @author lihy
 * @since 2016-09-07
 */
class FileDownloadList extends CommonModel {


    
    /**
     * @desc 获取模型
     * @param system $className
     * @return Ambigous <CActiveRecord, unknown, multitype:>
     */
    public static function model($className = __CLASS__) {
            return parent::model($className);
    }

    /**
     * @desc 设置表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
            return 'ueb_file_download_list';
    }
	
    /**
     * @desc 保存数据
     * 
     */
    public function addData($data) {
        return $this->getDbConnection()->createCommand()->insert(self::tableName(), $data);
    }
    
    
    // ============================= Search ==============================
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array(
	            'id'                =>  Yii::t('system', 'No.'),
	            'filename'			=>	'文件名称',
	            'create_user_id'    =>	'创建用户',
	            'create_time'       =>	'创建时间',
        		'local_path'		=>	'文件路径',
        );
    }
    
    /**
     * get search info
     */
    public function search() {
            $sort = new CSort();
            $sort->attributes = array(
                'defaultOrder'  => 'id',
            );
            $dataProvider = parent::search(get_class($this), $sort);
            $data = $this->additions($dataProvider->getData());
            $dataProvider->setData($data);
            return $dataProvider;
    }
    
    public function additions($datas){
    	if($datas){
    		foreach ($datas as &$data){
    			//下载地址
    			
    			$data['local_path'] = CHtml::link("文件地址", "/".ltrim($data['local_path'], "./"), array("target"=>"__blank"));
    		}
    	}
    	return $datas;
    }
    /**
     * filter search options
     * @return type
     */
    public function filterOptions() {
            $result = array(
                array(
                                'name'=>'create_time',
                                'type'=>'text',
                                'search'=>'RANGE',
                              	'htmlOptions'=>array(
            									'class'=>'date',
                              					'dateFmt'=>'yyyy-MM-dd HH:mm:ss'
            							)
                ),
               

            );
            return $result;
    }
    

    // ============================= Search End==============================
}