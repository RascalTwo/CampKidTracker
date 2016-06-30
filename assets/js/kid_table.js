function renderKids(rendering_kids, edit, table_id = "kid_table_body"){
    if (!Array.isArray(rendering_kids)){
        rendering_kids = [rendering_kids];
    }
    for (var i = 0; i < rendering_kids.length; i++){
        if ($("tr#" + rendering_kids[i].id + "-kid").length === 0){
            $("#" + table_id + " > tr").last().after(kid_to_row(rendering_kids[i], edit));
        }
        else{
            $("tr#" + rendering_kids[i].id + "-kid").replaceWith(kid_to_row(rendering_kids[i], edit));
        }
    }
}

function updateKids(new_kids){
    if (!Array.isArray(new_kids)){
        new_kids = [new_kids];
    }
    var redrawKids = [];
    for (var i = 0; i < kids.length; i++){
        for (var j = 0; j < new_kids.length; j++){
            if (kids[i].id !== new_kids[j].id){
                continue;
            }
            if (kids[i].modification_time === new_kids[j].modification_time && kids[i].status_update_time === new_kids[j].status_update_time && editing.indexOf(kids[i].id) === -1){
                new_kids.splice(j, 1);
                continue;
            }
            kids[i] = new_kids.splice(j, 1)[0];
            redrawKids.push(kids[i]);
            break;
        }
    }
    if (new_kids.length !== 0){
        kids = kids.concat(new_kids)
        redrawKids = redrawKids.concat(new_kids);
    }
    if (redrawKids.length !== 0){
        renderKids(redrawKids, false);
        updateClocks();
    }
}

function removeKid(kid){
    if (!isObject(kid)){
        kid = {
            id: kid
        };
    }
    $("tr#" + kid.id + "-kid").remove();
    for (var i = 0; i < kids.length; i++) {
        if (kids[i].id !== kid.id){
            continue;
        }
        kids.splice(i, 1);
        break;
    }
}

function kid_to_row(kid, edit){
    var html = "<tr id='" + kid.id + "-kid'>";
    for (var j = 0; j < self.renderColumns.length; j++){
        html += "<td>";
        switch(self.renderColumns[j].identifier){
            case "first_name":
                if (edit){
                    html += "<input size='10' type='text' id='" + kid.id + "-first_name' value='" + kid.first_name + "'>"
                    continue;
                }
                html += kid.first_name;
                break;

            case "last_name":
                if (edit){
                    html += "<input size='10' type='text' id='" + kid.id + "-last_name' value='" + kid.last_name + "'>"
                    continue;
                }
                html += kid.last_name;
                break;

            case "full_name":
                var full_name;
                if (kid.first_name === ""){
                    full_name = kid.last_name;
                }
                else if (kid.last_name === ""){
                    full_name = kid.first_name;
                }
                else{
                    full_name = kid.last_name + ", " + kid.first_name;
                }
                if (edit){
                    html += "<input size='15' type='text' id='" + kid.id + "-full_name' value='" + full_name + "'>"
                    continue;
                }
                html += full_name;
                break;

            case "parents":
                if (edit){
                    html += "<input size='15' type='text' id='" + kid.id + "-parents' value='" + kid.parents.join(", ") + "'>"
                    continue;
                }
                html += kid.parents.join("<br>");
                break;

            case "status":
                var name = "name='" + kid.id + "-kid_status'";
                html += "<label for='" + kid.id + "-in-kid_status'>In</label>";
                html += "<input " + name + " type='radio' id='" + kid.id + "-in-kid_status' value='in' " + (kid.status === "in" ? "checked" : "") + "><br>";

                html += "<label for='" + kid.id + "-out-kid_status'>Out</label>";
                html += "<input " + name + " type='radio' id='" + kid.id + "-out-kid_status' value='out' " + (kid.status === "out" ? "checked" : "") + "><br>";

                html += "<label for=" + kid.id + "-transit-kid_status'>Transit</label>";
                html += "<input " + name + " type='radio' id='" + kid.id + "-transit-kid_status' value='transit' " + (kid.status === "transit" ? "checked" : "") + "><br>";

                html += "<label for=" + kid.id + "-parentarrived-kid_status'>Parent Arrived</label>";
                html += "<input " + name + " type='radio' id='" + kid.id + "-parentarrived-kid_status' value='parentarrived' " + (kid.status === "parentarrived" ? "checked" : "") + "><br>";
                break;

            case "actions":
                <?php if (get_self() -> has_access("mod")){ ?>
                    if (edit){
                        html += "<input id='" + kid.id + "-kid_confirm_edit' type='button' value='Confirm Edit'>";
                    }
                    else{
                        html += "<input id='" + kid.id + "-kid_edit' type='button' value='Edit'>";
                    }
                    <?php if (get_self() -> has_access("admin")){ ?>
                        html += "<input id='" + kid.id + "-kid_delete' type='button' value='Delete'>";
                        html += "<br>";
                        if (kid.hidden){
                            html += "<input id='" + kid.id + "-kid_unhide' type='button' value='Un-Hide'>";
                        }
                        else{
                            html += "<input id='" + kid.id + "-kid_hide' type='button' value='Hide'>";
                        }
                    <?php } ?>
                <?php } ?>
                break;

            case "id":
                html += kid.id
                break;

            case "changed":
                html += "Modified:<br>";
                html += "<span class='time when' time='" + kid.modification_time + "'></span>";
                html += "<br>";
                html += "Status Updated:<br>";
                html += "<span class='time when' time='" + kid.status_update_time + "'></span>";
                break;

            case "group":
                var options_html = "<option value=''>None</option>";
                var group_name;
                for (var i = 0; i < groups.length; i++){
                    if (groups[i].id == kid.group){
                        group_name = groups[i].name;
                        if (!edit){
                            break;
                        }
                    }
                    if (edit){
                        if (groups[i].name === group_name){
                            options_html += "<option selected value='" + groups[i].id + "'>" + groups[i].name + "</option>"
                            continue;
                        }
                        options_html += "<option value='" + groups[i].id + "'>" + groups[i].name + "</option>"
                    }
                }
                if (edit){
                    html += "<select id='" + kid.id + "-kid_group'>";
                    html += options_html;
                    html += "</select>";
                }
                else{
                    if (kid.group !== null){
                        html += group_name;
                    }
                    else{
                        html += "None";
                    }
                }
                break;
        }
        html += "</td>";
    }
    html += "</tr>";
    return html;
}

