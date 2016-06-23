<span id="content">
    <br>
    <span id="group_list">
        <table>
            <tbody id="group_list_table_body">
                <tr>
                    <th>Group</th>
                    <th>Kids</th>
                    <th>Leaders</th>
                    <?php if (get_self() -> has_access("mod")){ ?>
                        <th>Actions</th>
                    <?php } ?>
                </tr>
            </tbody>
        </table>
    </span>
    <span id="group_view" class="group_loaded" style="display: none;">
        <h2 id="group_name"></h2>
        <table>
            <tbody id="current_group_kids_table_body">
                <?php echo get_self() -> table_header(false); ?>
            </tbody>
        </table>
        <table>
            <tbody id="current_group_leaders_table_body">
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
    <br>
    <?php if (get_self() -> has_access("mod")){ ?>
    <span id="group_creation">
        Group Name: <input type="text" id="new_group_name">
        <br>
        <input type="button" id="new_group" value="Create">
    </span>
    <?php } ?>
</span>
<script type="text/javascript">
    function getGroups(){
        $.get("api/group/list", function(response){
            response = JSON.parse(response);
            console.log(response);
            for (var i = 0; i < response.length; i++){
                $("#group_list_table_body > tr").last().after(response[i]);
            }
        });
    }

    $(document).on("click", "input[id$='-group_view']", function(event){
        var id = event.target.id.split("-")[0];
        $.post("api/group/get", {id: id}, function(response){
            response = JSON.parse(response);
            //message(response.message);
            console.log(response);
            if (response.success){
                $(".group_loaded").show();
                for (var i = 0; i < response.kids.length; i++){
                    $("#current_group_kids_table_body > tr").last().after(response.kids[i]);
                }
                for (var i = 0; i < response.accounts.length; i++){
                    $("#current_group_accounts_table_body > tr").last().after(response.accounts[i]);
                }
            }
        });
    });

    $(document).on("click", "input[id$='-group_delete']", function(event){
        var id = event.target.id.split("-")[0];
        $.post("api/group/delete", {id: id}, function(response){
            response = JSON.parse(response);
            message(response.message);
            console.log(response.message);
            if (response.success){
                $("#" + id + "-group").remove();
            }
        });
    });

    $(document).on("click", "input[id$='-group_edit']", function(event){
        var id = $(event.target)[0].id.split("-")[0]
        $.post("api/group/get", {id: id, edit: true}, function(response){
            response = JSON.parse(response);
            if (response.success){
                $("tr#" + id + "-group").replaceWith(response.html);
            }
        });
    });

    $(document).on("click", "input[id$='-group_confirm_edit']", function(event){
        var id = $(event.target)[0].id.split("-")[0]
        var post_data = {
            id: id,
            name: $("#" + id + "-group_name").val()
        };
        $.post("api/group/edit", post_data, function(response){
            response = JSON.parse(response);
            message(response.message);
            if (response.success){
                $("tr#" + id + "-group").replaceWith(response.html);
            }
        });
    });

    $("#new_group").click(function(){
        var name = $("#new_group_name").val();
        $.post("api/group/create", {name: name}, function(response){
            response = JSON.parse(response);
            message(response.message);
            getGroups();
        });
    });

    getGroups();

</script>
