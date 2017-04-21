<?php
/**
 * @desc ebay交互类
 */
class EbaySession {

    private $requestToken;
    
    private $devID;
    
    private $appID;
    
    private $certID;
    
    private $serverUrl;
    
    private $compatLevel;
    
    private $siteID;
    
    private $verb;
    
    private $calltype;
    
    private $boundary;
    
    public $siteIdArr = array(
        0   => 'EBAY-US',
        2   => 'EBAY-ENCA',
        3   => 'EBAY-GB',
        15  => 'EBAY-AU',
        16  => 'EBAY-AT',
        23  => 'EBAY-FRBE',
        71  => 'EBAY-FR',
        77  => 'EBAY-DE',
        100 => 'EBAY-MOTOR',
        101 => 'EBAY-IT',
        123 => 'EBAY-NLBE',
        146 => 'EBAY-NL',
        186 => 'EBAY-ES',
        193 => 'EBAY-CH',
        201 => 'EBAY-HK',
        203 => 'EBAY-IN',
        205 => 'EBAY-IE',
        207 => 'EBAY-MY',
        210 => 'EBAY-FRCA',
        211 => 'EBAY-PH',
        212 => 'EBAY-PL',
        216 => 'EBAY-SG'
    );

    /** 	
     * __construct
      Constructor to make a new instance of eBaySession with the details needed to make a call
      Input:	$userRequestToken - the authentication token fir the user making the call
      $developerID - Developer key obtained when registered at http://developer.ebay.com
      $applicationID - Application key obtained when registered at http://developer.ebay.com
      $certificateID - Certificate key obtained when registered at http://developer.ebay.com
      $useTestServer - Boolean, if true then Sandbox server is used, otherwise production server is used
      $compatabilityLevel - API version this is compatable with
      $siteToUseID - the Id of the eBay site to associate the call iwht (0 = US, 2 = Canada, 3 = UK, ...)
      $callName  - The name of the call being made (e.g. 'GeteBayOfficialTime')
      Output:	Response string returned by the server
     */
    public function __construct($userRequestToken, $developerID, $applicationID, $certificateID, $serverUrl, $compatabilityLevel, $siteToUseID, $callName, $calltype = 'trading', $boundary = '') {

        $this->requestToken     = $userRequestToken;
        $this->devID            = $developerID;
        $this->appID            = $applicationID;
        $this->certID           = $certificateID;
        $this->compatLevel      = $compatabilityLevel;
        $this->siteID           = $siteToUseID;
        $this->verb             = $callName;
        $this->serverUrl        = $serverUrl;
        $this->calltype         = $calltype;
        $this->boundary         = $boundary;
    }

    /**
     * sendHttpRequest
     * Sends a HTTP request to the server for this session
     * @param type $requestBody
     * @param type $xml
     * @return type
     */
    public function sendHttpRequest($requestBody, $xml = 0) {
        //build eBay headers using variables passed via constructor
        $headers = $this->buildEbayHeaders();

        if ($this->calltype == 'resolution' || $this->calltype == 'merchandising' ||
                $this->calltype == 'finding') {
            //initialise a CURL session
            $connection = curl_init($this->serverUrl);
        } else {
            //initialise a CURL session
            $connection = curl_init();

            //set the server we are using (could be Sandbox or Production server)
            curl_setopt($connection, CURLOPT_URL, $this->serverUrl);
        }


        //stop CURL from verifying the peer's certificate
        curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);

        //set the headers using the array of headers
        curl_setopt($connection, CURLOPT_HTTPHEADER, $headers);

        //set method as POST
        curl_setopt($connection, CURLOPT_POST, 1);

        //set the XML body of the request
        curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);

        //set it to return the transfer as a string from curl_exec
        curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);

        //set connection timeout as 120 second
        curl_setopt($connection, CURLOPT_CONNECTTIMEOUT, 120);

        //Send the Request
        $response = curl_exec($connection);

        //close the connection
        curl_close($connection);       
        
        //return the response
        $result = null;
        if (! empty($response)) {
        	try {
        		$result = simplexml_load_string($response);
        	} catch (Exception $e) {
        		
        		return null;
        		
        	}
            
        }              
        
        if ($xml) {
            return $response;
        } else {
            return $result;
        }
    }
   
    /**
     * buildEbayHeaders
     * Generates an array of string to be used as the headers for the HTTP request to eBay
     * @return string $headers String Array of Headers applicable for this call
     */
    private function buildEbayHeaders() {
        if ($this->calltype == 'resolution') {
            $headers = array(
                'X-EBAY-SOA-SERVICE-NAME: ResolutionCaseManagementService',
                'X-EBAY-SOA-OPERATION-NAME: ' . $this->verb,
                'X-EBAY-SOA-SERVICE-VERSION: 1.1.0',
                'X-EBAY-SOA-SECURITY-TOKEN: ' . $this->requestToken,
                'X-EBAY-SOA-REQUEST-DATA-FORMAT: XML'
            );
        } elseif ($this->calltype == 'merchandising') {
            $headers = array(
                'X-EBAY-SOA-SERVICE-NAME: MerchandisingService',
                'X-EBAY-SOA-OPERATION-NAME: ' . $this->verb,
                'X-EBAY-SOA-SERVICE-VERSION: 1.1.0',
                'X-EBAY-SOA-GLOBAL-ID: ' . $this->siteIdArr[$this->siteID],
                'EBAY-SOA-CONSUMER-ID: ' . $this->appID,
                'X-EBAY-SOA-REQUEST-DATA-FORMAT: XML',
            );
        } elseif ($this->calltype == 'finding') {
            $headers = array(
                'X-EBAY-SOA-SERVICE-NAME: FindingService',
                'X-EBAY-SOA-OPERATION-NAME: ' . $this->verb,
                'X-EBAY-SOA-SERVICE-VERSION: 1.12.0',
                'X-EBAY-SOA-GLOBAL-ID: ' . $this->siteIdArr[$this->siteID],
                'X-EBAY-SOA-SECURITY-APPNAME: ' . $this->appID,
                'X-EBAY-SOA-RESPONSE-DATA-FORMAT: XML',
            );
        } else {            
            $headers = array(
                //Regulates versioning of the XML interface for the API
                'X-EBAY-API-COMPATIBILITY-LEVEL: ' . $this->compatLevel,
                //set the keys
                'X-EBAY-API-DEV-NAME: ' . $this->devID,
                'X-EBAY-API-APP-NAME: ' . $this->appID,
                'X-EBAY-API-CERT-NAME: ' . $this->certID,
                //the name of the call we are requesting
                'X-EBAY-API-CALL-NAME: ' . $this->verb,
                //SiteID must also be set in the Request's XML
                //SiteID = 0  (US) - UK = 3, Canada = 2, Australia = 15, ....
                //SiteID Indicates the eBay site to associate the call with
                'X-EBAY-API-SITEID: ' . $this->siteID,
            );
            if ($this->boundary) {
                array_push($headers, 'Content-Type: multipart/form-data; boundary=' . $this->boundary);
            }
        }
        return $headers;
    }

}

?>