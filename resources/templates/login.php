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
        Username: <input type="text" id="username_input"><br>
        Password: <input type="text" id="password_input"><br>
        <p id="login_response"></p>
        <input type="button" id="login_button" value="Login">
        <div id="debug">
            Display : <input type="text" id="new_dn"><br>
            Username: <input type="text" id="new_un"><br>
            Password: <input type="text" id="new_pw"><br>
            Access L: <input type="text" id="new_ac"><br>
            <p id="create_response"></p>
            <input type="button" id="create_account" value="Create">
        </div>
    </body>

    <script type="text/javascript">
        $("#login_button").click(function(){
            var username = $("#username_input").val();
            var password = $("#password_input").val();
            $.post("api/account/login", {username: username, password: password}, function(response){
                console.log(response);
                eval(response);
            });
        });

        $("#create_account").click(function(){
            var display_name = $("#new_dn").val();
            var username = $("#new_un").val();
            var password = $("#new_pw").val();
            var access_level = $("#new_ac").val();
            $.post("api/account/create", {display_name: display_name, username: username, password: password, access_level: access_level, force: true}, function(response){
                console.log(response);
                eval(response);
            });
        });
    </script>

</html>
