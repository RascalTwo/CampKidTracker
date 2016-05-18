<?php

class Account{
    private $id;
    private $display_name;
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
                    "enabled" => false,
                    "sort_by" => false,
                    "position" => 9
                ],
                [
                    "text" => "<th>First Name</th>",
                    "name" => "first_name",
                    "enabled" => true,
                    "sort_by" => false,
                    "position" => 1
                ],
                [
                    "text" => "<th>Last Name</th>",
                    "name" => "last_name",
                    "enabled" => true,
                    "sort_by" => true,
                    "position" => 9
                ],
                [
                    "text" => "<th>Parents</th>",
                    "name" => "parents",
                    "enabled" => false,
                    "sort_by" => false,
                    "position" => 3
                ],
                [
                    "text" => "<th>Status</th>",
                    "name" => "status",
                    "enabled" => true,
                    "sort_by" => false,
                    "position" => 2
                ],
                [
                    "text" => "<th>ID</th>",
                    "name" => "id",
                    "enabled" => false,
                    "sort_by" => false,
                    "position" => 9
                ],
                [
                    "text" => "<th>Controls</th>",
                    "name" => "controls",
                    "enabled" => true,
                    "sort_by" => false,
                    "position" => 4
                ]
            ],
            "theme" => "full"
        ];

        $this -> account_creation_time = time();
    }

    public function get_username(){
        return $this -> username;
    }

    public function get_id(){
        return $this -> id;
    }

    public function username_match($username){
        return ($this -> username === $username);
    }

    public function password_match($password){
        return ($this -> password === hash("sha256", $this -> salt . $password));
    }

    public function has_access($access){
        return ($this -> access_level === $access);
    }

    public function login(){
        $this -> last_online = time();
    }

    public function update_preferences($columns, $theme){
        $this -> preferences["theme"] = $theme;
        foreach ($columns as $new_col_key => $new_col) {
            foreach ($this -> preferences["columns"] as $pref_col_key => $pref_col){
                if ($pref_col["name"] === $new_col_key){
                    $this -> preferences["columns"][$pref_col_key]["enabled"] = $new_col["enabled"];
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

?>