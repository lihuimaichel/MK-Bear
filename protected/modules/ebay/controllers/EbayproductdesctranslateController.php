<?php
/**
 * @desc ebay产品描述翻译
 */
class EbayproductdesctranslateController extends UebController
{
    /**
     * ebay描述翻译
     * 取在售中SKU累计销量最好的账号的英文标题作为翻译源，用google翻译工具自动翻译成其他小语种
       ——账号站点销量取值优先顺序：先取Ebay三个英文站点（UK、AU、CA）中销量最好的英文标题作为翻译源，如无销量则取速卖通
     * @link /ebay/ebayproduct/translate/current_page/1/end_page/100
     */
    public function actionTranslate() {
        set_time_limit(3600);
        ini_set('display_errors', true);
        error_reporting(-1);

        $currPage = trim(Yii::app()->request->getParam('current_page','1'));
        $endPage = trim(Yii::app()->request->getParam('end_page',''));
        $sku_test = trim(Yii::app()->request->getParam('sku',''));
        $lanCodeArr = array('fr'=>'French','de'=>'German','es'=>'Spain');//需要翻译的语言
        $tableName = 'ueb_product_description';
        $nowTime = date('Y-m-d H:i:s');
        $market_product = EbayProduct::model();

        //分页处理
        $obj = $market_product->dbConnection->createCommand()
                    ->select('count(*) as total')
                    ->from($tableName)
                    ->where(" language_code = 'english' ")
                    ->andWhere(" status = 0 ")
                    ->andWhere("title!='' AND description!='' ");
        $sku_test != '' && $obj->andWhere(" sku = '{$sku_test}' ");
        $res = $obj->queryRow();
        $total = (int)$res['total'];    
        if ($total == 0) {
            die('no data need to translate');
        }
        $pageSize = 300;
        $pageCount = ceil($total/$pageSize);
        echo 'total:'.$total.' pageCount:'.$pageCount."<br>";
        if ($currPage > $pageCount || $currPage < 1 ) {
            die('$currPage is invalid');
        }
        if ($endPage != '' && $endPage < 1 ) {
            die('$endPage is invalid');
        }      
        if ($endPage == '' || $endPage > $pageCount) {
            $endPage = $pageCount;
        }
        for($page = $currPage; $page<= $endPage; $page++) {
            $offset = ($page - 1) * $pageSize;
            $obj = $market_product->dbConnection->createCommand()
                    ->select('id,sku,title,description,included')
                    ->from($tableName)
                    ->where(" language_code = 'english' ")
                    ->andWhere(" status = 0 ")
                    ->andWhere("title!='' AND description!='' ")
                    ->order(' sku asc ')
                    ->limit($pageSize, $offset);
            $sku_test != '' && $obj->andWhere(" sku = '{$sku_test}' ");                    
            $res = $obj->queryAll();
            if (empty($res)) {
                break;
            }
            foreach ($res as $v) {
                $sta = $market_product->dbConnection->createCommand()
                    ->select('status')
                    ->from($tableName)
                    ->where(" id = :id ", array('id'=>$v['id']))
                    ->limit(1)
                    ->queryRow();

                if (!empty($sta['status']) ) {//如果已经在翻译中或已翻译则跳过
                    continue;
                }

                $sku = $v['sku'];
                $title_en = trim($v['title']);
                $description_en = preg_replace('/<img[^>]+\/?>/s','', trim($v['description']) );//去掉img
                $description_en = preg_replace('/<table[^>]+>.*?<\/table>/is','',$description_en);//去掉table
                $description_en = preg_replace('/<\/?span>/is','',$description_en);//去除多余的span标签
                $description_en = preg_replace('/<span[^>]+>/is','<span>',$description_en);//去除多余的span标签
                $included_en = trim($v['included']);                
                $market_product->dbConnection->createCommand()
                            ->update($tableName, array('status'=>1), "id=".$v['id']);//更新英文记录翻译中
                $dbTransaction = $market_product->dbConnection->beginTransaction ();// 开启事务
                try {
                    $status_en = 2;
                    foreach ($lanCodeArr as $targetLanguageCode => $lanCode) {
                        $row = array();
                        $row['sku'] = $sku;
                        $row['language_code'] = $lanCode;

                        //title
                        if ($title_en != '') {
                            $cnt=0;//连接超时自动重试3次
                            while($cnt < 3 && ($title_code = $this->getTranslateResponse($title_en,$targetLanguageCode)) === false ) {
                                $cnt++;
                            }
                        } else {
                            $title_code = '';
                        }
                        //echo 'title_'.$lanCode.': '.$title_code."<br>";

                        //description
                        if ($description_en != '') {
                            $cnt=0;//连接超时自动重试3次
                            while($cnt < 3 && ($description_code = $this->getTranslateResponse($description_en,$targetLanguageCode)) === false ) {
                                $cnt++;
                            }
                        } else {
                            $description_code = '';
                        }                     
                        //echo 'description_'.$lanCode.': '.$description_code."\r\n<br>";

                        //included
                        if ($included_en != '') {
                            $cnt=0;//连接超时自动重试3次
                            while($cnt < 3 && ($included_code = $this->getTranslateResponse($included_en,$targetLanguageCode)) === false ) {
                                $cnt++;
                            }
                        } else {
                            $included_code = '';
                        }

                        //echo 'included_'.$lanCode.': '.$included_en."\r\n<br>";
                        if ($title_code === false || $description_code === false || $included_code === false) {
                            throw new Exception("dailyLimitExceeded");
                        }   

                        $row['title'] = $title_code;
                        $row['description'] = $description_code;
                        $row['included'] = $included_code;
                        $row['status'] = $title_code != '' && $description_code != '' ? 2 : 0;
                        if ($row['status'] == 0) {
                            $status_en = 0;
                        }
                        MHelper::printvar($row,false);
                        $isOk = $this->saveProductDescription($sku, $lanCode, $row);
                        if (!$isOk) {
                            throw new Exception("saveProductDescription Error ");
                        }
                    }
                    $market_product->dbConnection->createCommand()
                                    ->update($tableName, array('status'=>$status_en), "id=".$v['id']);//更新英文记录已翻译
                    $dbTransaction->commit ();
                } catch (Exception $e) {
                    echo json_encode($v).'----'.$e->getMessage()."\r\n<br>";
                    $dbTransaction->rollback ();
                    $market_product->dbConnection->createCommand()
                                    ->update($tableName, array('status'=>0), "id=".$v['id']);//
                    if ($e->getMessage() == 'dailyLimitExceeded') {//额度不够直接跳出for循环
                        break 2;
                    }
                }
            }//endforeach1
        }//endfor
        die('ok');
    }

