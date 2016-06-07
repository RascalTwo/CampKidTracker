<?php

function generate_id($path){
    if (file_exists($path)){
        $ids = unserialize(file_get_contents($path));
    }
    else{
        $ids = [];
    }
    $generated_id = rand(1000, 9999);
    while (in_array($generated_id, $ids)){
        $generated_id = rand(1000, 9999);
    }
    $ids[] = $generated_id;
    file_put_contents($path, serialize($ids));
    error_log(print_r("Here's an ID for you: " . $generated_id, true));
    return $generated_id;
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

function append_comma_array($comma_array, $new_value){
    $array = comma_split_to_array($comma_array);
    if (in_array($new_value, $array)){
        return $comma_array;
    }
    $array[] = $new_value;
    return array_to_comma_split($array);
}

?>