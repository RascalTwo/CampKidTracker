<?php
require_once __DIR__ . "/resources/config.php";
require_once $config["class"]["router"];
require_once $config["class"]["account"];
require_once $config["class"]["kid"];
require_once $config["class"]["logging"];
require_once $config["class"]["utility"];

$router = new Router;

$logger = new Logger($config["path"]["logs"]);

#2/3
function logged_in(){
    return (array_key_exists("login", $_SESSION) && $_SESSION["login"]["account"] -> last_online + (60 * 60) > time());
}

#2/3
function get_self(){
    if (array_key_exists("login", $_SESSION)){
        return $_SESSION["login"]["account"];
    }
    return;
}

#1/3
function valid_access($required_access){
    switch ($required_access) {
        case "admin":
            return ($_SESSION["login"]["account"] -> access_level === "admin");
            break;

        case "mod":
            return ($_SESSION["login"]["account"] -> access_level === "admin" || $_SESSION["login"]["account"] -> access_level === "mod");
            break;

        case "user":
            return true;
            break;
    }
}

function redirect($path){
    echo "<script type='text/javascript'>window.location = 'http://" . $_SERVER["HTTP_HOST"] . $path . "'</script>";
}

#3/3
$router -> get("/", function(){
    global $config;
    if (!logged_in()){
        unset($_SESSION["login"]);
        include $config["templates"]["login"];
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
        include $config["templates"]["login"];
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
        include $config["templates"]["login"];
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
        include $config["templates"]["login"];
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
        include $config["templates"]["login"];
        return;
    }
    include $config["templates"]["history"];
    return;
});

#3/3
$router -> post("/api/account/login", function(){
    global $config, $logger;

    $logged_in = false;
    $accounts = load_accounts($config["database"]["accounts"]);
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> username_match($_POST["username"])){
            if ($accounts[$key] -> password_match($_POST["password"])){
                $logged_in = true;
                $accounts[$key] -> login();
                $_SESSION["login"]["account"] = $accounts[$key];
                $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"], $accounts[$key], true);
            }
            else{
                $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"], $accounts[$key], false);
            }
        }
    }

    if (!isset($event)){
        $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"], $_POST["username"], false);
    }

    $logger -> log($event);

    if ($logged_in){
        save_accounts($accounts, $config["database"]["accounts"]);
        echo "window.location.reload();";
        return;
    }

    echo "$('#login_response').html('Incorrect Username or Password!')";
    return;
}, ["username" => true, "password" => true]);

#1/3
$router -> post("/api/account/create", function(){
    global $config;
    if (!array_key_exists("force", $_POST) && !logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }
    if (!array_key_exists("force", $_POST) && !(get_self() -> has_access("admin"))){
        header("HTTP/1.0 403 Forbidden");
        return;
    }
    $accounts = load_accounts($config["database"]["accounts"]);
    $new_account = new Account(generate_id($config["database"]["ids"]), $_POST["display_name"], strtolower($_POST["username"]), $_POST["password"], $_POST["access_level"]);
    foreach ($accounts as $account) {
        if ($account -> get_username() !== $new_account -> get_username()){
            continue;
        }
        echo "$('#account_creation_response').html('Username already exists!');";
        return;
    }
    array_push($accounts, $new_account);
    save_accounts($accounts, $config["database"]["accounts"]);
    echo "$('#create_response').html('Account Successfully Created!');";
}, ["display_name" => true, "username" => true, "password" => true, "access_level" => true]);

#0/3
$router -> post("/api/account/view", function(){
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }
}, ["id" => true]);

#2/3
$router -> post("/api/account/edit", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }

    $accounts = load_accounts($config["database"]["accounts"]);
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> get_id() !== $_SESSION["login"]["account"] -> get_id()){
            continue;
        }
        $accounts[$key] -> update_preferences(json_decode($_POST["columns"], true), $_POST["theme"]);
        $_SESSION["login"]["account"] = $accounts[$key];
    }
    save_accounts($accounts, $config["database"]["accounts"]);
    echo "$(\"tbody\").html(\"" . $_SESSION["login"]["account"] -> table_header(true). "\");";
}, ["columns" => true, "theme" => true]);

