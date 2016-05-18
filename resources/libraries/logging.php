<?php
class Logger{
    private $path;

    public function __construct($path){
        $this -> path = $path;
    }

    public function log($event){
        $history = $this -> get_history();
        array_push($history, $event);
        $this -> save_log_file($history);
    }

    public function get_history(){
        $today_log_path = $this -> path . date("m-d-Y") . ".log";
        if (file_exists($today_log_path)){
            return unserialize(file_get_contents($today_log_path));
        }
        return [];
    }

    private function save_log_file($history){
        file_put_contents($this -> path . date("m-d-Y") . ".log", serialize($history));
    }
}

class Event{
    private $ip_address;
    private $account;
    private $when;

    public function __construct($ip_address, $account){
        $this -> ip_address = $ip_address;
        $this -> account = $account;
        $this -> when = time();
    }

    protected function account_un(){
        if (is_object($this -> account)){
            return $this -> account -> get_username();
        }
        return $this -> account;
    }

    protected function str_prefix(){
        return "[" . date("D M d G:i:s Y", $this -> when) . "] " . $this -> ip_address . " " . $this -> account_un() . ": ";
    }
}

class Account_Login_Event extends Event{
    private $successful;

    public function __construct($ip_address, $account, $successful){
        Event::__construct($ip_address, $account);
        $this -> successful = $successful;
    }

    public function __toString(){
        return $this -> str_prefix() . (($this -> successful) ? "Successfull" : "Unsuccessfull") . " Logged in.";
    }
}

class Kid_Create_Event extends Event{
    private $kid;

    public function __construct($ip_address, $account, $kid){
        Event::__construct($ip_address, $account);
        $this -> kid = $kid;
    }

    public function __toString(){
        return $this -> str_prefix() . "Added Kid '" . $this -> kid -> full_name() . "' with an ID of " . $this -> kid -> id . ".";
    }
}

class Kid_Status_Change_Event extends Event{
    private $kid_id;
    private $from_status;
    private $to_status;

    public function __construct($ip_address, $account, $kid, $from_status){
        Event::__construct($ip_address, $account);
        $this -> kid = $kid;
        $this -> from_status = $from_status;
        $this -> to_status = $kid -> status;
    }

    public function __toString(){
        return $this -> str_prefix() . "Changed the status of '" . $this -> kid -> full_name() . "' #" . $this -> kid -> id . " from '" . $this -> from_status . "' to '" . $this -> to_status . "'.";
    }
}

class Kid_Info_Change_Event extends Event{
    private $previous_state;

    public function __construct($ip_address, $account, $previous_state){
        Event::__construct($ip_address, $account);
        $this -> previous_state = $previous_state;
    }

    public function __toString(){
        return $this -> str_prefix() . "Edited '" . $this -> previous_state -> full_name() . "' #" . $this -> previous_state -> id . ".";
    }
}

class Account_Create_Event extends Event{
    private $created_account;

    public function __construct($ip_address, $creator_account, $created_account){
        Event::__construct($ip_address, $creator_account);
        $this -> created_account = $created_account;
    }

    public function __toString(){
        return $this -> str_prefix() . "Created account #" . $this -> created_account -> id . " with a username of '" . $this -> created_account -> get_username() . "'.";
    }
}

class Account_Edit_Event extends Event{
    private $previous_state;

    public function __construct($ip_address, $account, $previous_state){
        Event::__construct($ip_address, $account);
        $this -> previous_state = $previous_state;
    }

    public function __toString(){
        return $this -> str_prefix() . "Edited '" . $this -> previous_state -> get_username() . "' #" . $this -> previous_state -> id . ".";
    }
}

?>