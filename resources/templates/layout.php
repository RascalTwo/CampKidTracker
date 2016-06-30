<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=320, initial-scale=1">
        <title><?php echo $page_title; ?></title>
        <link rel="stylesheet" type="text/css" href="css/main.css">
        <?php if ($css !== NULL){
            foreach ($css as $filename){
                echo '<link rel="stylesheet" type="text/css" href="css/' . $filename . '.css">';
            }
        }
        ?>
        <script src="js/jquery-2.2.3.min.js"></script>
        <?php
        if ($js !== NULL){
            foreach ($js as $filename){
                echo '<script type="text/javascript" src="js/' . $filename . '.js"></script>';
            }
        }
        ?>
        <script type="text/javascript">
            $.ajaxSetup({
                error: function(jqXHR, exception){
                    try{
                        response = handleResponse(jqXHR.responseText)
                        if (response.hasOwnProperty("redirect")){
                            window.location.href = response.redirect;
                        }
                    }
                    catch(err){
                        message(jqXHR.status + " - " + jqXHR.statusText);
                    }
                }
            });

            function handleResponse(response){
                response = JSON.parse(response);
                if (response.hasOwnProperty("message")){
                    message(response.message);
                }
                return response;
            }

            var get = { //Finish or remove.
                byId: function(id){
                    return document.getElementById(id);
                }
            }

            function addListener(event, target, callback){ //Finish or remove.
                if (target.addEventListener){
                    target.addEventListener(event, callback, false);
                }
                else{
                    target.attachEvent("on" + event, callback);
                }
            }

            function message(new_message){
                var element = get.byId("message");
                element.innerHTML = new_message;
                if (element.style.visibility !== "visible"){
                    element.style.visibility = "visible";
                }
            }

        </script>
    </head>
    <body>
        <span id="navigation">
            <ul>
                <?php if (is_logged_in()){ ?>
                    <li><a href="/dashboard">Dashboard</a></li>
                    <li><a href="/groups">Groups</a></li>
                    <li><a href="/preferences">Preferences</a></li>
                    <?php if (get_self() -> has_access("admin")){ ?>
                        <li><a href="/admin">Admin</a></li>
                    <?php } ?>
                    <?php if (get_self() -> has_access("mod")){ ?>
                        <li><a href="/history">History</a></li>
                    <?php } ?>
                <?php }else{ ?>
                    <li><a href="/login">Log In</a></li>
                <?php }?>
                <li><a href="/about">About</a></li>
            </ul>
        </span>
        <br><br>
        <span id="message"></span>
        <?php include $template_path; ?>
    </body>
    <script type="text/javascript">
        addListener("click", get.byId("message"), function(){
            if (this.style.visibility !== "hidden"){
                this.style.visibility = "hidden";
            }
        });
    </script>
</html>