    public function getTranslateResponse($sourceText,$targetLanguageCode) {
        $key_config = array(
            'kk20111111',
            'kk20112222',
            'kk20113333',
            'kk20114444',
            'kk20115555',
            'kk20116666',//11
            'businessaryer',
            'damondary',
            'everdayed',
            'firstfastered',
            'gardenlivery',
            'hillermoutain',//22
            'rainbowonline88',
            'unitedsellersonline',
            'xdeals2013',
            'kk20110008',
            'simitte005',
            'ordernow2013',    
            //'simitte001',
            //'vakind88',
            //'shoppingonnewfrog',
            //'affectionionary',//33
            // 'innersetting',
            // 'loverlyer',
            // 'monsterlady123',
            // 'neverstop123456',
            // 'omygodolden',
            // 'petermen123456',
            // 'questionation123',
            // 'recoveryeration',//22                     
        );
        $max = count($key_config)-1;
        $api_key = $key_config[ mt_rand(0,$max) ];
        echo "<hr>api_key:".$api_key."<hr>";
        //url
        $url  = 'http://47.88.19.103/translate.php?api_key='.$api_key.'&text='.rawurlencode($sourceText).'&from=en&to='.$targetLanguageCode;
        //curl request
        $ch = curl_init(); // init curl
        curl_setopt($ch, CURLOPT_URL, $url); // set the url to fetch
        curl_setopt($ch, CURLOPT_HEADER, 0); // set headers (0 = no headers in result)
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // type of transfer (1 = to string)
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // time to wait in
        curl_setopt($ch, CURLOPT_POST, 0);
        $content = curl_exec($ch); // make the call
        curl_close($ch); // close the connection  
        if( $content === false)
        {
            echo 'Curl error: ' . curl_error($ch);
            return '';
        }  
        $res = '';   
        $rtn = json_decode($content,true);  
        MHelper::printvar($rtn,false);
        if (isset($rtn['data']['translations'])) {
            foreach ($rtn['data']['translations'] as $v) {
                $res .= $v['translatedText'];  
            }
        } else if ( isset($rtn['error'] ) ) {
            return false;
        }
        return $res;
    }

