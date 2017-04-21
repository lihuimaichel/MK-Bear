<?php

class WishOverseasWarehouse extends WishModel { 

    public $send_warehouse;

    public static function model($className = __CLASS__) {     
        return parent::model($className);
    }

    public function tableName() {
        return 'ueb_wish_overseas_warehouse_map';
    }

    /**
     * @desc 获取仓库列表
     * @return array
     */
    public static function getWarehouseAllList(){
       /*  return array(
                '41'  => array('name'=>'光明本地仓'),
                '14'  => array('name'=>'EBAY-UK'),
                '34'  => array('name'=>'4px英国仓'),
                '58'  => array('name'=>'Winit-UKMA'),
                '62'  => array('name'=>'4px美东仓'),       
        ); */
        $wishWarehouseConfigModel = new WishWarehouseConfig();
        return $wishWarehouseConfigModel->getWarehouseList();
    }
   
    /**
     * @desc 获取仓库键值对
     * @return array
     */
    public static function getWarehouseList(){
        $list = array();
        $WarehouseList = self::getWarehouseAllList();
        foreach ($WarehouseList as $key => $val){
            $list[$key] = $val['name'];
        }
        return $list;
    }    

    /**
     * @desc 获取仓库id列表
     * @return array
     */
    public static function getWarehouseIDs(){
        $list = self::getWarehouseList();
        $WarehouseIDs = array_keys($list);
        return $WarehouseIDs;
    }
    
    /**
     * @desc 获取仓库名称
     * @param int $WarehouseID
     * @return string
     */
    public static function getWarehouseName($WarehouseID){
        $list = self::getWarehouseList();
        return $list[$WarehouseID];
    }   
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array( 
            'id'                    => Yii::t('wish_product_statistic', 'ID'),
            'overseas_warehouse_id' => Yii::t('wish_product_statistic', 'Oerseas Warehouse ID'),
            'sku'                   => Yii::t('wish_product_statistic', 'Sku'),
            'product_id'            => Yii::t('wish_product_statistic', 'Product ID'),
            'seller'                => Yii::t('wish_product_statistic', 'Seller'),
            'account_name'          => Yii::t('wish_product_statistic', 'Account Name'),   
            'seller_id'             => Yii::t('wish_product_statistic', 'Seller'),
            'account_id'            => Yii::t('wish_product_statistic', 'Account Name'),                      
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
      	$data = $this->addtions($dataProvider->data);
      	$dataProvider->setData($data);
      	return $dataProvider;
    }
    
