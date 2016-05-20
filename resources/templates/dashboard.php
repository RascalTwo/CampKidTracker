<?php
$page_title = "Flordia Summer Camp Kids - Dashboard";
include "header.php";
?>
        <span id="content">
            <span id="kid_table">
                <table>
                    <tbody id="kid_table_body">
                        <?php echo get_self() -> table_header(false); ?>
                    </tbody>
                </table>
            </span>
            <?php if (get_self() -> has_atleast_access("mod")){ ?>
                <span id="new_kid">
                    First Name: <input type="text" id="new_first_name"><br>
                    Last Name: <input type="text" id="new_last_name"><br>
                    Status:
                    <label for="new_status_in">In</label>
                    <input type="radio" name="new_status" id="new_status_in" value="in">
                    <label for="new_status_out">Out</label>
                    <input type="radio" name="new_status" id="new_status_out" value="out">
                    <label for="new_status_transit">Transit</label>
                    <input type="radio" name="new_status" id="new_status_transit" value="transit">
                    <br>
                    Parents: <input type="text" id="new_parents"><br>
                    <p id="add_kid_response"></p>
                    <input type="button" id="add_kid" value="Add">
                </span>
            <?php } ?>
        </span>
    </body>

    <script type="text/javascript">
        function pollUpdate(){
            $.get("api/kid/poll", function(response){
                if (last_poll !== response){
                    var first = false;
                    if (last_poll === 0){
                        first = true;
                    }
                    $.post("api/kid/list", {since: last_poll, editing: editing.join(","), first: first}, function(response){
                        eval(response);
                    })
                    last_poll = response;
                    setTimeout(updateClocks, 1000);
                }
            })
        }

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

        $("#add_kid").click(function(){
            var first_name = $("#new_first_name").val();
            var last_name = $("#new_last_name").val();
            var status = $("input[name=new_status]:checked").val();
            var parents = $("#new_parents").val();
            $.post("api/kid/add", {first_name: first_name, last_name: last_name, status: status, parents: parents}, function(response){
                eval(response);
            });
            setTimeout(updateClocks, 1000);
        });

        $("body").on("click", "input[id$='-delete']", function(event){
            var id = event.target.id.split("-")[0];
            $.post("api/kid/delete", {id: id}, function(response){
                $("tr[id='" + id + "']").replaceWith(response);
            });
        });

        $("body").on("click", "input[id$='-hide']", function(event){
            var id = event.target.id.split("-")[0];
            $.post("api/kid/edit", {id: id, hidden: true}, function(response){
                $("tr[id='" + id + "']").replaceWith(response);
            });
        });

        $("body").on("click", "input[id$='-edit']", function(event){
            var id = $(event.target)[0].id.split("-")[0]
            editing.push(id);
            $.post("api/kid/get", {id: id, edit: true}, function(response){
                $("tr[id='" + id + "']").replaceWith(response);
            });
        });

        $("body").on("click", "input[id$='-confirm_edit']", function(event){
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
                $("tr[id='" + id + "']").replaceWith(response);
            });
        });

        $("body").on("change", "input[type='radio'][name!=new_status]", function(event){
            var element_info = event.target.id.split("-");
            $.post("api/kid/change_status", {id: element_info[0], status: element_info[1]});
        })

        last_poll = 0;

        pollUpdate();

        setInterval(pollUpdate, 2500);

        setInterval(updateClocks, 60000);

        var editing = [];
    </script>

</html>
