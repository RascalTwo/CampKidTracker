<?php
require_once __DIR__ . "/resources/config.php";
require_once $config["class"]["router"];
require_once $config["class"]["account"];
require_once $config["class"]["kid"];
require_once $config["class"]["logging"];
require_once $config["class"]["utility"];

$router = new Router;

$logger = new Logger($config["path"]["logs"]);

function logged_in(){
    return (array_key_exists("login", $_SESSION) && $_SESSION["login"]["account"] -> last_online + (60 * 60) > time());
}

function get_self(){
    if (array_key_exists("login", $_SESSION)){
        return $_SESSION["login"]["account"];
    }
    return;
}

function error_page($error_message){
    global $config;
    include $config["templates"]["error"];
}

function redirect($path){
    echo "<script type='text/javascript'>window.location = 'http://" . $_SERVER["HTTP_HOST"] . $path . "'</script>";
}

#3/3
$router -> get("/", function(){
    global $config;
    if (!logged_in()){
        unset($_SESSION["login"]);
        redirect("/login");
        return;
    }
    redirect("/dashboard");
    return;
});

#3/3
$router -> get("/login", function(){
    global $config;
    include $config["templates"]["login"];
    return;
});

#3/3
$router -> get("/dashboard", function(){
    global $config;
    if (!logged_in()){
        unset($_SESSION["login"]);
        redirect("/login");
        return;
    }
    include $config["templates"]["dashboard"];
    return;
});

#3/3
$router -> get("/preferences", function(){
    global $config;
    if (!logged_in()){
        unset($_SESSION["login"]);
        redirect("/login");
        return;
    }
    include $config["templates"]["preferences"];
    return;
});

#3/3
$router -> get("/admin", function(){
    global $config;
    if (!logged_in()){
        unset($_SESSION["login"]);
        redirect("/login");
        return;
    }
    if (get_self() -> has_access("admin")){
        include $config["templates"]["admin"];
        return;
    }
    header("HTTP/1.0 401 Unauthorized");
    return;
});

#3/3
$router -> get("/history", function(){
    global $config;
    if (!logged_in()){
        unset($_SESSION["login"]);
        redirect("/login");
        return;
    }
    include $config["templates"]["history"];
    return;
});

#3/3
$router -> get("/about", function(){
    global $config;
    include $config["templates"]["about"];
    return;
});

/**
 * Login to an account.
 * @param $_POST["username"] - Username of account logging into.
 * @param $_POST["password"] - Password of account logging into.
 *
 * @return Javascript.
 */
$router -> post("/api/account/login", function(){
    global $config, $logger;

    $logged_in = false;
    $accounts = load_accounts($config["database"]["accounts"]);
    if (count($accounts) === 0){
        $new_account = new Account(generate_id($config["database"]["ids"]), $_POST["display_name"], strtolower($_POST["username"]), $_POST["password"], "admin");
        array_push($accounts, $new_account);
    }
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> username_match($_POST["username"])){
            if ($accounts[$key] -> password_match($_POST["password"])){
                $logged_in = true;
                $accounts[$key] -> login();
                $_SESSION["login"]["account"] = $accounts[$key];
                $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"], $accounts[$key] -> get_username(), true);
            }
            else{
                $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"], $accounts[$key] -> get_username(), false);
            }
        }
    }

    if (!isset($event)){
        $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"], strtolower($_POST["username"]), false);
    }

    $logger -> log($event);

    if ($logged_in){
        save_accounts($accounts, $config["database"]["accounts"]);
        echo "window.location = window.location.origin + '/dashboard';";
        return;
    }

    echo "$('#login_response').html('Incorrect Username or Password!')";
    return;
}, ["username" => true, "password" => true]);

/**
 * Create an account.
 * @param $_POST["display_name"] - Display name of new account.
 * @param $_POST["username"] - Username of new account.
 * @param $_POST["password"] - Password of new account.
 * @param $_POST["access_level"] - Access level of new account.
 *
 * @return Javascript.
 */
