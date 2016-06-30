<span id="content">
    <br>
    <span id="preferences">
        <table>
            <tbody id="columns">
                <?php echo get_self() -> table_header(true); ?>
            </tbody>
        </table>
        <br>
        <label for="theme_value">Full Theme</label>
        <input type="checkbox" id="theme_value" <?php echo get_self() -> theme === "full" ? "checked" : ""; ?>>
        <br>
        <label for="email_contacts">Email Address(es)</label>
        <input type="text" id="email_addresses" value="<?php echo implode(', ', get_self() -> contact['emails']); ?>">
        <br>
        <label for="phone_contacts">Phone Number(s)</label>
        <input type="text" id="phone_numbers" value="<?php echo implode(', ', get_self() -> contact['phones']); ?>">
        <br>
        <input id="save_button" type="button" value="Save">
    </span>
</span>
<script type="text/javascript">
    $("#save_button").click(function(){
        var post_data = {
            theme: $("#theme_value")[0].checked ? "full" : "minimal",
            emails: clean_comma_array($("#email_addresses").val()),
            phones: clean_comma_array($("#phone_numbers").val())
        }
        var columns = "";
        $("td > input").each(function(index){
            var element_info = this.id.split("-");
            var value = element_info[1] === "enabled" ? this.checked : this.value
            columns += columns[columns.length-1] === "," ? value + ";" : element_info[0] + ":" + value + ",";
        });
        columns = columns.slice(0, -1);
        post_data["columns"] = columns;

        $.post("api/account/edit", post_data, function(response){
            response = handleResponse(response);
            if (response.success){
                $("#columns").html(response.data);
            }
        });
    });
</script>
