<span id="content">
    Username: <input class="lowercase_input" type="text" id="username_input"><br>
    Password: <input type="password" id="password_input"><br>
    <input type="button" id="login_button" value="Login">
</span>
<script type="text/javascript">
    $("#login_button").click(function(){
        var username = $("#username_input").val();
        var password = $("#password_input").val();
        $.post("api/account/login", {username: username, password: password}, function(response){
            response = JSON.parse(response);
            message(response.message);
            if (response.success){
                window.location.href = response.redirect;
            }
        });
    });
</script>