    public function parseTemplate($sku,$platformCode='') {
        if ($platformCode == '') {
            $platformCode = Platform::CODE_ALIEXPRESS;
        }
        $listing = AliexpressProduct::model();

        $record = array();
        //单品刊登
        $row_main = $listing->dbConnection->createCommand()
                ->select('a.account_id,a.aliexpress_product_id,a.sku,a.subject,a.is_variation,b.detail')
                ->from($listing->tableName() .' AS a')
                ->join ('ueb_aliexpress_product_extend AS b', 'a.id = b.product_id' )
                ->andWhere("a.sku='{$sku}'")
                ->andWhere("a.is_variation=0")
                ->order("a.gmt_create desc")
                ->limit(1)
                ->queryRow();
        if (!empty($row_main)) {
            $record = $row_main;
        }

        //多属性刊登
        if ( empty($record) ) {
            $row_child = $listing->dbConnection->createCommand()
                    ->select('a.account_id,a.aliexpress_product_id,v.sku,a.subject,a.is_variation,b.detail')
                    ->from($listing->tableName() .' AS a')
                    ->join ('ueb_aliexpress_product_extend AS b', 'a.id = b.product_id' )
                    ->leftJoin ('ueb_aliexpress_product_variation AS v', 'a.id = v.product_id' )
                    ->andWhere("v.sku='{$sku}'")
                    ->andWhere("a.is_variation=1")
                    ->order("a.gmt_create desc")
                    ->limit(1)
                    ->queryRow();  
            $record = $row_child;           
        }

        MHelper::printvar($record,false);
        if (empty($record)) {
            return '';
        }

        $row = array();
        $row['sku'] = $sku;
        $row['language_code'] = 'english';

        $accountID = $record['account_id'];
        $itemID = $record['aliexpress_product_id'];
        $tplcon = MHelper::getDescription($platformCode, $accountID, $itemID);
        if ($tplcon == '') {
            return '';
        }
        //MHelper::printvar($tplcon);
        
        // title       
        $row['title'] = trim($record['subject']);

        // description
        $description = '';
        preg_match_all('/<div[^>]+class="des_title"[^>]*>Description<\/div>\s*<div[^>]*class="des"[^>]*>(.*?)<\/div>/s',$tplcon,$matchs);
        if (!empty($matchs[1][0])) {
            $description = trim($matchs[1][0]);
        }
        $row['description'] = $description;

        // included
        $included = '';
        preg_match_all('/<div[^>]+class="des_title"[^>]*>Included<\/div>\s*<div[^>]*class="des"[^>]*>(.*?)<\/div>/s',$tplcon,$matchs);
        if (!empty($matchs[1][0])) {
            $included = trim($matchs[1][0]);
        }
        $row['included'] = $included;
        $row['note'] = 'itemID:'.$itemID.','.'accountID:'.$accountID.','.($record['is_multiple']==1?'多属性刊登':'单品刊登').";";
        MHelper::printvar($row,false);
        //save
        return $this->saveProductDescription($row['sku'], $row['language_code'], $row);        
    }

