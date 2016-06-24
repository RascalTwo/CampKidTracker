<?php

function generate_id($path){
    $ids = load_data($path);
    $generated_id = rand(10000, 99999);
    while (in_array($generated_id, $ids)){
        $generated_id = rand(10000, 99999);
    }
    $ids[] = $generated_id;
    save_data($ids, $path);
    return $generated_id;
}

function load_data($path){
    if (file_exists($path)){
        $data = unserialize(file_get_contents($path));
    }
    else{
        $data = [];
    }
    return $data;
}

function save_data($data, $path){
    file_put_contents($path, serialize($data));
}

function get_objects($ids, $data){
    return get_objects_by($ids, "id", $data);
}

function get_objects_by($property_values, $property, $data){
    if (!is_array($data)){
        $data = load_data($data);
    }
    $returning_objects = [];
    foreach ($data as $object){
        foreach ($property_values as $key => $value){
            if ($object[$property] === $value){
                $returning_objects[$key] = $object;
            }
        }
    }
    ksort($returning_objects);
    return $returning_objects;
}

function any_item_in_array($items, $in){
    foreach ($items as $item){
        if (in_array($item, $in)){
            return true;
        }
    }
    return false;
}

function add_objects($objects, $path, $data=NULL){
    if (!is_array($data)){
        $data = load_data($path);
    }
    foreach ($objects as $object){
        $data[] = $object;
    }
    save_data($data, $path);
}

function remove_objects($objects, $path, $data=NULL){
    if (!is_array($data)){
        $data = load_data($path);
    }
    foreach ($data as $data_key => $_){
        foreach ($objects as $object_key => $value){
            if ($data[$data_key] -> id !== $objects[$object_key] -> id){
                continue;
            }
            unset($data[$data_key]);
        }
    }
    save_data($data, $path);
}

function objects_to_ids($objects){
    $ids = [];
    foreach ($objects as $object){
        $ids[] = $object -> id;
    }
    return $ids;
}

function swap_array_values(&$array, $first, $second){
    $temp = $array[$first];
    $array[$first] = $array[$second];
    $array[$second] = $temp;
}

function comma_split_to_array($string){
    $array = [];
    foreach (explode(",", $string) as $item){
        if ($item == NULL || $item == ''){
            continue;
        }
        $array[] = $item;
    }
    return $array;
}

function array_to_comma_split($array){
    return implode(",", $array) . ",";
}

function append_to_comma_array($comma_array, $new_value){
    $array = comma_split_to_array($comma_array);
    if (in_array($new_value, $array)){
        return $comma_array;
    }
    $array[] = $new_value;
    return array_to_comma_split($array);
}

?>