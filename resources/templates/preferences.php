<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <title>Florida Summer Camp Kids Status</title>

        <link rel="stylesheet" type="text/css" href="css/index.css">
        <script src="js/jquery-2.2.0.js"></script>

        <style>

        </style>
    </head>

    <body>
        <h1>Change yo settings!</h1>
        <div id="content">
            <table>
                <tbody>
                    <?php echo $_SESSION["login"]["account"] -> table_header(true); ?>
                </tbody>
            </table>
            <input id="save_button" type="button" value="Save">
        </div>
    </body>
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
        $.post("api/account/edit", {columns: JSON.stringify(options), theme: "full"}, function(response){
            eval(response);
        });
    });
    </script>

</html>