    public function saveProductDescription($sku,$lanCode,$row) {
        try {
            $tableName = 'ueb_product_description';
            $market_product = EbayProduct::model();
            $info = $market_product->dbConnection->createCommand()
                            ->select('id')
                            ->from($tableName)
                            ->where("language_code='{$lanCode}' and sku='{$sku}' ")
                            ->queryRow();
            if (!empty($info)) {
                $market_product->dbConnection->createCommand()
                                ->update($tableName, $row, "id=".$info['id']);
            } else {
                $market_product->dbConnection->createCommand()
                                ->insert($tableName, $row);
            }  
            return true;
        } catch (Exception $e) {
            //echo $e->getMessage();
            return false;
        }
    }  


    /**
     * 同步market_ebay.ueb_product_description描述到产品描述信息表(French/German/Spain)
     * @author yangsh
     * @since 2016-07-27
     * @link /ebay/ebayproduct/syncdesc/language_code/French   
     */
    public function actionSyncdesc() {
        set_time_limit(2*3600);
        $languageCode = trim(Yii::app()->request->getParam('language_code',''));
        if ($languageCode == '') {
            die('language_code is empty');
        }
        $nowTime = date("Y-m-d H:i:s");
        $tableName = 'ueb_product_description';
        $model = Productdesc::model();
        $ebayProduct = UebModel::model('EbayProduct');
        //分页
        $res = $ebayProduct->dbConnection->createCommand()
                ->select("count(*) as total")
                ->from($tableName)
                ->where("is_to_oms=0")
                ->andWhere("title!=''")
                ->andWhere("language_code=:code",array('code'=>$languageCode))
                ->queryRow();
        $total = $res['total'];
        $pageSize = 1000;
        $pageCount = ceil($total/$pageSize);
        echo 'total: '.$total.' ### pageCount: '.$pageCount." <br>";

        for ($page=1; $page <= $pageCount; $page++) { 
            $offset = ($page - 1) * $pageSize;
            $obj = $ebayProduct->dbConnection->createCommand()
                ->select("*")
                ->from($tableName)
                ->where("is_to_oms=0")
                ->andWhere("title!=''")
                ->andWhere("language_code=:code",array('code'=>$languageCode))
                ->order("sku asc")
                ->limit($pageSize, $offset);
            //echo $obj->Text;exit;
            $res = $obj->queryAll();
            if (empty($res)) {
                continue;
            }
            foreach ($res as $v) {
                $sku = $v['sku'];
                echo $sku."<hr>";

                $productInfo = $model->dbConnection->createCommand()
                    ->select("id")
                    ->from($model->tableName())
                    ->where("sku=:sku",array('sku'=>$sku))
                    ->andWhere("language_code=:code",array('code'=>$languageCode))
                    ->queryRow();

                $row = array();
                $row['sku']             = $sku;
                $row['language_code']   = $languageCode;
                $row['title']           = $v['title'];
                $row['description']     = $v['description'];
                $row['included']        = $v['included'];

                if (empty($productInfo)) {
                    $info = $model->dbConnection->createCommand()
                        ->select("id")
                        ->from(Product::tableName())
                        ->where("sku=:sku",array('sku'=>$sku))
                        ->queryRow();
                    if (empty($info)) {
                        continue;
                    }
                    $row['product_id']      = $info['id'];
                    $row['create_user_id']  = 0;
                    $row['create_time']     = $nowTime;
                    $isok = $model->dbConnection->createCommand()
                           ->insert($model->tableName(),$row );
                    $product_id = $model->dbConnection->getLastInsertID();
                } else if ( $productInfo['title'] !='' || $productInfo['description'] !='' ) {
                    echo $sku.' title or description is not empty<br>';
                    continue;
                } else {
                    $isok = $model->dbConnection->createCommand()
                           ->update($model->tableName(),$row, "id=:id", array('id'=>$productInfo['id']) );
                    $product_id = $productInfo['id'];
                }
                //MHelper::printvar($row,false);
                if ($isok) {
                    $ebayProduct->dbConnection->createCommand()
                        ->update($tableName, array('is_to_oms'=>1,'to_oms_time'=>$nowTime), "id={$v['id']}");
                }
                echo '$id: '.$product_id.' ### $sku: '.$sku.' ## $languageCode: '.$languageCode.' save success<br>'; 
                //break 2;
            }
        }
        die('finish');
    }    

