function comma_array_clean(string){
    var values = string.split(",");
    for (var i = 0; i < values.length; i++){
        values[i] = values[i].trim();
    }
    return values.join(",");
}