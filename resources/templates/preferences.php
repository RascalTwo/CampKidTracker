<span id="content">
    <br>
    <span id="preferences">
        <table>
            <tbody>
                <?php echo $_SESSION["login"]["account"] -> table_header(true); ?>
            </tbody>
        </table>
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

        $.post("api/account/edit", {columns: columns, theme: "full"}, function(response){
            eval(response);
        });
    });
</script>