    public function actionUpdateprostatus() {
        set_time_limit( 3600 );
        ini_set('display_errors', true);
        error_reporting(-1);

        $tableName = 'ueb_product_description';
        $nowTime = date('Y-m-d H:i:s');
        $market_product = EbayProduct::model();
        $product = Product::model();
        $productdesc = Productdesc::model();

        //分页处理
        $obj = $product->dbConnection->createCommand()
                    ->select('count(*) as total')
                    ->from($product->tableName() . ' AS p')
                    //->leftJoin ( $productdesc->tableName () . ' AS d', 'd.sku = p.sku' )
                    ->where(" p.product_status in(2,3,4,5,6,8) ");//
        //echo $obj->Text;exit;
        $res = $obj->queryRow();

        $total = $res['total'];    
        $pageSize = 5000;
        $pageCount = ceil($total/$pageSize);
        echo 'total:'.$total.' pageCount:'.$pageCount."<br>";   

        $c = 0;
        for($page = 1; $page<= $pageCount; $page++) {
            $offset = ($page - 1) * $pageSize;
            $res = $product->dbConnection->createCommand()
                        ->select('p.sku,p.product_is_multi')
                        ->from($product->tableName() . ' AS p')
                        //->leftJoin ( $productdesc->tableName () . ' AS d', 'd.sku = p.sku' )
                        ->where(" p.product_status in(2,3,4,5,6,8) ")
                        ->order(' p.sku asc ')
                        ->limit($pageSize, $offset)
                        ->queryAll();
            if (empty($res)) {
                continue;
            }
            foreach ($res as $data) {
                $isok = $market_product->dbConnection->createCommand()
                                                    ->update($tableName, array('product_is_multi'=>$data['product_is_multi']), "sku='{$data['sku']}'");

                $c++;
                echo $pageCount.'--current-- '.$page.'--'.$data['sku'].'--c--'.$c.'--'.($isok ? 'success' : 'failure')."\r\n<br>";
            }
        }
        die('ok');
    }

