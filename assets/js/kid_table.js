function updateClocks(){
    $(".change_time").each(function(){
        var seconds_sinse = Math.floor(new Date().getTime() / 1000) - parseInt(this.getAttribute("time"));
        var days = Math.floor(seconds_sinse / (24*60*60));
        var hours = Math.floor(seconds_sinse / (60*60));
        var minutes = Math.floor(seconds_sinse / (60));
        var message = "";
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
        $(this).html(message);
    });
}

$(document).on("click", "input[id$='-delete']", function(event){
    var id = event.target.id.split("-")[0];
    $.post("api/kid/delete", {id: id}, function(response){
        response = JSON.parse(response);
        //message(response.message);
        console.log(response.message);
        if (response.success){
            $("tr#" + id).remove();
        }
    });
});

$(document).on("click", "input[id$='-hide']", function(event){
    var id = event.target.id.split("-")[0];
    $.post("api/kid/edit", {id: id, hidden: true}, function(response){
        response = JSON.parse(response);
        //message(response.message);
        console.log(response.message);
        if (response.success){
            $("tr#" + id).replaceWith(response.html);
        }
    });
});

$(document).on("click", "input[id$='-unhide']", function(event){
    var id = event.target.id.split("-")[0];
    $.post("api/kid/edit", {id: id, hidden: false}, function(response){
        response = JSON.parse(response);
        //message(response.message);
        console.log(response.message);
        if (response.success){
            $("tr#" + id).replaceWith(response.html);
        }
    });
});

$(document).on("click", "input[id$='-edit']", function(event){
    var id = $(event.target)[0].id.split("-")[0]
    editing.push(id);
    $.post("api/kid/get", {id: id, edit: true}, function(response){
        response = JSON.parse(response);
        if (response.success){
            $("tr#" + id).replaceWith(response.html);
        }
    });
});

$(document).on("click", "input[id$='-confirm_edit']", function(event){
    var id = $(event.target)[0].id.split("-")[0]
    var index = editing.indexOf(id);
    if (index !== -1){
        editing.splice(index, 1);
    }
    var post_data = {id: id};
    $("input[type!=button][id^=" + id + "]").each(function(){
        if (this.id.split("-").length === 3){
            return;
        }
        post_data[this.id.split("-")[1]] = this.value;
    });
    $.post("api/kid/edit", post_data, function(response){
        response = JSON.parse(response);
        //message(response.message);
        console.log(response.message);
        if (response.success){
            $("tr#" + id).replaceWith(response.html);
        }
    });
});

$(document).on("change", "input[type='radio'][name$='kid_status']", function(event){
    var element_info = event.target.id.split("-");
    $.post("api/kid/change_status", {id: element_info[0], status: element_info[1]}, function(response){
        response = JSON.parse(response);
        //message(response.message);
        console.log(response.message);
    });
})
