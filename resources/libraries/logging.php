<?php
class Logger{
    private $path;
    public $current_filepath;

    public function __construct($path){
        $this -> path = $path;
        $this -> current_filepath = $this -> path . date("m-d-Y") . ".log";
    }

    public function log($event){
        $history = $this -> get_today();
        array_push($history, $event);
        $this -> save_log_file($history);
    }

    public function get_today(){
        $this -> current_filepath = $this -> path . date("m-d-Y") . ".log";
        if (file_exists($this -> current_filepath)){
            return unserialize(file_get_contents($this -> current_filepath));
        }
        return [];
    }

    public function get_all_history(){
        $all_history = [];
        $files = scandir($this -> path);
        foreach($files as $file){
            if (strpos($file, ".log") === false){
                continue;
            }
            $all_history = array_merge($all_history, unserialize(file_get_contents($this -> path . $file)));
        }
        return $all_history;
    }

    private function save_log_file($history){
        file_put_contents($this -> path . date("m-d-Y") . ".log", serialize($history));
    }
}

class Event{
    public $ip_address;
    public $username;
    public $time;

    public function __construct($ip_address, $username){
        $this -> ip_address = $ip_address;
        $this -> username = $username;
        $this -> time = time();
    }

    protected function str_prefix(){
        return "[" . date("D M d G:i:s Y", $this -> time) . "] " . $this -> ip_address . " " . $this -> username . ": ";
    }

    protected function row_string($when, $ip_address, $username, $action, $target){
        return "<tr><td>$when</td><td>$ip_address</td><td>$username</td><td>$action</td><td>$target</td><tr>";
    }
}

class Creation_Event extends Event{
    public $type;
    public $created_info;

    public function __construct($ip_address, $username, $type, $created_info){
        Event::__construct($ip_address, $username);
        $this -> type = $type;
        $this -> created_info = "";
        foreach ($created_info as $info_name => $value){
            $this -> created_info .= $info_name . ": " . $value . "<br>";
        }
    }

    public function __toString(){
        return $this -> str_prefix();
    }

    public function to_table_row(){
        return $this -> row_string(date("m-d-y g:i A", $this -> time), $this -> ip_address, $this -> username,  (($this -> type === "account") ? "Created " : "Added ") . ucfirst($this -> type), $this -> created_info);
    }
}

class Deletion_Event extends Event{
    public $type;
    public $deleted_info;

    public function __construct($ip_address, $username, $type, $deleted_info){
        Event::__construct($ip_address, $username);
        $this -> type = $type;
        $this -> deleted_info = "";
        foreach ($deleted_info as $info_name => $value){
            $this -> deleted_info .= $info_name . ": " . $value . "<br>";
        }
    }

    public function __toString(){
        return $this -> str_prefix();
    }

    public function to_table_row(){
        return $this -> row_string(date("m-d-y g:i A", $this -> time), $this -> ip_address, $this -> username, "Deleted " . $this -> type, $this -> deleted_info);
    }
}

class Edit_Event extends Event{
    public $type;
    public $edited_info;
    public $edited_fields;

    public function __construct($ip_address, $username, $type, $edited_info, $edited_fields){
        Event::__construct($ip_address, $username);
        $this -> type = $type;
        $this -> edited_info = "";
        foreach ($edited_info as $info_name => $value){
            $this -> edited_info .= $info_name . ": " . $value . "<br>";
        }
        $this -> edited_fields = $edited_fields;
    }

    public function __toString(){
        return $this -> str_prefix();
    }

    public function to_table_row(){
        return $this -> row_string(date("m-d-y g:i A", $this -> time), $this -> ip_address, $this -> username, "Edited " . ucfirst($this -> type), $this -> edited_info . implode(",", $this -> edited_fields));
    }
}

class Account_Login_Event extends Event{
    public $successful;

    public function __construct($ip_address, $username, $successful){
        Event::__construct($ip_address, $username);
        $this -> successful = $successful;
    }

    public function __toString(){
        return $this -> str_prefix();
    }

    public function to_table_row(){
        return $this -> row_string(date("m-d-y g:i A", $this -> time), $this -> ip_address, $this -> username, (($this -> successful) ? "Logged In" : "Attempted to Login"), "");
    }
}

class Kid_Status_Change_Event extends Event{
    private $kid_info;
    private $old_status;
    private $new_status;

    public function __construct($ip_address, $username, $kid_info, $old_status, $new_status){
        Event::__construct($ip_address, $username);
        $this -> kid_info = "";
        foreach ($kid_info as $info_name => $value){
            $this -> kid_info .= $info_name . ": " . $value . "<br>";
        }
        $this -> old_status = $old_status;
        $this -> new_status = $new_status;
    }

    public function __toString(){
        return $this -> str_prefix();
    }

    public function to_table_row(){
        return $this -> row_string(date("m-d-y g:i A", $this -> time), $this -> ip_address, $this -> username, "Changed Kid Status", "Kid:<br>" . $this -> kid_info . "<br>" . $this -> old_status . " -> " . $this -> new_status);
    }
}

class Custom_Event extends Event{
    public $action;
    public $target;

    public function __construct($ip_address, $username, $action, $target){
        Event::__construct($ip_address, $username);
        $this -> action = $action;
        $this -> target = $target;
    }

    public function __toString(){
        return $this -> str_prefix();
    }

    public function to_table_row(){
        return $this -> row_string(date("m-d-y g:i A", $this -> time), $this -> ip_address, $this -> username, $this -> action, $this -> target);
    }
}

?>