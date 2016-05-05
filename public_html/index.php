<?php
    session_start();
    include(dirname(__FILE__) . "/../resources/config.php");
?>
<?php
    error_log(print_r($_SESSION, true));
?>
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
        <div id="content">

            <h1>Hello World</h1>

        </div>
    </body>

    <script type="text/javascript">
        $("body").on("click", "#login_button", function(){
            var username = $("#username_input").val()
            var password = $("#password_input").val()
            $("#content").load("backend.php", {action: "login", username: username, password: password})
            $("#content").load("backend.php", {action: "content"})
        })

        $("body").on("click", "#add_kid_button", function(){
            var name = $("#new_kid_name").val()
            var status = $("#new_kid_status").is(':checked')
            var parents = $("#new_kid_parents").val()
            $.post("backend.php", {action: "add_kid", name: name, status: status, parents: parents}, function(data){
                console.log(data)
            })
        })

        $("body").on("click", "input[id$='-edit']", function(event){
            var id = $(event.target)[0].id.split("-")[0]
            console.log(id)
            $("tr[id='" + id + "']").load("backend.php", {action: "show_edit", id: id})
        })

        $("body").on("click", "input[id$='-confirm_edit']", function(event){
            var id = $(event.target)[0].id.split("-")[0]
            var first_name = $("#" + id + "-first_name").val()
            var last_name = $("#" + id + "-last_name").val()
            var parents = $("#" + id + "-parents").val()
            $("tr[id='" + id + "']").load("backend.php", {action: "confirm_edit", id: id, fname: first_name, lname: last_name, parents: parents})
        })

        $("body").on("change", "input[type='checkbox'][id!='new_kid_status']", function(event){
            var id = $(event.target)[0].id.split("-")[0]
            $.post("backend.php", {action: "status_change", id: id, status: $($(event.target)[0]).is(':checked')})
        })

        $("#content").load("backend.php", {action: "content"})

        latest_data = ""

        setInterval(function(){
            $.post("backend.php", {action: "poll"}, function(last_changed){
                if (latest_data != last_changed){
                    $("#content").load("backend.php", {action: "content"})
                }
                latest_data = last_changed
            })
        }, 2500)
    </script>

</html>