    public function addtions($datas){
    	if(empty($datas)) return $datas;

        $wishAccountList = UebModel::model("WishAccount")->getIdNamePairs();
        $warehouseList = $this->getWarehouseList();
        foreach ($datas as &$data){
            $data['account_name'] = isset($wishAccountList[$data['account_id']]) ? $wishAccountList[$data['account_id']] : '';
            $data['send_warehouse'] = $warehouseList[$data['overseas_warehouse_id']]; //发货仓库
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
                        'name'=>'sku',
                        'type'=>'text',
                        'search'=>'=',
                        'htmlOption' => array(
                                'size' => '22',
                        )
                ),           
        		array(
        				'name'=>'product_id',
        				'type'=>'text',
        				'search'=>'LIKE',
        				'htmlOption' => array(
        						'size' => '22',
        				)
        		),
                array(
                        'name'   =>'overseas_warehouse_id',
                        'type'   =>'dropDownList',
                        'search' =>'=',
                        'data'   =>$this->getWarehouseList()
                ),                  
                array(
                        'name'      => 'account_id',
                        'type'      => 'dropDownList',
                        'search'    => '=',
                        'data'      => UebModel::model("WishAccount")->getIdNamePairs(),
                ),
                
                array(
                        'name'      => 'seller_id',
                        'type'      => 'dropDownList',
                        'search'    => '=',
                        'data'      => User::model()->getUserNameByDeptID(array(15, 37)),
                ),                                                
        );
        return $result;
    }

    /**
     * @desc 导入信息
     * @param string $excelFile
     * @return string
     */
    public function saveDataByExcel($excelFile){
    	Yii::import('application.vendors.MyExcel.php');    	
    	$data = new MyExcel();
        $seller_id = 0;
        $account_id = 0;
    	$excelData = $data->get_excel_con($excelFile);
        if (count($excelData) < 2) return 'no_excel_data';

        $have_insert_sql = 0;
        $encryptSku = new encryptSku();
        $sql = "insert into " .$this->tableName(). "(sku,product_id,overseas_warehouse_id,seller_id,account_id) values ";
        for($i=2;$i<= count($excelData);$i++){
            $sku                 = trim((string)$excelData[$i]['A']);            
            $productID           = trim((string)$excelData[$i]['B']);
            $overseasWarehouseID = (int)$excelData[$i]['C'];    //海外仓ID（账号ID）
            $seller              = trim((string)$excelData[$i]['D']); //销售人员
            $accountName         = trim((string)$excelData[$i]['E']); //账号
            //转换为销售人员ID
            if(!empty($seller)) $seller_id = User::model()->getUserIdByName(addslashes($seller));

            //转换为账号ID
            if (!empty($accountName)) {
                $accountInfo = WishAccount::model()->getInfoByAccountname(addslashes($accountName));
                if($accountInfo) $account_id = $accountInfo['id'];
            }

            if ($overseasWarehouseID > 0 && !empty($productID)){
                $sql .="('" .trim(addslashes($sku)). "','" .trim(addslashes($productID)). "','" .$overseasWarehouseID. "','" .$seller_id. "','" .$account_id. "'),";
                $have_insert_sql = 1;
            }
        }    

        if ($have_insert_sql == 1){
            $sql = substr($sql,0,strlen($sql)-1);
            $ret = $this->getDbConnection()->createCommand($sql)->execute();  
            if ($ret){
                //删除重复记录(并且是保留最新插入的重复记录)
                $delsql = "delete from " .$this->tableName(). " where id not in ( select * from ( select max(id) from " .$this->tableName(). " group by product_id ) as u )";
                $this->getDbConnection()->createCommand($delsql)->execute(); 
                return 'seccess';
            } 
        }else{
            return 'excel_data_errors';
        }       
    }

    /**
     * @desc 根据产品ID获取记录
     * @param string $productID
     */
    public function getWarehouseInfoByProductID($productID = ''){
        if(empty($productID)) return false;
        $ret = $this->dbConnection->createCommand()
                ->select('*')
                ->from($this->tableName())
                ->where('product_id = "'.$productID.'"')
                ->queryRow();
        return $ret;
    }   

    /**
     * @desc 根据条件获取多条数据
     * @param unknown $fields
     * @param unknown $conditions
     * @param string $param
     * @return mixed
     */
    public function getListByCondition($fields, $conditions, $param = null){
        return $this->getDbConnection()->createCommand()
                                ->select($fields)
                                ->from($this->tableName())
                                ->where($conditions, $param)
                                ->queryAll();
    }     

    /**
     * @desc 根据条件更新数据
     * @param string $condition
     * @param array $updata
     * @return boolean
     */
    public function updateListByCondition($conditions, $updata){
        if(empty($conditions) || empty($updata)) return false;
        return $this->getDbConnection()->createCommand()
                    ->update($this->tableName(), $updata, $conditions);
    }    
   	
    /**
     * @desc 根据自增ID更新数据
     * @param int $id
     * @param array $updata
     * @return boolean
     */
    public function updateInfoByID($id, $updata){
        if(empty($id) || empty($updata)) return false;
        $conditions = "id = ".$id;
        return $this->getDbConnection()->createCommand()->update($this->tableName(), $updata, $conditions);
    } 

    /**
     * @desc 新增刊登
     * @param array $data
     * @return int
     */
    public function addWarehouseAdd($data){
        if(empty($data)) return false;
        $id = 0;
        $ret = $this->getDbConnection()->createCommand()->insert($this->tableName(), $data);
        if($ret) $id = $this->getDbConnection()->getLastInsertID();
        return $id;
    }
    
    /**
     * 根据条件获取信息
     */
    public function getInfoByCondition($where) {
        if (empty($where)) return false;
        return $this->getDbConnection()->createCommand()
                    ->select('*')
                    ->from($this->tableName())
                    ->where($where)
                    ->queryRow();
    }    


}