$(document).on("click", "input[id$='-kid_delete']", function(event){
    var id = event.target.id.split("-")[0];
    $.post("api/kid/delete", {id: id}, function(response){
        response = handleResponse(response);
        if (response.success){
            removeKid(id);
        }
    });
});

$(document).on("click", "input[id$='-kid_hide']", function(event){
    var id = event.target.id.split("-")[0];
    $.post("api/kid/edit", {id: id, hidden: true}, function(response){
        response = handleResponse(response)
        if (response.success){
            removeKid(response.data)
        }
    });
});

$(document).on("click", "input[id$='-kid_unhide']", function(event){
    var id = event.target.id.split("-")[0];
    $.post("api/kid/edit", {id: id, hidden: false}, function(response){
        response = handleResponse(response)
        if (response.success){
            updateKids(response.data)
        }
    });
});

$(document).on("click", "input[id$='-kid_edit']", function(event){//DONE
    var id = event.target.id.split("-")[0];
    for (var i = 0; i < kids.length; i++){
        if (kids[i].id == id){
            editing.push(id);
            renderKids(kids[i], true)
        }
    }
});

$(document).on("click", "input[id$='-kid_confirm_edit']", function(event){
    var id = event.target.id.split("-")[0];
    var index = editing.indexOf(id);
    if (index !== -1){
        editing.splice(index, 1);
    }
    var post_data = {id: id};
    $("input[type!=button][id^=" + id + "]").each(function(){
        if (this.id.split("-").length === 3){
            return;
        }
        var property = this.id.split("-")[1];
        if (["parents", "full_name"].indexOf(property) !== -1){
            post_data[property] = clean_comma_array(this.value)
            return;
        }
        post_data[property] = this.value;
    });
    post_data["group"] = $("#" + id + "-kid_group :selected").val();
    console.log(post_data);
    $.post("api/kid/edit", post_data, function(response){
        response = handleResponse(response);
        if (response.success){
            updateKids(response.data);
        }
    });
});

$(document).on("change", "input[type='radio'][name$='-kid_status']", function(event){
    var element_info = event.target.id.split("-");
    $.post("api/kid/update_status", {id: element_info[0], status: element_info[1]}, function(response){
        updateKids(handleResponse(response).data)
    });
})