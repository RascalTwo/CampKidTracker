<?php

class Kid{
    public $id;
    public $first_name;
    public $last_name;
    public $parents;
    public $status;
    public $modification_time;
    public $status_update_time;
    public $visible;

    public function __construct($id, $first_name, $last_name, $parents, $status){
        $this -> id = $id;
        $this -> first_name = $first_name;
        $this -> last_name = $last_name;
        $this -> parents = $this -> parse_comma_split($parents);
        $this -> status = $status;
        $this -> modification_time = time();
        $this -> status_update_time = time();
        $this -> visible = true;
    }

    private function parse_comma_split($string){
        if (strpos($string, ",") !== false){
            $parts = explode(",", $string);
            foreach ($parts as $key => $part){
                $parts[$key] = trim($part);
            }
            return $parts;
        }
        return [$string];
    }

    public function update_value($name, $value){
        switch ($name){
            case "status":
                $this -> status = $value;
                $this -> status_update_time = time();
                break;

            case "parents":
                $this -> parents = $this -> parse_comma_split($value);
                break;

            case "full_name":
                $full_name = $this -> parse_comma_split($value);
                if (count($full_name) === 1){
                    $this -> last_name = $full_name[0];
                    $this -> first_name = "";
                    break;
                }
                $this -> first_name = $full_name[1];
                break;

            case "first_name":
                $this -> first_name = $value;
                break;

            case "last_name":
                $this -> last_name = $value;
                break;
        }
        $this -> modification_time = time();
    }

    public function get_cell($name, $edit, $account){
        $response = "<td>";
        switch ($name) {
            case "first_name":
                if ($edit){
                    $response .= "<input size='10' type='text' id='" . $this -> id . "-first_name' value='" . $this -> first_name . "'>";
                    continue;
                }
                $response .= $this -> first_name;
                break;

            case "last_name":
                if ($edit){
                    $response .= "<input size='10' type='text' id='" . $this -> id . "-last_name' value='" . $this -> last_name . "'>";
                    continue;
                }
                $response .= $this -> last_name;
                break;

            case "full_name":
                if ($edit){
                    $response .= "<input size='15' type='text' id='" . $this -> id . "-full_name' value='" . $this -> last_name . ", " . $this -> first_name . "'>";
                    continue;
                }
                $response .= $this -> last_name . ", " . $this -> first_name;
                break;

            case "parents":
                if ($edit){
                    $response .= "<input size='15' type='text' id='" . $this -> id . "-parents' value='" . implode(",", $this -> parents) . "'>";
                    continue;
                }
                $response .= implode("<br>", $this -> parents);
                break;

            case "status":
                $name = "name='" . $this -> id . "-status'";

                $response .= "<label for='" . $this -> id . "-in-status'>In</label>";
                $response .= "<input " . $name . " type='radio' id='" . $this -> id . "-in-status' value='in' " . (($this -> status === "in") ? "checked" : "") . "><br>";

                $response .= "<label for='" . $this -> id . "-out-status'>Out</label>";
                $response .= "<input " . $name . " type='radio' id='" . $this -> id . "-out-status' value='out' " . (($this -> status === "out") ? "checked" : "") . "><br>";

                $response .= "<label for=" . $this -> id . "-transit-status'>Transit</label>";
                $response .= "<input " . $name . " type='radio' id='" . $this -> id . "-transit-status' value='transit' " . (($this -> status === "transit") ? "checked" : "") . "><br>";
                break;

            case "controls":
                if ($account -> has_access("user")){
                    continue;
                }
                elseif ($account -> has_access("admin")){
                    $response .= "<input id='" . $this -> id . "-delete' type='button' value='Delete'>";
                    $response .= "<br>";
                }
                if ($edit){
                    $response .= "<input id='" . $this -> id . "-confirm_edit' type='button' value='Confirm Edit'>";
                    continue;
                }
                $response .= "<input id='" . $this -> id . "-edit' type='button' value='Edit'>";
                break;

            case "id":
                $response .= $this -> id;
                break;
        }
        return $response . "</td>";
    }

    public function to_table_row($edit, $account){
        $response = "<tr id='" . $this -> id . "'>";
        foreach ($account -> preferences["columns"] as $column){
            if ($column["enabled"]){
                $response .= $this -> get_cell($column["name"], $edit, $account);
            }
        }
        $response .= "</tr>";
        return $response;
    }
}

function load_kids($path){
    if (file_exists($path)){
        return unserialize(file_get_contents($path));
    }
    return [];
}

function save_kids($kids, $path){
    file_put_contents($path, serialize($kids));
}

function get_kid($id, $path){
    $kids = load_kids($path);
    foreach ($kids as $kid){
        if ($kid -> id == $id){
            return $kid;
        }
    }
}

?>