$router -> post("/api/account/create", function(){
    global $config, $logger;
    if (!array_key_exists("force", $_POST) && !logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in.");
        return;
    }
    if (!array_key_exists("force", $_POST) && !(get_self() -> has_access("admin"))){
        header("HTTP/1.0 403 Forbidden");
        error_page("Only accounts with an access level of 'admin' can create accounts.");
        return;
    }
    $accounts = load_accounts($config["database"]["accounts"]);
    $new_account = new Account(generate_id($config["database"]["ids"]), $_POST["display_name"], strtolower($_POST["username"]), $_POST["password"], $_POST["access_level"]);
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> get_username() !== $new_account -> get_username()){
            continue;
        }
        $logger -> log(new Custom_Event($_SERVER["REMOTE_ADDR"], get_self() -> get_username(), "Failed to create account", "Username '" . $new_account -> get_username() . "' already exists"));
        echo "$('#create_response').html('Username already exists!');";
        return;
    }

    $logger -> log(new Creation_Event($_SERVER["REMOTE_ADDR"], get_self() -> get_username(), "account", ["Display Name" => $new_account -> display_name, "Username" => $new_account -> get_username(), "ID" => $new_account -> id]));

    array_push($accounts, $new_account);
    save_accounts($accounts, $config["database"]["accounts"]);
    echo "$('#create_response').html('Account Successfully Created!');";
}, ["display_name" => true, "username" => true, "password" => true, "access_level" => true]);

/**
 * Delete an account.
 * @param $_POST["id"] - ID of account to delete.
 *
 * @return string - HTML code for table row.
 */
$router -> post("/api/account/delete", function(){
    global $config, $logger;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }
    if (!(get_self() -> has_access("admin"))){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Only accounts with an access level of 'admin' can delete accounts.");
        return;
    }
    if (get_self() -> id === $_POST["id"]){
        header("HTTP/1.0 400 Bad Request");
        error_page("You can not delete your own account.");
        return;
    }
    $account = get_account($_POST["id"], $config["database"]["accounts"]);

    $logger -> log(new Deletion_Event($_SERVER["REMOTE_ADDR"], get_self() -> get_username(), "account", ["Display Name" => $account -> display_name, "Username" => $account -> get_username(), "ID" => $account -> id]));

    $accounts = load_accounts($config["database"]["accounts"]);
    unset($accounts[array_search($account, $accounts)]);
    save_accounts($accounts, $config["database"]["accounts"]);
    echo "";
}, ["id" => true]);

/**
 * List accounts.
 *
 * @return HTML.
 */
$router -> get("/api/account/list", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }
    if (!(get_self() -> has_access("admin"))){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Only accounts with an access level of 'admin' can list accounts.");
        return;
    }
    foreach (load_accounts($config["database"]["accounts"]) as $account){
        echo $account -> to_table_row();
    }
});

/**
 * Edit own account.
 * @param $_POST["columns"] - Preferences of columns.
 * @param $_POST["theme"] - Theme chosen.
 *
 * @return Javascript.
 */
$router -> post("/api/account/edit", function(){
    global $config, $logger;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }

    $accounts = load_accounts($config["database"]["accounts"]);
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> id !== $_SESSION["login"]["account"] -> id){
            continue;
        }
        $accounts[$key] -> update_preferences($_POST["columns"], $_POST["theme"]);
        $_SESSION["login"]["account"] = $accounts[$key];
    }

    $logger -> log(new Edit_Event($_SERVER["REMOTE_ADDR"], get_self() -> get_username(), "account", ["Display Name" => get_self() -> display_name, "Username" => get_self() -> get_username(), "ID" => get_self() -> id], ["Columns", "Theme"]));

    save_accounts($accounts, $config["database"]["accounts"]);
    echo "$(\"tbody\").html(\"" . $_SESSION["login"]["account"] -> table_header(true). "\");";
}, ["columns" => true, "theme" => true]);

/**
 * Return the last modified time of all accounts.
 *
 * @return string - UTC/Epoch account modification time.
 */
$router -> get("/api/kid/poll", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }

    if (!file_exists($config["database"]["kids"])){
        file_put_contents($config["database"]["kids"], serialize([]));
    }
    echo filemtime($config["database"]["kids"]);
    return;
});