    /**
     * 同步产品到临时表
     * @link /ebay/ebayproduct/syncproduct
     */
    public function actionSyncproduct() {
        die('forbidden');
        set_time_limit(3600);
        ini_set('display_errors', true);
        error_reporting(-1);

        $tableName = 'ueb_product_description';
        $nowTime = date('Y-m-d H:i:s');
        $market_product = EbayProduct::model();
        $product = Product::model();
        $productdesc = Productdesc::model();

        //分页处理
        $obj = $product->dbConnection->createCommand()
                    ->select('count(*) as total')
                    ->from($product->tableName() . ' AS p')
                    ->leftJoin ( $productdesc->tableName () . ' AS d', 'd.sku = p.sku' )
                    ->where(" p.product_status in(2,3,4,5,6,8) ");
                    //->andWhere(" p.product_is_multi=2 ");
        //echo $obj->Text;exit;
        $res = $obj->queryRow();

        $total = $res['total'];    
        $pageSize = 5000;
        $pageCount = ceil($total/$pageSize);
        echo 'total:'.$total.' pageCount:'.$pageCount."<br>";   

        $c = 0;
        for($page = 1; $page<= $pageCount; $page++) {
            $offset = ($page - 1) * $pageSize;
            $res = $product->dbConnection->createCommand()
                        ->select('p.sku,p.product_is_multi,d.language_code,d.title,d.description,d.included,d.customs_name')
                        ->from($product->tableName() . ' AS p')
                        ->leftJoin ( $productdesc->tableName () . ' AS d', 'd.sku = p.sku' )
                        ->where(" p.product_status in(2,3,4,5,6,8) ")
                        //->andWhere(" p.product_is_multi=2 ")
                        ->order(' p.sku asc ')
                        ->limit($pageSize, $offset)
                        ->queryAll();
            if (empty($res)) {
                continue;
            }
            foreach ($res as $data) {
                //只同步英文
                if ( $data['language_code'] != 'english') {
                    continue;
                }  
                $sku = $data['sku'];
                $data['status'] = 0;
                $data['updated'] = $nowTime;
                $data['note'] = '';
                if ( empty($data['customs_name']) ) {
                    $data['customs_name'] = '';
                }                
                $row = $market_product->dbConnection->createCommand()
                                ->select('id,title,description')
                                ->from($tableName)
                                ->where("sku=:sku and language_code=:code", array('sku'=>$sku,'code'=>$data['language_code']))
                                ->queryRow();
                $isok = true;
                if ( !empty($row) ) {
                    $isok = $market_product->dbConnection->createCommand()
                                                    ->update($tableName, $data, "id=".$row['id']);
                } else {
                    $isok = $market_product->dbConnection->createCommand()
                                                    ->insert($tableName, $data);
                }
                $c++;
                echo $pageCount.'--current-- '.$page.'--'.$data['sku'].'--'.$data['language_code'].'--c--'.$c.'--'.($isok ? 'success' : 'failure')."\r\n<br>";
            }
        }
        die('ok');
    }

    /**
     * 解析模板
     * @link /ebay/ebayproduct/parsetpl/current_page/1/sku/56912.03
     */
    public function actionParsetpl() {
        die('forbidden');
        set_time_limit(3600);
        ini_set('display_errors', true);
        error_reporting(-1);

        $currPage = trim(Yii::app()->request->getParam('current_page','1'));
        $sku_test = trim(Yii::app()->request->getParam('sku',''));
        $tableName = 'ueb_product_description';
        $nowTime = date('Y-m-d H:i:s');
        $market_product = EbayProduct::model();

        //分页处理
        $obj = $market_product->dbConnection->createCommand()
                    ->select('count(*) as total')
                    ->from($tableName)
                    ->where(" language_code = 'english' ")
                    ->andWhere(" status = 0 ");

        $sku_test != '' && $obj->andWhere(" sku = '{$sku_test}' ");
        $res = $obj->queryRow();
        $total = (int)$res['total'];    
        if ($total == 0) {
            die('no data need to translate');
        }
        $pageSize = 5000;
        $pageCount = ceil($total/$pageSize);
        echo 'total:'.$total.' pageCount:'.$pageCount."\r\n<br>";

        for($page = $currPage; $page<= $pageCount; $page++) {
            $offset = ($page - 1) * $pageSize;
            $obj = $market_product->dbConnection->createCommand()
                    ->select('sku')
                    ->from($tableName)
                    ->where(" language_code = 'english' ")
                    ->andWhere(" status = 0 ")
                    ->order(' sku asc ')
                    ->limit($pageSize, $offset);
            $sku_test != '' && $obj->andWhere(" sku = '{$sku_test}' ");
            $res = $obj->queryAll();
            if (empty($res)) {
                break;
            }
            foreach ($res as $v) {
                $sku = $v['sku'];
                $isok = $this->parseTemplate($sku);
                echo $sku.' english row save '.($isok ? 'success' : 'failure')."\r\n<br>";
            }
        }
        
        die('finish');
    }

