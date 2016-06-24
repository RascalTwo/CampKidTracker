<?php

class Kid{
    public $id;
    public $first_name;
    public $last_name;
    public $status;
    public $parents;
    public $group;
    public $modification_time;
    public $status_update_time;
    public $hidden;

    public function __construct($id, $first_name, $last_name, $parents, $status){
        $this -> id = $id;
        $this -> first_name = $first_name;
        $this -> last_name = $last_name;
        $this -> status = $status;
        $this -> parents = $this -> parse_comma_split($parents);
        $this -> group;
        $this -> modification_time = time();
        $this -> status_update_time = time();
        $this -> hidden = false;
    }

    public function get_full_name($first_last=true, $seperator=', '){
        if ($first_last){
            $this -> last_name . $seperator . $this -> first_name;
        }
        else{
            $this -> first_name . $seperator . $this -> last_name;
        }
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

    public function update_preference($name, $value, &$old_value=NULL){
        $changed = true;
        switch ($name){
            case "status":
                $old_value = $this -> status;
                if ($this -> status !== $value){
                    $this -> status = $value;
                    $this -> status_update_time = time();
                    break;
                }
                $changed = false;
                break;

            case "parents":
                $old_value = $this -> parents;
                if ($this -> parents !== $this -> parse_comma_split($value)){
                    $this -> parents = $this -> parse_comma_split($value);
                    break;
                }
                $changed = false;
                break;

            case "group":
                $old_value = $this -> group;
                if ($this -> group !== $value){
                    $this -> group = $value;
                    break;
                }
                $changed = false;
                break;

            case "full_name":
                $old_value = $this -> full_name;
                if ($this -> get_full_name() !== $this -> parse_comma_split($value)){
                    $full_name = $this -> parse_comma_split($value);
                    if (count($full_name) === 1){
                        $this -> first_name = $full_name[0];
                        break;
                    }
                    $this -> last_name = $full_name[0];
                    $this -> first_name = $full_name[1];
                    break;
                }
                $changed = false;
                break;

            case "first_name":
                $old_value = $this -> first_name;
                if ($this -> first_name !== $value){
                    $this -> first_name = $value;
                    break;
                }
                $changed = false;
                break;

            case "last_name":
                $old_value = $this -> last_name;
                if ($this -> last_name !== $value){
                    $this -> last_name = $value;
                    break;
                }
                $changed = false;
                break;

            case "hidden":
                $old_value = $this -> hidden;
                if ($this -> hidden !== $value){
                    $this -> hidden = $value;
                    break;
                }
                $changed = false;
                break;
        }
        if ($name !== "status" && $name !== "hidden"){
            $this -> modification_time = time();
        }
        return $changed;
    }

    public function get_cell($name, $edit, $account, $groups){
        $response = "<td>";
        switch ($name) {
            case "first_name":
                if ($edit){
                    $response .= "<input size='10' type='text' id='" . $this -> id . "-kid_first_name' value='" . $this -> first_name . "'>";
                    continue;
                }
                $response .= $this -> first_name;
                break;

            case "last_name":
                if ($edit){
                    $response .= "<input size='10' type='text' id='" . $this -> id . "-kid_last_name' value='" . $this -> last_name . "'>";
                    continue;
                }
                $response .= $this -> last_name;
                break;

            case "full_name":
                if ($edit){
                    $response .= "<input size='15' type='text' id='" . $this -> id . "-kid_full_name' value='" . $this -> get_full_name() . "'>";
                    continue;
                }
                $response .= $this -> get_full_name();
                break;

            case "parents":
                if ($edit){
                    $response .= "<input size='15' type='text' id='" . $this -> id . "-kid_parents' value='" . implode(",", $this -> parents) . "'>";
                    continue;
                }
                $response .= implode("<br>", $this -> parents);
                break;

            case "status":
                $name = "name='" . $this -> id . "-kid_status'";

                $response .= "<label for='" . $this -> id . "-in-kid_status'>In</label>";
                $response .= "<input " . $name . " type='radio' id='" . $this -> id . "-in-kid_status' value='in' " . (($this -> status === "in") ? "checked" : "") . "><br>";

                $response .= "<label for='" . $this -> id . "-out-kid_status'>Out</label>";
                $response .= "<input " . $name . " type='radio' id='" . $this -> id . "-out-kid_status' value='out' " . (($this -> status === "out") ? "checked" : "") . "><br>";

                $response .= "<label for=" . $this -> id . "-transit-kid_status'>Transit</label>";
                $response .= "<input " . $name . " type='radio' id='" . $this -> id . "-transit-kid_status' value='transit' " . (($this -> status === "transit") ? "checked" : "") . "><br>";

                $response .= "<label for=" . $this -> id . "-parentarrived-kid_status'>Parent Arrived</label>";
                $response .= "<input " . $name . " type='radio' id='" . $this -> id . "-parentarrived-kid_status' value='parentarrived' " . (($this -> status === "parentarrived") ? "checked" : "") . "><br>";
                break;

            case "actions":
                if ($account -> access_level_is("user")){
                    continue;
                }
                elseif ($account -> has_access("admin")){
                    $response .= "<input id='" . $this -> id . "-kid_delete' type='button' value='Delete'>";
                    $response .= "<br>";
                    if ($this -> hidden){
                        $response .= "<input id='" . $this -> id . "-kid_unhide' type='button' value='Un-Hide'>";
                    }
                    else{
                        $response .= "<input id='" . $this -> id . "-kid_hide' type='button' value='Hide'>";
                    }
                }
                if ($edit){
                    $response .= "<input id='" . $this -> id . "-kid_confirm_edit' type='button' value='Confirm Edit'>";
                    continue;
                }
                $response .= "<input id='" . $this -> id . "-kid_edit' type='button' value='Edit'>";
                break;

            case "id":
                $response .= $this -> id;
                break;

            case "changed":
                $response .= "Modified: " . date("m-d-y g:i A", $this -> modification_time) . "<br>";
                $response .= "<span class='change_time' time='" . $this -> modification_time . "'></span>";
                $response .= "<br>";
                $response .= "Status Updated: " . date("m-d-y g:i A", $this -> status_update_time) . "<br>";
                $response .= "<span class='change_time' time='" . $this -> status_update_time . "'></span>";
                break;

            case "group":
                if ($edit){
                    $response .= "<select id='" . $this -> id . "-kid_group'>";
                    $response .= "<option value=''>None</option>";
                    if ($this -> group !== NULL){
                        $response .= "<option selected value='" . $this -> group . "'>" . $this -> group . "</option>";
                    }
                    foreach ($groups as $group){
                        $response .= "<option value='" . $group -> id . "'>" . $group -> name . "</option>";
                    }
                    $response .= "</select>";
                }
                else{
                    if ($this -> group !== NULL){
                        foreach ($groups as $group){
                            if ($this -> group != $group -> id){
                                continue;
                            }
                            $response .= $group -> name;
                            break;
                        }
                    }
                    else{
                        $response .= "None";
                    }
                }
                break;
        }
        return $response . "</td>";
    }

    public function to_table_row($edit, $account, $groups){
        $response = "<tr id='" . $this -> id . "'>";
        foreach ($account -> columns as $name => $column){
            if ($column["enabled"]){
                $response .= $this -> get_cell($name, $edit, $account, $groups);
            }
        }
        $response .= "</tr>";
        return $response;
    }
}
?>