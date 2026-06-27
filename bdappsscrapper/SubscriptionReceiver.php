<?php


namespace App\Classes;
use Exception;

class SubscriptionReceiver{

    private $frequency; 
    private $status;
    private $applicationId;
    private $subscriberId;
    private $timeStamp;
    
   
	
    public function __construct(){
        $array = json_decode(file_get_contents('php://input'), true);
       // $this->thejson = json_decode(file_get_contents('php://input'), true);
        $this->frequency = $array['frequency'];
        $this->status = $array['status'];
        $this->subscriberId = $array['subscriberId'];
        $this->applicationId = $array['applicationId'];
        $this->timeStamp = $array['timeStamp'];
        

        
    }



    public function getFrequency(){
        return $this->frequency;
    }
	
	public function getStatus(){
		return $this->status;
	}
    
	
    public function getsubscriberId(){
        return $this->subscriberId;
    }

    public function getApplicationId(){
        return $this->applicationId;
    }


    public function getTimestamp(){
        return $this->timeStamp;
    }
	
   

    

}