    /**
     * @desc actionCheckstatus
     * @link /ebay/ebayproduct/checkstatus
     */
    public function actionCheckstatus() {
        set_time_limit(3600);
        ini_set('display_errors', true);
        error_reporting(-1);
        
        $currPage = trim(Yii::app()->request->getParam('current_page','1'));
        $endPage = trim(Yii::app()->request->getParam('end_page',''));        
        $sku_test = trim(Yii::app()->request->getParam('sku',''));
        $lanCodeArr = array('fr'=>'French','de'=>'German','es'=>'Spain');//需要翻译的语言
        $tableName = 'ueb_product_description';
        $nowTime = date('Y-m-d H:i:s');
        $market_product = EbayProduct::model();

        $obj = $market_product->dbConnection->createCommand()
                    ->select('count(*) as total')
                    ->from($tableName)
                    ->where(" language_code = 'english' ")
                    ->andWhere(" status = 0 ");
        $sku_test != '' && $obj->andWhere(" sku = '{$sku_test}' ");      
        $res = $obj->queryRow();

        $total = (int)$res['total'];    
        if ($total == 0) {
            die('no data ');
        }
        $pageSize = 1000;
        $pageCount = ceil($total/$pageSize);
        echo 'total:'.$total.' pageCount:'.$pageCount."<br>";
        if ($currPage > $pageCount || $currPage < 1 ) {
            die('$currPage is invalid');
        }
        if ($endPage != '' && $endPage < 1 ) {
            die('$endPage is invalid');
        }      
        if ($endPage == '' || $endPage > $pageCount) {
            $endPage = $pageCount;
        }
        for($page = $currPage; $page<= $endPage; $page++) {
            $offset = ($page - 1) * $pageSize;
            $obj = $market_product->dbConnection->createCommand()
                    ->select('id,sku')
                    ->from($tableName)
                    ->where(" language_code = 'english' ")
                    ->andWhere(" status = 0 ")
                    ->order('id desc')
                    ->limit($pageSize, $offset);
            $sku_test != '' && $obj->andWhere(" sku = '{$sku_test}' ");      
            $res = $obj->queryAll();
            if (empty($res)) {
                die('data is emtpy');
            }
            foreach ($res as $v) {
                $obj = $market_product->dbConnection->createCommand()
                        ->select('count(*) as total')
                        ->from($tableName)
                        ->where(array('in','language_code',$lanCodeArr))
                        ->andWhere("sku=:sku",array('sku'=>$v['sku']))
                        ->andWhere(" status = 2 ");
                $sku_test != '' && $obj->andWhere(" sku = '{$sku_test}' ");     
                $rs = $obj->queryRow();
                if (!empty($rs) && $rs['total'] == 3) {
                    echo 'update '.$v['sku'].' status=2 <br>';
                    $market_product->dbConnection->createCommand()
                                ->update($tableName, array('status'=>2), "id=".$v['id']);//更新英文记录翻译中
                }
            }
        }
        die('ok');
    }

