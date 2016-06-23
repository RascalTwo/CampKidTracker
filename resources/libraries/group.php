<?php
class Group{
    public $id;
    public $name;

    public $kids;
    public $leaders;

    public function __construct($id, $name){
        $this -> id = $id;
        $this -> name = $name;

        $this -> kids = [];
        $this -> leaders = [];
    }

    public function comma_split_to_array($string){
        $array = [];
        foreach (explode(",", $string) as $item){
            if ($item == NULL || $item == ''){
                continue;
            }
            $array[] = $item;
        }
        return $array;
    }

    public function update_value($name, $value){
        $changed = true;
        switch ($name){
            case "kids":
                if ($this -> kids !== $this -> comma_split_to_array($value)){
                    $this -> kids = $this -> comma_split_to_array($value);
                    break;
                }
                $changed = false;
                break;

            case "leaders":
                if ($this -> leaders !== $this -> comma_split_to_array($value)){
                    $this -> leaders = $this -> comma_split_to_array($value);
                    break;
                }
                $changed = false;
                break;

            case "name":
                if ($this -> name !== $value){
                    $this -> name = $value;
                    break;
                }
                $changed = false;
                break;
        }
        return $changed;
    }

    public function add_kid($id){
        $this -> kids[] = $id;
    }

    public function add_leader($id){
        $this -> leaders[] = $id;
    }

    public function remove_kid($id){
        if (array_key_exists($id, $this -> kids)){
            unset($this -> kids[$id]);
        }
    }

    public function remove_leader($id){
        if (array_key_exists($id, $this -> leaders)){
            unset($this -> leaders[$id]);
        }
    }

    public function to_table_row($edit){
        $response = "<tr id='" . $this -> id . "-group'><td>";
        if ($edit){
            $response .= "<input size='10' type='text' id='" . $this -> id . "-group_name' value='" . $this -> name . "'>";
        }
        else{
            $response .= $this -> name;
        }
        $response .= "</td><td>";
        $response .= count($this -> kids);
        $response .= "</td><td>";
        $response .= count($this -> leaders);
        if (get_self() -> has_access("mod")){
            $response .= "</td><td>";
            $response .= "<input id='" . $this -> id . "-group_delete' type='button' value='Delete'><br>";
            $response .= "<input type='button' value='List Members' id='" . $this -> id . "-group_view'><br>";
            if ($edit){
                $response .= "<input type='button' value='Confirm Edit' id='" . $this -> id . "-group_confirm_edit' >";
            }
            else{
                $response .= "<input type='button' value='Edit Name' id='" . $this -> id . "-group_edit'>";
            }
        }
        $response .= "</td></tr>";
        return $response;
    }
}

function load_groups($path){
    if (file_exists($path)){
        return unserialize(file_get_contents($path));
    }
    return [];
}

function save_groups($accounts, $path){
    file_put_contents($path, serialize($accounts));
}

function get_group($id, $path){
    $groups = load_groups($path);
    foreach ($groups as $group){
        if ($group -> id == $id){
            return $group;
        }
    }
}
?>