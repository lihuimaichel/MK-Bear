<?php
/**
 * @desc SuggestProductMatch model
 * @author wx 
 */
class SuggestProductMatch extends ProductsModel {
    
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }
    
    public function rules() {
    	return array(
    			array('item_id,match_rate,sku,create_time,modify_time', 'safe')
    	);
    }
    
    /**
     * @desc 数据库表名
     * @see CActiveRecord::tableName()
     */
    public function tableName() {
        return 'ueb_suggest_product_match';
    }
    
    /**
     * @desc 匹配标题
     * @param string $title
     * @param integer $top
     */
    public function getSuggestProductMatch( $title,$top = 5 ){
    	$ret = array();
    	if( isset($title) && !empty($title) ){
    		$title = addslashes($title);
    		$title = str_replace( array(' to ',' for ',' in ',' & ',' + ',' of ',' and ',' or ',' For ',' Of ',' To ',' And ',' Of ',' In '),' ',$title );
    		$titleArr = explode(' ', $title);
    		$titleNum = count($titleArr);
    		
    		$ret = $this->dbConnection->createCommand()
    				->select( 'sku,count(title_word)/'.$titleNum.'*100 as rate' )
    				->from( ProductTitleWord::model()->tableName() )
    				->where( 'title_word in ('.MHelper::simplode($titleArr).')' )
    				->limit($top)
    				->group('sku')
    				->order('rate DESC')
    				->queryAll();
    	}
    	return $ret;
    	
    }
    
	
}