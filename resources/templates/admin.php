<?php
$page_title = "Flordia Summer Camp Kids - Admin";
include "header.php";
?>
        <span id="content">
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
                <p id="create_response"></p>
                <input type="button" id="create_account" value="Create">
            </span>
            <span id="account_list">
                <table>
                    <tbody>
                        <tr id="account_list_header">
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
    </body>
    <script type="text/javascript">
        function pollUpdate(){
            $.get("api/kid/poll", function(response){
                if (last_poll !== response){
                    var first = false;
                    if (last_poll === 0){
                        first = true;
                    }
                    $.post("api/kid/list", {since: last_poll, editing: editing.join(","), first: first, hidden: true}, function(response){
                        eval(response);
                    })
                    last_poll = response;
                    setTimeout(updateClocks, 1000);
                }
            })
        }

        function listAccounts(){
            $.get("api/account/list", function(response){
                $("#account_list_header").after(response);
            });
        }

        $("#create_account").click(function(){
            var display_name = $("#display_name").val();
            var username = $("#username").val();
            var password = $("#password").val();
            var access_level = $("input[name=access_level]:checked").val();
            $.post("api/account/create", {display_name: display_name, username: username, password: password, access_level: access_level}, function(response){
                eval(response);
            });
        });

        $("body").on("click", "input[id$='-delete_account']", function(event){
            var id = event.target.id.split("-")[0];
            $.post("api/account/delete", {id: id}, function(response){
                $("tr[id='" + id + "']").replaceWith(response);
            });
        });

        $("body").on("click", "input[id$='-delete']", function(event){
            var id = event.target.id.split("-")[0];
            $.post("api/kid/delete", {id: id}, function(response){
                $("tr[id='" + id + "']").replaceWith(response);
            });
        });

        $("body").on("click", "input[id$='-unhide']", function(event){
            var id = event.target.id.split("-")[0];
            $.post("api/kid/edit", {id: id, hidden: false}, function(response){
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

        $("body").on("change", "input[type='radio'][name!=access_level]", function(event){
            var element_info = event.target.id.split("-");
            $.post("api/kid/change_status", {id: element_info[0], status: element_info[1]});
        })

        last_poll = 0;

        pollUpdate();

        setInterval(pollUpdate, 2500);

        var editing = [];

        listAccounts();
    </script>

</html>
