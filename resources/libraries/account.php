<?php

class Account{
    public $id;

    public $display_name;
    public $username;
    private $password;
    private $salt;
    public $access_level;

    public $columns;
    public $theme;
    public $contact;

    public $account_creation_time;
    public $last_online;

    public function __construct($id, $display_name, $username, $password, $access_level){
        $this -> id = $id;
        $this -> display_name = $display_name;
        $this -> username = $username;

        $this -> salt = openssl_random_pseudo_bytes(256);

        $this -> password = hash("sha256", $this -> salt . $password);

        $this -> access_level = $access_level;

        $this -> columns = [
            [
                "identifier" => "full_name",
                "display" => "Full Name",
                "enabled" => true,
                "position" => 1
            ],
            [
                "identifier" => "parents",
                "display" => "Parents",
                "enabled" => true,
                "position" => 2
            ],
            [
                "identifier" => "status",
                "display" => "Status",
                "enabled" => true,
                "position" => 3
            ],
            [
                "identifier" => "actions",
                "display" => "Actions",
                "enabled" => true,
                "position" => 4
            ],
            [
                "identifier" => "changed",
                "display" => "Changed",
                "enabled" => true,
                "position" => 5
            ],
            [
                "identifier" => "group",
                "display" => "Group",
                "enabled" => true,
                "position" => 6
            ],
            [
                "identifier" => "first_name",
                "display" => "First Name",
                "enabled" => false,
                "position" => 7
            ],
            [
                "identifier" => "last_name",
                "display" => "Last Name",
                "enabled" => false,
                "position" => 8
            ],
            [
                "identifier" => "id",
                "display" => "ID",
                "enabled" => false,
                "position" => 9
            ]
        ];

        $this -> theme = "full";

        $this -> contact = [
            "emails" => [],
            "phones" => []
        ];

        $this -> account_creation_time = time();
    }

    public function username_match($username){
        return ($this -> username === strtolower($username));
    }

    public function password_match($password){
        return ($this -> password === hash("sha256", $this -> salt . $password));
    }

    public function access_level_is($access){
        return ($this -> access_level === $access);
    }

    public function has_access($access){
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

    public function contact($kid_id){
        error_log(print_r("$kid_id Has had their status changed!", true));
    }

    private function parse_raw_columns($raw_columns){
        $raw_columns = explode(";", $raw_columns);
        $columns = [];
        foreach ($raw_columns as $raw_column){
            $key = explode(":", $raw_column)[0];
            $enabled = explode(":", explode(",", $raw_column)[0])[1] === "true";
            $position = explode(",", $raw_column)[1];
            $columns[$key] = ["enabled" => $enabled, "position" => $position];
        }
        return $columns;
    }

    public function update_preference($preference, $new_value, &$old_value=NULL){
        $changed = true;
        switch($preference){
            case "access_level":
                $old_value = $this -> access_level;
                if ($new_value !== $this -> access_level){
                    $this -> access_level = $new_value;
                    break;
                }
                $changed = false;
                break;

            case "display_name":
                $old_value = $this -> display_name;
                if ($new_value !== $this -> display_name){
                    $this -> display_name = $new_value;
                    break;
                }
                $changed = false;
                break;

            case "theme":
                $old_value = $this -> theme;
                if ($new_value !== $this -> theme){
                    $this -> theme = $new_value;
                    break;
                }
                $changed = false;
                break;

            case "columns":
                $old_value = $this -> columns;
                $new_columns = $this -> parse_raw_columns($new_value);
                foreach ($new_columns as $key => $_){
                    $new_columns[$key]["display"] = $this -> columns[$key]["display"];
                }

                if ($new_columns !== $this -> columns){
                    $this -> columns = $new_columns;
                    break;
                }
                $changed = false;
                break;

            case "emails":
                $old_value = $this -> contact["emails"];
                if ($new_value !== implode(",", $this -> contact["emails"])){
                    $this -> contact["emails"] = explode(",", $new_value);
                    break;
                }
                $changed = false;
                break;

            case "phones":
                $old_value = $this -> contact["phones"];
                if ($new_value !== implode(",", $this -> contact["phones"])){
                    $this -> contact["phones"] = explode(",", $new_value);
                    break;
                }
                $changed = false;
                break;
        }
        return $changed;
    }

    public function sort_columns(){
        function compare_position($a, $b) {
          return $a["position"] - $b["position"];
        }

        uasort($this -> columns, "compare_position");
    }

    public function table_header($editing){
        $this -> sort_columns();
        $response = "<tr>";

        foreach ($this -> columns as $header){
            if (!$editing && $header["enabled"]){
                $response .= "<th>" . $header["display"] . "</th>";
            }
            if ($editing){
                $response .= "<th>" . $header["display"] . "</th>";
            }
        }

        $response .= "</tr>";

        if ($editing){
            $response .= "<tr>";
            foreach ($this -> columns as $key => $header){
                $response .= "<td>";
                $response .= "Position: <input id='" . $key . "-position' type='text' value='" . $header["position"] . "' size='1'>";
                $response .= "<br>";
                $response .= "<label for='" . $key . "'>Enabled: </label>";
                $response .= "<input id='" . $key . "-enabled' type='checkbox' " . (($header["enabled"]) ? "checked" : "") . "></input>";
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

    public function json(){
        return json_encode($this);
    }
}
?>