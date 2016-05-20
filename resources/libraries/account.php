<?php

class Account{
    public $id;
    public $display_name;
    private $username;
    private $password;
    private $salt;

    private $access_level;

    public $preferences;

    public $account_creation_time;
    public $last_online;

    public function __construct($id, $display_name, $username, $password, $access_level){
        $this -> display_name = $display_name;
        $this -> username = $username;

        $this -> salt = openssl_random_pseudo_bytes(256);

        $this -> password = hash("sha256", $this -> salt . $password);

        $this -> access_level = $access_level;

        $this -> preferences = [
            "columns" => [
                [
                    "text" => "<th>Full Name</th>",
                    "name" => "full_name",
                    "enabled" => true,
                    "position" => 1
                ],
                [
                    "text" => "<th>Parents</th>",
                    "name" => "parents",
                    "enabled" => true,
                    "position" => 2
                ],
                [
                    "text" => "<th>Status</th>",
                    "name" => "status",
                    "enabled" => true,
                    "position" => 3
                ],
                [
                    "text" => "<th>Options</th>",
                    "name" => "options",
                    "enabled" => true,
                    "position" => 4
                ],
                [
                    "text" => "<th>Changed</th>",
                    "name" => "changed",
                    "enabled" => true,
                    "position" => 5
                ],
                [
                    "text" => "<th>First Name</th>",
                    "name" => "first_name",
                    "enabled" => false,
                    "position" => 6
                ],
                [
                    "text" => "<th>Last Name</th>",
                    "name" => "last_name",
                    "enabled" => false,
                    "position" => 7
                ],
                [
                    "text" => "<th>ID</th>",
                    "name" => "id",
                    "enabled" => false,
                    "position" => 8
                ]
            ],
            "theme" => "full"
        ];

        $this -> account_creation_time = time();
    }

    public function get_username(){
        return $this -> username;
    }

    public function username_match($username){
        return ($this -> username === strtolower($username));
    }

    public function password_match($password){
        return ($this -> password === hash("sha256", $this -> salt . $password));
    }

    public function has_access($access){
        return ($this -> access_level === $access);
    }

    public function has_atleast_access($access){
        switch ($access) {
            case "admin":
                return ($this -> access_level === $access);
                break;

            case "mod":
                return ($this -> access_level === "mod" || $this -> access_level === "admin");
                break;

            case "user":
                return true;
                break;
        }
    }

    public function login(){
        $this -> last_online = time();
    }

    private function parse_raw_columns($raw_columns){
        $raw_columns = explode(";", $raw_columns);
        $columns = [];
        foreach ($raw_columns as $raw_column){
            $key = explode(":", $raw_column)[0];
            $enabled = explode(":", explode(",", $raw_column)[0])[1];
            $position = explode(",", $raw_column)[1];
            $columns[$key] = ["enabled" => $enabled, "position" => $position];
        }
        return $columns;
    }

    public function update_preferences($columns, $theme){
        $columns = $this -> parse_raw_columns($columns);
        $this -> preferences["theme"] = $theme;
        foreach ($columns as $new_col_key => $new_col) {
            foreach ($this -> preferences["columns"] as $pref_col_key => $pref_col){
                if ($pref_col["name"] === $new_col_key){
                    $this -> preferences["columns"][$pref_col_key]["enabled"] = $new_col["enabled"] === "true";
                    $this -> preferences["columns"][$pref_col_key]["position"] = $new_col["position"];
                }
            }
        }
    }

    public function sort_columns(){
        function compare_position($a, $b) {
          return $a["position"] - $b["position"];
        }

        usort($this -> preferences["columns"], "compare_position");
    }

    public function table_header($editing){
        $this -> sort_columns();
        $response = "<tr>";

        foreach ($this -> preferences["columns"] as $header){
            if (!$editing && $header["enabled"]){
                $response .= $header["text"];
            }
            if ($editing){
                $response .= $header["text"];
            }
        }

        $response .= "</tr>";

        if ($editing){
            $response .= "<tr>";
            foreach ($this -> preferences["columns"] as $header){
                $response .= "<td>";
                $response .= "Position: <input id='" . $header["name"] . "-position' type='text' value='" . $header["position"] . "' size='1'>";
                $response .= "<br>";
                $response .= "<label for='" . $header["name"] . "'>Enabled: </label>";
                $response .= "<input id='" . $header["name"] . "-enabled' type='checkbox' " . (($header["enabled"]) ? "checked" : "") . "></input>";
                $response .= "</td>";
            }
            $response .= "</tr>";
        }
        return $response;
    }

    public function to_table_row(){
        $response = "<tr id='" . $this -> id . "'>";
        $response .= "<td>";
        $response .= $this -> display_name;
        $response .= "</td>";
        $response .= "<td>";
        $response .= $this -> username;
        $response .= "</td>";
        $response .= "<td>";
        $response .= ucfirst($this -> access_level);
        $response .= "</td>";
        $response .= "<td>";
        if ($this -> last_online === NULL){
            $response .= "Never";
        }
        else{
            $response .= date("m-d-y g:i A", $this -> last_online);
        }
        $response .= "</td>";
        $response .= "<td>";
        $response .= "<input type='button' value='Delete Account' id='" . $this -> id . "-delete_account'>";
        $response .= "</td>";
        $response .= "</tr>";
        return $response;
    }
}

function load_accounts($path){
    if (file_exists($path)){
        return unserialize(file_get_contents($path));
    }
    return [];
}

function save_accounts($accounts, $path){
    file_put_contents($path, serialize($accounts));
}

function get_account($id, $path){
    $accounts = load_accounts($path);
    foreach ($accounts as $account){
        if ($account -> id == $id){
            return $account;
        }
    }
}

?>