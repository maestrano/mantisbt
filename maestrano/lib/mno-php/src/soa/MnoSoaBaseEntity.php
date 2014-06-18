<?php

/**
 * Mno Entity Interface
 */
class MnoSoaBaseEntity extends MnoSoaHelper
{                   
    const STATUS_ERROR = 1;
    const STATUS_NEW_ID = 2;
    const STATUS_EXISTING_ID = 3;
    const STATUS_DELETED_ID = 4;
    
    protected $_local_entity;
    protected $_local_entity_name;
    protected $_mno_entity_name;
    
    protected $_create_rest_entity_name;
    protected $_create_http_operation;
    protected $_update_rest_entity_name;
    protected $_update_http_operation;
    protected $_receive_rest_entity_name;
    protected $_receive_http_operation;
    protected $_delete_rest_entity_name;
    protected $_delete_http_operation;
    
    protected $_enable_delete_notifications=false;
    
    
    public function __construct() {
        global $conn;
        MnoSoaDB::initialize($conn);
        MnoSoaLogger::initialize();
    }
    
    /**************************************************************************
     *                         ABSTRACT METHODS                               *
     **************************************************************************/
    
    /**
    * Build a Maestrano entity message
    * 
    * @return MaestranoEntity the maestrano entity json object
    */
    protected function build() 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    protected function persist($mno_entity) 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public function getLocalEntityIdentifier() 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public function getLocalEntityByLocalIdentifier($local_id)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public function createLocalEntity()
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public function getUpdates($timestamp) 
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public function process_notification($notification)
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    /**************************************************************************
     *                       COMMON INHERITED METHODS                         *
     **************************************************************************/
    
    public function getLocalEntityName()
    {
        return $this->_local_entity_name;
    }
    
    protected function getMnoEntityName()
    {
        return $this->_mno_entity_name;
    }
    
    public function pushId()
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    public function pullId()
    {
        throw new Exception('Function '. __FUNCTION__ . ' must be overriden in Entity class!');
    }
    
    /**************************************************************************
     *                            REST API METHODS                            *
     **************************************************************************/
    
    public function send($local_entity) 
    {
        MnoSoaLogger::debug("start");

        $this->_local_entity = $local_entity;
        
        $message = $this->build();
        $message = json_encode($message);
        $mno_had_no_id = empty($this->_id);
        
        if ($mno_had_no_id) {
            MnoSoaLogger::debug("$this->_id = ".$this->_id);
            $response = $this->callMaestrano($this->_create_http_operation, $this->_create_rest_entity_name, $message);
        } else {
            $response = $this->callMaestrano($this->_update_http_operation, $this->_update_rest_entity_name . '/' . $this->_id, $message);
        }
        
        if (empty($response)) {
            return false;
        }
	
        $local_entity_id = $this->getLocalEntityIdentifier();
        $local_entity_now_has_id = !empty($local_entity_id);
        
        $mno_response_id = $response->id;
        $mno_response_has_id = !empty($mno_response_id);
	
        if ($mno_had_no_id && $local_entity_now_has_id && $mno_response_has_id) {
            MnoSoaDB::addIdMapEntry($local_entity_id, $this->getLocalEntityName(), $mno_response_id, $this->getMnoEntityName());
        }
        
        MnoSoaLogger::debug("end");
        return true;
    }
    
    public function receive($mno_entity) 
    {
        return $this->persist($mno_entity);
    }
    
    public function receiveNotification($notification) {
        $mno_entity = $this->callMaestrano($this->_receive_http_operation, $this->_receive_rest_entity_name . '/' . $notification->id);

        if (empty($mno_entity)) { return false; }
        
        return $this->receive($mno_entity);
    }
    
    public function sendDeleteNotification($local_id) 
    {
        MnoSoaLogger::debug("start local_id = " . $local_id);
        $mno_id = MnoSoaDB::getMnoIdByLocalId($local_id, $this->getLocalEntityName(), $this->getMnoEntityName());
	
        if (MnoSoaDB::isValidIdentifier($mno_id)) {
            MnoSoaLogger::debug("corresponding mno_id = " . $mno_id->_id);
            
            if ($this->_enable_delete_notifications) {
                $response = $this->callMaestrano($this->_delete_http_operation, $this->_delete_rest_entity_name . '/' . $mno_id->_id);
                if (empty($response)) { 
                    return false; 
                }
            }
            
            MnoSoaDB::deleteIdMapEntry($local_id, $this->getLocalEntityName());
            MnoSoaLogger::debug("after deleting ID entry");
        }
        
        return true;
    }
    
    /**
     * Send/retrieve data from Maestrano integration service
     *
     * @param HTTPOperation {"POST", "PUT", "GET", "DELETE"}
     * @param String EntityName
     * @param JSON Request payload
     * @return JSON Response payload
     */
    protected function callMaestrano($operation, $entity, $msg='')
    {            
      MnoSoaLogger::debug("start");
      $maestrano = MaestranoService::getInstance();
      $url = $maestrano->getSoaUrl();
      $curl = curl_init($url . $entity);
      MnoSoaLogger::debug("path = " . $url . $entity);
      MnoSoaLogger::debug("maestrano msg = ".$msg);
      curl_setopt($curl, CURLOPT_HEADER, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
      curl_setopt($curl, CURLOPT_TIMEOUT, '60');
      
      MnoSoaLogger::debug("before switch");
      
      switch ($operation) {
	  case "POST":
	      curl_setopt($curl, CURLOPT_POST, true);
	      curl_setopt($curl, CURLOPT_POSTFIELDS, $msg);
	      break;
	  case "PUT":
	      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
	      curl_setopt($curl, CURLOPT_POSTFIELDS, $msg);
	      break;
	  case "GET":
	      break;
          case "DELETE":
              curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
              break;
      }

      MnoSoaLogger::debug("before curl_exec");
      $response = trim(curl_exec($curl));
      MnoSoaLogger::debug("after curl_exec");
      MnoSoaLogger::debug("response = ". $response);
      $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      
      MnoSoaLogger::debug("status = ". $status);
      
      if ( $status != 200 ) {
            MnoSoaLogger::error("Error: call to URL $url failed with status $status, response $response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl), 0);
            curl_close($curl);
            return null;
      }

      curl_close($curl);

      $response = json_decode($response, false);
      
      return $response;
    }
}
?>