#2/3
$router -> get("/api/kid/poll", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }

    if (!file_exists($config["database"]["kids"])){
        file_put_contents($config["database"]["kids"], serialize([]));
    }
    echo filemtime($config["database"]["kids"]);
    return;
});

#0/3
$router -> get("/api/kid/list", function(){
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }
});

#1/3
$router -> post("/api/kid/changed", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }

    $editing = json_decode($_POST["editing"]);
    foreach (load_kids($config["database"]["kids"]) as $kid){
        if ($kid -> modification_time < $_POST["since"] || in_array($kid -> id, $editing)){
            continue;
        }
        if ($_POST["first"] === "true"){
            echo "$(\"tr\").last().after(\"" . $kid -> to_table_row(false, $_SESSION["login"]["account"]) . "\")\n";
        }
        else{
            echo "$(\"tr#" . $kid -> id . "\").replaceWith(\"" . $kid -> to_table_row(false, $_SESSION["login"]["account"]) . "\")\n";
        }
    }
    return;
}, ["since" => true, "first" => true, "editing" => true]);

#2/3
$router -> post("/api/kid/get", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }

    if ($_POST["type"] === "row"){
        echo get_kid($_POST["id"], $config["database"]["kids"]) -> to_table_row($_POST["edit"] === "true", $_SESSION["login"]["account"]);
    }
}, ["id" => true, "type" => true, "edit" => true]);

#2/3
$router -> post("/api/kid/add", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }

    $kids = load_kids($config["database"]["kids"]);
    $new_kid = new Kid(generate_id($config["database"]["ids"]), $_POST["first_name"], $_POST["last_name"], $_POST["parents"], $_POST["status"]);
    array_push($kids, $new_kid);
    save_kids($kids, $config["database"]["kids"]);
    echo "$(\"tr\").last().after(\"" . $new_kid -> to_table_row(false, get_self()) . "\")\n";
}, ["first_name" => true, "last_name" => true, "parents" => true, "status" => true, ]);

#2/3
$router -> post("/api/kid/delete", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }

    if (!(get_self() -> has_access("admin"))){
        echo get_kid($_POST["id"], $config["database"]["kids"]) -> to_table_row(true, get_self());
        header("HTTP/1.0 401 Unauthorized");
        return;
    }
    $kids = load_kids($config["database"]["kids"]);
    unset($kids[array_search(get_kid($_POST["id"], $config["database"]["kids"]), $kids)]);
    save_kids($kids, $config["database"]["kids"]);
    echo "";
}, ["id" => true]);

#2/3
$router -> post("/api/kid/change_status", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }

    $kids = load_kids($config["database"]["kids"]);
    foreach ($kids as $key => $_){
        if ($kids[$key] -> id != $_POST["id"]){
            continue;
        }
        $kids[$key] -> update_value("status", $_POST["status"]);
    }
    save_kids($kids, $config["database"]["kids"]);
}, ["id" => true, "status" => true]);

#2/3
$router -> post("/api/kid/edit", function(){
    global $config;
    if (!logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        return;
    }

    $kids = load_kids($config["database"]["kids"]);
    foreach ($kids as $kid_key => $kid){
        if ($kids[$kid_key] -> id != $_POST["id"]){
            continue;
        }
        foreach ($_POST as $data_key => $value){
            $kids[$kid_key] -> update_value($data_key, $value);
        }
    }
    save_kids($kids, $config["database"]["kids"]);
    echo get_kid($_POST["id"], $config["database"]["kids"]) -> to_table_row(false, $_SESSION["login"]["account"]);
}, ["id" => true, "status" => true, "first_name" => false, "last_name" => false, "parents" => false, "full_name" => false]);

#1/3
$router -> get("/api/log/view/all", function(){
    global $logger;
    if (logged_in()){
        foreach ($logger -> get_history() as $event) {
            echo $event . "\n";
        }
        return;
    }
    header("HTTP/1.0 401 Unauthorized");
});

session_start();

$router -> match($_SERVER, $_POST);

?>