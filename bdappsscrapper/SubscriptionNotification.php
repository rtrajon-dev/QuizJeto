<?php


namespace App\Classes;
use Exception;

class SubscriptionNotification{

    private $applicationId; 
    private $frequency;
    private $requestId;
    private $status;
    private $subscriberId;
    private $timeStamp;
	
    public function __construct(){
        $array = json_decode(file_get_contents('php://input'), true);
       // $this->thejson = json_decode(file_get_contents('php://input'), true);
        $this->applicationId = $array['applicationId'];
        $this->frequency = $array['frequency'];
        $this->requestId = $array['requestId'];
        $this->status = $array['status'];
        $this->subscriberId = $array['subscriberId'];
        $this->timeStamp = $array['timeStamp'];
        

        
            $responses = array("statusCode" => "S1000", "statusDetail" => "Success");
        
    }


	
	
    public function getRequestID(){
        return $this->requestId;
    }

    public function getApplicationId(){
        return $this->applicationId;
    }
    
    public function getStatus()
    {
         return $this->status;
    }
    
     public function getSubscriberId()
    {
         return $this->subscriberId;
    }
    
    public function getTimeStamp()
    {
        return $this->timeStamp;
    }
    
    

   

}