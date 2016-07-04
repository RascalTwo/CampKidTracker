function clean_comma_array(string){
    var values = string.split(",");
    for (var i = 0; i < values.length; i++){
        values[i] = values[i].trim();
    }
    return values.join(",");
}

function updateClocks(){
    $(".time").each(function(){
        var message = "";
        if (this.className.indexOf("since") !== -1){
            var seconds_since = Math.floor(new Date().getTime() / 1000) - parseInt(this.getAttribute("time"));
            var days = Math.floor(seconds_since / (24*60*60));
            var hours = Math.floor(seconds_since / (60*60));
            var minutes = Math.floor(seconds_since / (60));
            if (days === 0 && hours === 0 && minutes === 0){
                message = "Less then a minute ago...";
            }
            else if (days === 0 && hours == 0){
                message = minutes + " minutes ago";
            }
            else if (days === 0){
                minutes -= hours * 60;
                message = hours + " hours and " + minutes + " minutes ago";
            }
            else{
                minutes -= hours * 60;
                hours -= 24 * days;
                message = days + " days, " + hours + " hours, and " + minutes + " minutes ago";
            }
        }
        else{
            var date = new Date(parseInt(this.getAttribute("time"))*1000);
            message = date;
        }
        this.innerHTML = message;
    });
}

function isObject(object){
    if (object === null){
        return false;
    }
    return ((typeof object === "function") || (typeof object === "object"));
}

$(document).on("click", "span.time", function(event){
    if (this.className.indexOf("since") !== -1){
        this.className = "time when";
    }
    else{
        this.className = "time since";
    }
    updateClocks();
})
