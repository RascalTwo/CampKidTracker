<span id="content">
    <br>
    <span id="kid_table">
        <table>
            <tbody id="kid_table_body">
                <?php echo get_self() -> table_header(false); ?>
            </tbody>
        </table>
    </span>
    <?php if (get_self() -> has_access("mod")){ ?>
        <span id="new_kid">
            First Name: <input type="text" id="new_first_name"><br>
            Last Name: <input type="text" id="new_last_name"><br>
            Parents: <input type="text" id="new_parents"><br>
            Group:
            <select id="new_group">
                <option value="">None</option>
            </select>
            <br>
            Status:
            <label for="new_status_in">In</label>
            <input type="radio" name="new_status" id="new_status_in" value="in">
            <label for="new_status_out">Out</label>
            <input checked type="radio" name="new_status" id="new_status_out" value="out">
            <label for="new_status_transit">Transit</label>
            <input type="radio" name="new_status" id="new_status_transit" value="transit">
            <label for="new_status_parentarrived">Parent Arrived</label>
            <input type="radio" name="new_status" id="new_status_parentarrived" value="parentarrived">
            <br>
            <input type="button" id="add_kid" value="Add">
        </span>
    <?php } ?>
</span>
<script type="text/javascript">
    var self = <?php echo get_self() -> json(); ?>;
    var kids = <?php echo as_json("kids", function($var){return !($var -> hidden);}); ?>;
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
                $.post("api/kid/list", {since: last_poll}, function(response){
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

    pollUpdate();

    $("#add_kid").click(function(){
        var post_data = {
            first_name: $("#new_first_name").val(),
            last_name: $("#new_last_name").val(),
            status: $("input[name=new_status]:checked").val(),
            parents: $("#new_parents").val(),
            group: $("#new_group :selected").val()
        };
        $.post("api/kid/add", post_data, function(response){
            updateKids(handleResponse(response).data);
        });
    });

    for (var i = 0; i < groups.length; i++){
        $("select > option").last().after("<option value='" + groups[i].id + "'>" + groups[i].name + "</option>")
    }

    setInterval(pollUpdate, 2500);
    setInterval(updateClocks, 60000);
</script>