/**
 * Get Javascript code to create table row for all kids modified after provided UTC/Epoch time.
 * @param  $_POST["since"] - Last time the client has received data.
 * @param  $_POST["first"] - Is this the first request for data.
 * @param  $_POST["editing"] - List of kid ids that are currently being edited.
 * @param  $_POST["hidden"] - Should hidden kids be rendered, or not.
 *
 * @return string - Javascript code to be evaluated by the client.
 */
$router -> post("/api/kid/list", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }

    $editing = explode(",", $_POST["editing"]);
    foreach (load_kids($config["database"]["kids"]) as $kid){
        if ($kid -> modification_time < $_POST["since"] || in_array($kid -> id, $editing)){
            continue;
        }
        if ($_POST["first"] === "true"){
            $code ="$(\"#kid_table_body > tr\").last().after(\"" . $kid -> to_table_row(false, get_self()) . "\")\n";
        }
        else{
            $code = "$(\"#kid_table_body > tr#" . $kid -> id . "\").replaceWith(\"" . $kid -> to_table_row(false, get_self()) . "\")\n";
        }
        if (array_key_exists("hidden", $_POST)){
            if (get_self() -> has_atleast_access("mod")){
                if ($kid -> is_hidden()){
                    echo $code;
                    continue;
                }
                continue;
            }
            continue;
        }
        if (!($kid -> is_hidden())){
            echo $code;
        }
    }
}, ["since" => true, "first" => true, "editing" => true, "hidden" => false]);

/**
 * Get HTML table row of kid with the provided ID.
 * @param  $_POST["id"] - ID of kid to get HTML table row for.
 * @param  $_POST["edit"] - Should the HTML table row be editable or not.
 *
 * @return string - HTML table row.
 */
$router -> post("/api/kid/get", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }
    $kid = get_kid($_POST["id"], $config["database"]["kids"]);
    if ($kid -> is_hidden()){
        if (get_self() -> has_access("mod")){
            echo $kid -> to_table_row($_POST["edit"] === "true", get_self());
            return;
        }
        header("HTTP/1.0 401 Unauthorized");
        error_page("Only accounts with an access level of 'mod' can view hidden kids.");
        return;
    }
    echo $kid -> to_table_row($_POST["edit"] === "true", get_self());
}, ["id" => true, "edit" => true]);

/**
 * Add new kid.
 * @param  $_POST["first_name"] - First name of new kid.
 * @param  $_POST["last_name"] - Last name of new kid.
 * @param  $_POST["parents"] - Parent(s) of new kid.
 * @param  $_POST["status"] - Status of new kid.
 *
 * @return string - Javascript code to be ran by the client.
 */
$router -> post("/api/kid/add", function(){
    global $config, $logger;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }

    $kids = load_kids($config["database"]["kids"]);

    $new_kid = new Kid(generate_id($config["database"]["ids"]), $_POST["first_name"], $_POST["last_name"], $_POST["parents"], $_POST["status"]);

    $logger -> log(new Creation_Event($_SERVER["REMOTE_ADDR"], get_self() -> get_username(), "kid", ["Full Name" => $new_kid -> get_full_name(), "ID" => $new_kid -> id]));

    array_push($kids, $new_kid);
    save_kids($kids, $config["database"]["kids"]);
    echo "$(\"tr\").last().after(\"" . $new_kid -> to_table_row(false, get_self()) . "\")\n";
}, ["first_name" => true, "last_name" => true, "parents" => true, "status" => true]);

/**
 * Delete a kid.
 * @param $_POST["id"] - ID of kid to delete.
 *
 * @return string - HTML code for table row.
 */
$router -> post("/api/kid/delete", function(){
    global $config, $logger;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }

    if (!(get_self() -> has_access("admin"))){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Only accounts with an access level of 'admin' can delete accounts.");
        return;
    }
    $kid = get_kid($_POST["id"], $config["database"]["kids"]);

    $logger -> log(new Deletion_Event($_SERVER["REMOTE_ADDR"], get_self() -> get_username(), "kid", ["Full Name" => $kid -> get_full_name(), "ID" => $kid -> id]));

    $kids = load_kids($config["database"]["kids"]);
    unset($kids[array_search($kid, $kids)]);
    save_kids($kids, $config["database"]["kids"]);
    echo "";
}, ["id" => true]);