    /**
     * @desc actionChecktranslate
     * @link /ebay/ebayproduct/checktranslate
     */
    public function actionChecktranslate() {
        set_time_limit(3600);
        ini_set('display_errors', true);
        error_reporting(-1);

        $code = trim(Yii::app()->request->getParam('code',''));
        $currPage = trim(Yii::app()->request->getParam('current_page','1'));
        $endPage = trim(Yii::app()->request->getParam('end_page',''));
        $sku_test = trim(Yii::app()->request->getParam('sku',''));
        $lanCodeArr = array('fr'=>'French','de'=>'German','es'=>'Spain');//需要翻译的语言
        if ($code == '' || !isset($lanCodeArr[$code])) {
            die('code is emtpy');
        }

        $tableName = 'ueb_product_description';
        $nowTime = date('Y-m-d H:i:s');
        $market_product = EbayProduct::model();

        $targetLanguageCode = $code;
        $lanCode = $lanCodeArr[$code];
        $obj = $market_product->dbConnection->createCommand()
                    ->select('count(*) as total')
                    ->from($tableName)
                    ->where(" language_code = '{$lanCode}' ")
                    ->andWhere(" status = 0 and (title='' or description='') ");
        $sku_test != '' && $obj->andWhere(" sku = '{$sku_test}' ");
        $res = $obj->queryRow();
        $total = (int)$res['total'];    
        if ($total == 0) {
            die('no data need to translate');
        }
        $pageSize = 1000;
        $pageCount = ceil($total/$pageSize);
        echo 'total:'.$total.' pageCount:'.$pageCount."<br>";
        if ($currPage > $pageCount || $currPage < 1 ) {
            die('$currPage is invalid');
        }
        if ($endPage != '' && $endPage < 1 ) {
            die('$endPage is invalid');
        }      
        if ($endPage == '' || $endPage > $pageCount) {
            $endPage = $pageCount;
        }
        for($page = $currPage; $page<= $endPage; $page++) {
            $offset = ($page - 1) * $pageSize;
            $obj = $market_product->dbConnection->createCommand()
                        ->select('id,sku')
                        ->from($tableName)
                        ->where(" language_code = '{$lanCode}' ")
                        ->andWhere(" status = 0 and (title='' or description='') ")
                        ->order(' sku asc ')
                        ->limit($pageSize, $offset);
            $sku_test != '' && $obj->andWhere(" sku = '{$sku_test}' ");                    
            $res = $obj->queryAll();
            if (empty($res)) {
                break;
            }
            foreach ($res as $v) {
                try {
                    $obj = $market_product->dbConnection->createCommand()
                            ->select('id,sku,title,description,included')
                            ->from($tableName)
                            ->where(" sku = :sku ", array('sku'=>$v['sku']))
                            ->andWhere(" language_code = 'english' ")
                            ->limit(1);
                    $rs = $obj->queryRow();
                    if (empty($rs)) {
                        continue;
                    }
                    $sku = $rs['sku'];
                    $title_en = trim($rs['title']);
                    $description_en = preg_replace('/<img[^>]+\/?>/s','', trim($rs['description']) );//去掉img
                    $included_en = trim($rs['included']);

                    $row = array();
                    $row['sku'] = $sku;
                    $row['language_code'] = $lanCode;

                    //title
                    if ($title_en != '') {
                        $title_code = $this->getTranslateResponse($title_en,$targetLanguageCode);
                    } else {
                        $title_code = '';
                    }
                    //echo 'title_'.$lanCode.': '.$title_code."<br>";

                    //description
                    if ($description_en != '') {
                        $description_code = $this->getTranslateResponse($description_en,$targetLanguageCode);
                    } else {
                        $description_code = '';
                    }                     
                    //echo 'description_'.$lanCode.': '.$description_code."\r\n<br>";

                    //included
                    if ($included_en != '') {
                        $included_code = $this->getTranslateResponse($included_en,$targetLanguageCode);
                    } else {
                        $included_code = '';
                    }
                    //echo 'included_'.$lanCode.': '.$included_en."\r\n<br>";
                    if ($title_code === false || $description_code === false || $included_code === false) {
                        throw new Exception("dailyLimitExceeded");
                    }   

                    $row['title'] = $title_code;
                    $row['description'] = $description_code;
                    $row['included'] = $included_code;
                    $row['status'] = $title_code != '' && $description_code != '' ? 2 : 0;
                    if ($row['status'] == 0) {
                        $status_en = 0;
                    }
                    MHelper::printvar($row,false);
                    $isOk = $this->saveProductDescription($sku, $lanCode, $row);                        
                } catch (Exception $e) {
                    echo $e->getMessage()."<br>";
                }
            }
        }
        die('finish');
    }      

}