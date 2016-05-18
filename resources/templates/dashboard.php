<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Florida Summer Camp Kids Status</title>

        <link rel="stylesheet" type="text/css" href="css/index.css">
        <script src="js/jquery-2.2.0.js"></script>

        <style>

        </style>
    </head>

    <body>
        <h1> Ello Mate</h1>
        <div id="content">
            <table>
                <tbody>
                    <?php echo $_SESSION["login"]["account"] -> table_header(false); ?>
                </tbody>
            </table>
        <div id="debug">
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
        </div>
        </div>
    </body>

    <script type="text/javascript">
        function pollUpdate(){
            $.get("api/kid/poll", function(response){
                if (last_poll !== response){
                    var first = false;
                    if (last_poll === 0){
                        first = true;
                    }
                    $.post("api/kid/changed", {since: last_poll, editing: JSON.stringify(editing), first: first}, function(response){
                        eval(response);
                    })
                    last_poll = response;
                }
            })
        }

        $("#add_kid").click(function(){
            var first_name = $("#new_first_name").val();
            var last_name = $("#new_last_name").val();
            var status = $("input[name=new_status]:checked").val();
            var parents = $("#new_parents").val();
            $.post("api/kid/add", {first_name: first_name, last_name: last_name, status: status, parents: parents}, function(response){
                eval(response);
            });
        });

        $("body").on("click", "input[id$='-delete']", function(event){
            var id = event.target.id.split("-")[0];
            $.post("api/kid/delete", {id: id}, function(response){
                $("tr[id='" + id + "']").replaceWith(response);
            });
        });

        $("body").on("click", "input[id$='-edit']", function(event){
            var id = $(event.target)[0].id.split("-")[0]
            editing.push(id);
            $.post("api/kid/get", {id: id, edit: true, type: "row"}, function(response){
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
            $("input[type!=button][id^=" + id + "]").each(function(index){
                var element_info = this.id.split("-");
                var value;
                if (element_info[2] === "status"){
                    if (!this.checked){
                        return;
                    }
                    value = element_info[1];
                    element_info[1] = element_info[2];
                }
                else{
                    value = this.value;
                }
                post_data[element_info[1]] = value;
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

        setInterval(pollUpdate, 2500)

        var editing = [];
    </script>

</html>
