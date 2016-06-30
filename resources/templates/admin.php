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
                <tr class="table_header">
                    <th>Display Name</th>
                    <th>Username</th>
                    <th>Access Level</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </tbody>
        </table>
    </span>
</span>
<script type="text/javascript">
    var self = <?php echo get_self() -> json(); ?>;
    var kids = <?php echo as_json("kids", function($var){return $var -> hidden;}); ?>;
    var groups = <?php echo as_json("groups"); ?>;

    var last_poll = 0;
    var editing = [];

    self.renderColumns = self.columns.filter(function(column){
        return column.enabled;
    }).sort(function(a, b){
        return a.position - b.position;
    });

    renderKids(kids, false);

    function pollUpdate(){
        $.get("api/kid/poll", function(response){
            if (last_poll !== response){
                last_poll = response;
                $.post("api/kid/list", {since: last_poll, hidden: true}, function(response){
                    response = handleResponse(response);
                    kids = kids.sort(function(a, b){
                        return a.id - b.id;
                    })
                    response.data = response.data.sort(function(a, b){
                        return a.id - b.id;
                    })
                    updateKids(response.data);
                })
            }
        })
    }

    function listAccounts(){
        $.get("api/account/list", function(response){
            response = handleResponse(response);
            $("#account_table_body").children().each(function(){
                if (this.className === "table_header"){
                    return;
                }
                $(this).remove();
            });
            for (var i = 0; i < response.data.length; i++){
                $("#account_table_body > tr").last().after(response.data[i]);
            }
        });
    }

    $("#create_account").click(function(){
        var post_data = {
            display_name: $("#display_name").val(),
            username: $("#username").val(),
            password: $("#password").val(),
            access_level: $("input[name=access_level]:checked").val()
        }
        $.post("api/account/create", post_data, function(response){
            response = handleResponse(response);
            console.log(response);
            if (response.success){
                listAccounts();
            }
        });
    });

    $(document).on("click", "input[id$='-delete_account']", function(event){
        var id = event.target.id.split("-")[0];
        $.post("api/account/delete", {id: id}, function(response){
            response = handleResponse(response);
            $("tr#" + id + "-account").remove();
        });
    });

    pollUpdate();
    listAccounts();

    setInterval(pollUpdate, 2500);
    setInterval(updateClocks, 60000);
</script>
