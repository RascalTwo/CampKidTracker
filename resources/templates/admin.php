<span id="content">
    <br>
    <span id="hidden_kid_table">
        <table>
            <tbody id="kid_table_body">
                <?php echo get_self() -> table_header(false); ?>
            </tbody>
        </table>
    </span>
    <span id="account_creation">
        Display : <input type="text" id="display_name"><br>
        Username: <input class="lowercase_input" type="text" id="username"><br>
        Password: <input type="text" id="password"><br>
        Access Level:
        <label for="access_level_admin">Admin</label>
        <input type="radio" name="access_level" id="access_level_admin" value="admin">
        <label for="access_level_mod">Mod</label>
        <input type="radio" name="access_level" id="access_level_mod" value="mod">
        <label for="access_level_user">User</label>
        <input type="radio" name="access_level" id="access_level_user" value="user" checked>
        <input type="button" id="create_account" value="Create">
    </span>
    <span id="account_list">
        <table>
            <tbody id="account_table_body">
                <tr>
                    <th>Display Name</th>
                    <th>Username</th>
                    <th>Access Level</th>
                    <th>Last Login</th>
                    <th>Options</th>
                </tr>
            </tbody>
        </table>
    </span>
</span>
<script type="text/javascript">
    function pollUpdate(){
        $.get("api/kid/poll", function(response){
            if (last_poll !== response){
                var append = false;
                if (last_poll === 0){
                    append = true;
                }
                $.post("api/kid/list", {since: last_poll, editing: editing.join(","), append: append, hidden: true}, function(response){
                    response = JSON.parse(response);
                    for (var i = 0; i < response.length; i++){
                        if (append){
                            $("#kid_table_body > tr").last().after(response[i].html);
                        }
                        else{
                            $("#kid_table_body > tr#" + response[i].id).replaceWith(response[i].html)
                        }
                    }
                })
                last_poll = response;
                setTimeout(updateClocks, 1000);
            }
        })
    }

    function listAccounts(){
        $.get("api/account/list", function(response){
            response = JSON.parse(response);
            for (var i = 0; i < response.length; i++){
                $("#account_table_body > tr").last().after(response[i]);
            }
        });
    }

    $("#create_account").click(function(){
        var display_name = $("#display_name").val();
        var username = $("#username").val();
        var password = $("#password").val();
        var access_level = $("input[name=access_level]:checked").val();
        $.post("api/account/create", {display_name: display_name, username: username, password: password, access_level: access_level}, function(response){
            response = JSON.parse(response);
            //message(response.message);
            console.log(response.message);
            if (response.success){
                listAccounts();
            }
        });
    });

    $("body").on("click", "input[id$='-delete_account']", function(event){
        var id = event.target.id.split("-")[0];
        $.post("api/account/delete", {id: id}, function(response){
            response = JSON.parse(response);
            $("tr[id='" + id + "']").replaceWith(response);
        });
    });


    var last_poll = 0;

    pollUpdate();

    setInterval(pollUpdate, 2500);

    setInterval(updateClocks, 60000);

    var editing = [];

    listAccounts();
</script>
