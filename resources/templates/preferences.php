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
        var options = {};
        $("td > input").each(function(index){
            var element_info = this.id.split("-");
            var value;
            if (element_info[1] === "enabled"){
                value = this.checked;
            }
            else{
                value = this.value;
            }
            if (!(element_info[0] in options)){
                options[element_info[0]] = {};
            }
            options[element_info[0]][element_info[1]] = value;
        });

        var keys = Object.keys(options)
        var columns = "";
        for (var i = 0; i < keys.length; i++){
            columns += keys[i] + ":" + options[keys[i]].enabled + "," + options[keys[i]].position;
            if (i != keys.length - 1){
                columns += ";";
            }
        }

        var theme = $("#theme_value")[0].checked ? "full" : "minimal";
        var emails = comma_array_clean($("#email_addresses").val());
        var phones = comma_array_clean($("#phone_numbers").val());
        $.post("api/account/edit", {columns: columns, theme: theme, emails: emails, phones: phones}, function(response){
            response = JSON.parse(response);
            message(response.message);
            if (response.success){
                $("#columns").html(response.html);
            }
        });
    });
</script>
