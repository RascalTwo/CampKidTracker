<span id="content">
    <br>
    <span id="group_list">
        <table>
            <tbody id="group_list_table_body">
                <tr class="table_header">
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
                <tr class="table_header">
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
    var self = <?php echo get_self() -> json(); ?>;
    var kids = <?php echo as_json("kids", function($var){return !($var -> hidden);}); ?>;
    var groups = <?php echo as_json("groups"); ?>;

    var editing = [];

    self.renderColumns = self.columns.filter(function(column){
        return column.enabled;
    }).sort(function(a, b){
        return a.position - b.position;
    });

    function listGroups(){
        $.get("api/group/list", function(response){
            response = handleResponse(response);
            $("#group_list_table_body").children().each(function(){
                if (this.className === "table_header"){
                    return;
                }
                $(this).remove();
            });
            for (var i = 0; i < response.data.length; i++){
                $("#group_list_table_body > tr").last().after(response.data[i]);
            }
        });
    }

    function listKids(kids){
        $("#current_group_kids_table_body").children().each(function(){
            if (this.className === "table_header"){
                return;
            }
            $(this).remove();
        });
        for (var i = 0; i < kids.length; i++){
            $("#current_group_kids_table_body > tr").last().after(kid_to_row(kids[i]));
        }
    }

    function listLeaders(leaders){
        $("#current_group_leaders_table_body").children().each(function(){
            if (this.className === "table_header"){
                return;
            }
            $(this).remove();
        });
        for (var i = 0; i < leaders.length; i++){
            $("#current_group_leaders_table_body > tr").last().after(leaders[i]);
        }
    }

    $("#new_group").click(function(){
        var name = $("#new_group_name").val();
        $.post("api/group/create", {name: name}, function(response){
            handleResponse(response);
            listGroups();
        });
    });

    $(document).on("click", "input[id$='-group_view']", function(event){
        var id = event.target.id.split("-")[0];
        $.post("api/group/get", {id: id}, function(response){
            response = handleResponse(response);
            if (response.success){
                $(".group_loaded").show();
                listKids(response.data.kids);
                listLeaders(response.data.leaders);
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
                $("tr#" + id + "-group").replaceWith(response.data.group);
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
                $("tr#" + id + "-group").replaceWith(response.data);
            }
        });
    });

    listGroups();

    setInterval(updateClocks, 60000);

</script>