/**
 * Change the status of a kid.
 * @param  $_POST["id"] - ID of kid status being changed.
 * @param  $_POST["status"] - New status of kid.
 */
$router -> post("/api/kid/change_status", function(){
    global $config, $logger;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }

    $kids = load_kids($config["database"]["kids"]);
    $old_status;
    foreach ($kids as $key => $_){
        if ($kids[$key] -> id != $_POST["id"]){
            continue;
        }
        $old_status = $kids[$key] -> status;
        $kids[$key] -> update_value("status", $_POST["status"]);
        $logger -> log(new Kid_Status_Change_Event($_SERVER["REMOTE_ADDR"], get_self() -> get_username(), ["Full Name" => $kids[$key] -> get_full_name(), "ID" => $kids[$key] -> id], $old_status, $_POST["status"]));
    }
    save_kids($kids, $config["database"]["kids"]);
}, ["id" => true, "status" => true]);

/**
 * Edit kid information.
 * @param $_POST["id"] - ID of kid to change information of.
 * @param $_POST["first_name"] - New first name of kid.
 * @param $_POST["last_name"] - New last name of kid.
 * @param $_POST["full_name"] - New full name of kid.
 * @param $_POST["parents"] - New parents of kid.
 * @param $_POST["hidden"] - New hidden status of kid.
 *
 * @return string - HTML code for table row.
 */
$router -> post("/api/kid/edit", function(){
    global $config, $logger;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }
    $changed_fields = [];

    $kids = load_kids($config["database"]["kids"]);
    foreach ($kids as $kid_key => $kid){
        if ($kids[$kid_key] -> id != $_POST["id"]){
            continue;
        }
        foreach ($_POST as $data_key => $value){
            if ($data_key === "id"){
                continue;
            }
            if ($data_key === "hidden"){
                if (get_self() -> has_atleast_access("mod")){
                    if ($kids[$kid_key] -> update_value($data_key, $value)){
                        array_push($changed_fields, $data_key);
                    }
                }
                else{
                    header("HTTP/1.0 401 Unauthorized");
                }
            }
            else{
                if ($kids[$kid_key] -> update_value($data_key, $value)){
                    array_push($changed_fields, $data_key);
                }
            }
        }
        error_log(print_r($changed_fields, true));
        $logger -> log(new Edit_Event($_SERVER["REMOTE_ADDR"], get_self() -> get_username(), "kid", ["Full Name" => $kids[$kid_key] -> get_full_name(), "ID" => $kids[$kid_key] -> id], $changed_fields));
    }

    save_kids($kids, $config["database"]["kids"]);
    if (array_key_exists("hidden", $_POST)){
        echo "";
        return;
    }
    echo get_kid($_POST["id"], $config["database"]["kids"]) -> to_table_row(false, get_self());
}, ["id" => true, "first_name" => false, "last_name" => false, "parents" => false, "full_name" => false, "hidden" => false]);

/**
 * Get Javascript code to create table row for all kids modified after provided UTC/Epoch time.
 * @param  $_POST["since"] - Last time the client has received data.\
 *
 * @return string - Javascript code to be evaluated by the client.
 */
$router -> post("/api/history/list", function(){
    global $config, $logger;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }

    $history = $logger -> get_all_history();

    function compare_time($a, $b){
        return $b -> time - $a -> time;
    }
    usort($history, "compare_time");
    foreach ($history as $event){
        if ($_POST["first"] === "true"){
            $code ="$(\"#history_table_body > tr\").last().after(\"" . $event -> to_table_row() . "\")\n";
        }
        else{
            $code = "$(\"#history_table_body > tr#" . $event -> id . "\").replaceWith(\"" . $event -> to_table_row() . "\")\n";
        }
        echo $code;
    }
}, ["since" => true]);

/**
 * Return the last modified time of the latest log file.
 *
 * @return string - UTC/Epoch account modification time.
 */
$router -> get("/api/history/poll", function(){
    global $logger;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        error_page("Must be logged in");
        return;
    }

    if (!file_exists($logger -> current_filepath)){
        file_put_contents($logger -> current_filepath, serialize([]));
    }
    echo filemtime($logger -> current_filepath);
    return;
});

session_start();

$router -> match($_SERVER, $_POST);

?>