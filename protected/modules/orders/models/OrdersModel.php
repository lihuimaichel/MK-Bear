<?php
/**
 * @desc 规定数据库
 * @author Gordon
 */
class OrdersModel extends UebModel {
   
	/**
	 * @desc 设置连接的库
	 * @return string
	 */
    public function getDbKey() {
        return 'db_oms_order';
    }
    
}