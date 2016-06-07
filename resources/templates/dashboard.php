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
            Status:
            <label for="new_status_in">In</label>
            <input type="radio" name="new_status" id="new_status_in" value="in">
            <label for="new_status_out">Out</label>
            <input type="radio" name="new_status" id="new_status_out" value="out">
            <label for="new_status_transit">Transit</label>
            <input type="radio" name="new_status" id="new_status_transit" value="transit">
            <label for="new_status_parentarrived">Parent Arrived</label>
            <input type="radio" name="new_status" id="new_status_parentarrived" value="parentarrived">
            <br>
            Parents: <input type="text" id="new_parents"><br>
            <input type="button" id="add_kid" value="Add">
        </span>
    <?php } ?>
</span>
<script type="text/javascript">
    function pollUpdate(){
        $.get("api/kid/poll", function(response){
            if (last_poll !== response){
                var append = false;
                if (last_poll === 0){
                    append = true;
                }
                $.post("api/kid/list", {since: last_poll, editing: editing.join(","), append: append}, function(response){
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

    $("#add_kid").click(function(){
        var first_name = $("#new_first_name").val();
        var last_name = $("#new_last_name").val();
        var status = $("input[name=new_status]:checked").val();
        var parents = $("#new_parents").val();
        $.post("api/kid/add", {first_name: first_name, last_name: last_name, status: status, parents: parents}, function(response){
            response = JSON.parse(response);
            //message(response.message);
            console.log(response.message);
            if (response.success){
                $("#kid_table_body > tr").last().after(response.html);
            }
        });
        setTimeout(updateClocks, 1000);
    });

    var last_poll = 0;

    pollUpdate();

    setInterval(pollUpdate, 2500);

    setInterval(updateClocks, 60000);

    var editing = [];

</script>
