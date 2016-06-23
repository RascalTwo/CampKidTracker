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

    public function to_table_row(){
        $response = "<tr id='" . $this -> id . "-group'><td>";
        $response .= $this -> name;
        $response .= "</td><td>";
        $response .= count($this -> kids);
        $response .= "</td><td>";
        $response .= count($this -> leaders);
        if (get_self() -> has_access("mod")){
            $response .= "</td><td>";
            $response .= "<input id='" . $this -> id . "-group_delete' type='button' value='Delete'><br>";
            $response .= "<input type='button' value='View/Modify' id='" . $this -> id . "-group_view'><br>";
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