<?php
/**
 * @package Ueb.modules.AmazonModel.models
 */
class AmazonAsinWarehouse extends AmazonModel { 

    public static function model($className = __CLASS__) {     
        return parent::model($className);
    }

    public function tableName() {
        return 'ueb_amazon_overseas_warehouse_asin_map';
    }
    
    /**
     * Declares attribute labels.
     * @return array
     */
    public function attributeLabels() {
        return array( 
            'id'                  => Yii::t('amazon_product', 'ID'),
            'asin'                => 'ASIN',
            'asin1'               => Yii::t('amazon_product', 'Asin1'),
            'overseas_warehouse_id' => Yii::t('amazon_product', 'Oerseas Warehouse ID'),
            'sku'                 => Yii::t('amazon_product', 'Sku'),
            'seller_sku'          => Yii::t('amazon_product', 'Seller Sku'),
            'seller'              => Yii::t('amazon_product', 'Seller'),
            'account_name'        => Yii::t('amazon_product', 'Account Name'),
        );
    }

    /**
     * @desc 获取仓库列表
     * @return array
     */
    public static function getWarehouseAllList(){
        $warehouseConfigModel = new AmazonWarehouseConfig();
        return $warehouseConfigModel->getWarehouseList();
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
                        'search'=>'LIKE',
                        'htmlOption' => array(
                                'size' => '22',
                        )
                ),             
                array(
                        'name'=>'seller_sku',
                        'type'=>'text',
                        'search'=>'LIKE',
                        'htmlOption' => array(
                                'size' => '22',
                        )
                ),
                array(
                        'name'=>'overseas_warehouse_id',
                        'type'=>'text',
                        'search'=>'LIKE',
                        'htmlOption' => array(
                                'size' => '22',
                        )
                ),                           
        		array(
        				'name'=>'asin',
        				'type'=>'text',
        				'search'=>'LIKE',
        				'htmlOption' => array(
        						'size' => '22',
        				)
        		), 
                array(
                        'name'=>'seller',
                        'type'=>'text',
                        'search'=>'LIKE',
                        'htmlOption' => array(
                                'size' => '22',
                        )
                ),   
                array(
                        'name'=>'account_name',
                        'type'=>'text',
                        'search'=>'LIKE',
                        'htmlOption' => array(
                                'size' => '22',
                        )
                ),                                            
        );
        return $result;
    }

    /**
     * @desc 导入信息
     * @param string $excelFile
     * @return string
     */
    public function saveAsinDataByExcel($excelFile){
    	Yii::import('application.vendors.MyExcel.php');    	
    	$data = new MyExcel();    	
    	$excelData = $data->get_excel_con($excelFile);    	

        if (count($excelData) < 2) return 'no_excel_data';

        $have_insert_sql = 0;
        $encryptSku = new encryptSku();
        $sql = "insert into " .$this->tableName(). "(sku,seller_sku,overseas_warehouse_id,asin,seller,account_name) values ";
        for($i=2;$i<= count($excelData);$i++){
            $sellSKU             = (string)$excelData[$i]['A'];
            $overseasWarehouseID = (int)$excelData[$i]['B']; //海外仓ID（账号ID）
            $asin                = (string)$excelData[$i]['C'];
            $seller              = (string)$excelData[$i]['D'];
            $accountName         = (string)$excelData[$i]['E'];

            //对在线SKU进行解密
            $sku = '';
            if (!empty($sellSKU)){
                $sku = $encryptSku->getAmazonRealSku2($sellSKU);
            }

            //asin/在线SKU/海外仓ID三项都不能为空
            if ($overseasWarehouseID > 0 && !empty($asin) && strlen($asin) < 30 && !empty($sellSKU)){
                $sql .="('" .trim(addslashes($sku)). "','" .trim(addslashes($sellSKU)). "'," .$overseasWarehouseID. ",'" .trim(addslashes($asin)). "','" .trim(addslashes($seller)). "','" .trim(addslashes($accountName)). "'),";
                $have_insert_sql = 1;
            }
        }    

        if ($have_insert_sql == 1){
            $sql = substr($sql,0,strlen($sql)-1);
            $ret = $this->getDbConnection()->createCommand($sql)->execute();  
            if ($ret){
                //删除重复记录(并且是保留最新插入的重复记录)，通过asin+sell_sku来判断重复（唯一）
                $delsql = "delete from " .$this->tableName(). " where id not in ( select * from ( select max(id) from " .$this->tableName(). " group by asin,seller_sku ) as u )";
                $this->getDbConnection()->createCommand($delsql)->execute(); 
                return 'seccess';
            } 
        }else{
            return 'excel_data_errors';
        }       
    }

    /**
     * @desc 根据ASIN+在线SKU获取海外仓影射记录
     * @param string $asin
     * @param string $sellSKU
     */
    public function getWarehouseInfoByAsin($asin = '', $sellSKU = ''){
        if(empty($asin) || empty($sellSKU)) return false;
        $ret = $this->dbConnection->createCommand()
                ->select('*')
                ->from($this->tableName())
                ->where('asin = "'.$asin.'"')
                ->andWhere('seller_sku = "'.$sellSKU.'"')
                ->queryRow();
        return $ret;
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