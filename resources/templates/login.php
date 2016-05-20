<?php
$page_title = "Flordia Summer Camp Kids - Login";
include "header.php";
?>
        <span id="content">
            <br>
            Username: <input class="lowercase_input" type="text" id="username_input"><br>
            Password: <input type="text" id="password_input"><br>
            <p id="login_response"></p>
            <input type="button" id="login_button" value="Login">
        </span>
    </body>
    <script type="text/javascript">
        $("#login_button").click(function(){
            var username = $("#username_input").val();
            var password = $("#password_input").val();
            $.post("api/account/login", {username: username, password: password}, function(response){
                eval(response);
            });
        });
    </script>
</html>
