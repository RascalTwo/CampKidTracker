<?php
require_once __DIR__ . "/resources/config.php";
require_once $config["class"]["router"];
require_once $config["class"]["account"];
require_once $config["class"]["group"];
require_once $config["class"]["kid"];
require_once $config["class"]["logging"];
require_once $config["class"]["utility"];

$router = new Router($config["path"]["assets"], $config["path"]["templates"]);
$logger = new Logger($config["path"]["logs"]);

function is_logged_in(){
    global $config;
    $self = get_self();
    if ($self === NULL){
        return false;
    }
    if ($self -> last_online + (60 * 60) <= time()){
        return false;
    }
    foreach (load_accounts($config["database"]["accounts"]) as $account){
        if ($self -> id !== $account -> id){
            continue;
        }
        return true;
    }
    return false;
}

function get_self(){
    if (array_key_exists("login", $_SESSION)){
        return $_SESSION["login"]["account"];
    }
    return NULL;
}

$router -> get("/", function($router){
    if (!is_logged_in()){
        unset($_SESSION["login"]);
        $router -> redirect("/login");
        return;
    }
    $router -> redirect("/dashboard");
    return;
});

$router -> get("/login", function($router){
    $router -> show_template("login", "Login");
    return;
});

$router -> get("/dashboard", function($router){
    if (!is_logged_in()){
        unset($_SESSION["login"]);
        $router -> redirect("/login");
        return;
    }
    $router -> show_template("dashboard", "Dashboard", ["kid_table"]);
    return;
});

$router -> get("/groups", function($router){
    if (!is_logged_in()){
        unset($_SESSION["login"]);
        $router -> redirect("/login");
        return;
    }
    $router -> show_template("groups", "Groups");
    return;
});

$router -> get("/preferences", function($router){
    if (!is_logged_in()){
        unset($_SESSION["login"]);
        $router -> redirect("/login");
        return;
    }
    $router -> show_template("preferences", "Preferences", ["utility"]);
    return;
});

$router -> get("/admin", function($router){
    if (!is_logged_in()){
        unset($_SESSION["login"]);
        $router -> redirect("/login");
        return;
    }
    if (get_self() -> has_access("admin")){
        $router -> show_template("admin", "Admin", ["kid_table"]);
        return;
    }
    header("HTTP/1.0 401 Unauthorized");
    $router -> show_error_page("Only accounts with admin access can access this resource.", "401 Unauthorized");
    return;
});

$router -> get("/history", function($router){
    if (!is_logged_in()){
        unset($_SESSION["login"]);
        $router -> redirect("/login");
        return;
    }
    $router -> show_template("history", "History");
    return;
});

$router -> get("/about", function($router){
    $router -> show_template("about", "About");
    return;
});

/**
 * Login to an account.
 * @param $_POST["username"] - Username of account logging into.
 * @param $_POST["password"] - Password of account logging into.
 *
 * @return Boolean result.
 */
$router -> post("/api/account/login", function($router){
    global $config, $logger;

    $accounts = load_accounts($config["database"]["accounts"]);
    if (count($accounts) === 0){
        $new_account = new Account(generate_id($config["database"]["ids"]), "Owner", strtolower($_POST["username"]), $_POST["password"], "admin");
        array_push($accounts, $new_account);
    }

    $logged_in = false;
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> username_match($_POST["username"])){
            if ($accounts[$key] -> password_match($_POST["password"])){
                $logged_in = true;
                $accounts[$key] -> login();
                $_SESSION["login"]["account"] = $accounts[$key];
                $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"], $accounts[$key] -> username, true);
            }
            else{
                $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"], $accounts[$key] -> username, false);
            }
        }
    }

    if (!isset($event)){
        $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"], strtolower($_POST["username"]), false);
    }

    $logger -> log($event);

    if ($logged_in){
        save_accounts($accounts, $config["database"]["accounts"]);
        echo json_encode([
            "success" => true,
            "message" => "Logged in successfully!",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/dashboard"
        ]);
        return;
    }

    echo json_encode([
        "success" => false,
        "message" => "Invalid login username or password!"
    ]);
    return;
}, ["username" => true, "password" => true]);

/**
 * Create an account.
 * @param $_POST["display_name"] - Display name of new account.
 * @param $_POST["username"] - Username of new account.
 * @param $_POST["password"] - Password of new account.
 * @param $_POST["access_level"] - Access level of new account.
 *
 * @return Text message.
 */
$router -> post("/api/account/create", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in.", "401 Unauthorized");
        return;
    }
    if (!get_self() -> has_access("admin")){
        header("HTTP/1.0 403 Forbidden");
        $router -> show_error_page("Only accounts with an access level of 'admin' can create accounts.", "403 Forbidden");
        return;
    }

    $new_account = new Account(generate_id($config["database"]["ids"]), $_POST["display_name"], strtolower($_POST["username"]), $_POST["password"], $_POST["access_level"]);

    $accounts = load_accounts($config["database"]["accounts"]);
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> username !== $new_account -> username){
            continue;
        }
        $logger -> log(new Custom_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "Failed to create account", "Username '" . $new_account -> username . "' already exists"));
        echo json_encode([
            "success" => false,
            "message" => "Username '" . $_POST["username"] . "' already exists!"
        ]);
        return;
    }

    $logger -> log(new Creation_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "account", ["Display Name" => $new_account -> display_name, "Username" => $new_account -> username, "ID" => $new_account -> id]));

    $accounts[] = $new_account;
    save_accounts($accounts, $config["database"]["accounts"]);
    echo json_encode([
        "success" => false,
        "message" => "Account Successfully Created!"
    ]);
    return;
}, ["display_name" => true, "username" => true, "password" => true, "access_level" => true]);

/**
 * Delete an account.
 * @param $_POST["id"] - ID of account to delete.
 */
$router -> post("/api/account/delete", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }
    if (!(get_self() -> has_access("admin"))){
        header("HTTP/1.0 403 Forbidden");
        $router -> show_error_page("Only accounts with an access level of 'admin' can delete accounts.", "403 Forbidden");
        return;
    }
    if (get_self() -> id == $_POST["id"]){
        header("HTTP/1.0 400 Bad Request");
        $router -> show_error_page("You can not delete your own account.", "400 Bad Request");
        return;
    }
    $account = get_account($_POST["id"], $config["database"]["accounts"]);

    $logger -> log(new Deletion_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "account", ["Display Name" => $account -> display_name, "Username" => $account -> username, "ID" => $account -> id]));

    $accounts = load_accounts($config["database"]["accounts"]);
    unset($accounts[array_search($account, $accounts)]);
    save_accounts($accounts, $config["database"]["accounts"]);
    echo json_encode([
        "success" => true,
        "message" => "Account deleted!"
    ]);
    return;
}, ["id" => true]);

/**
 * List accounts.
 *
 * @return HTML.
 */
$router -> get("/api/account/list", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }
    if (!(get_self() -> has_access("admin"))){
        header("HTTP/1.0 403 Forbidden");
        $router -> show_error_page("Only accounts with an access level of 'admin' can list accounts.", "403 Forbidden");
        return;
    }
    $response = [];
    foreach (load_accounts($config["database"]["accounts"]) as $account){
        $response[] = $account -> to_table_row();
    }
    echo json_encode($response);
    return;
});

/**
 * Edit own account.
 * @param $_POST["columns"] - Preferences of columns.
 * @param $_POST["theme"] - Theme chosen.
 *
 * @return HTML.
 */
$router -> post("/api/account/edit", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }

    $changed_fields = [];

    $accounts = load_accounts($config["database"]["accounts"]);
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> id !== get_self() -> id){
            continue;
        }
        foreach ($_POST as $data_key => $value){
            if ($accounts[$key] -> update_preference($data_key, $value)){
                $changed_fields[] = $data_key;
            }
        }
        $_SESSION["login"]["account"] = $accounts[$key];
        break;
    }

    $logger -> log(new Edit_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "account", ["Display Name" => get_self() -> display_name, "Username" => get_self() -> username, "ID" => get_self() -> id], $changed_fields));

    save_accounts($accounts, $config["database"]["accounts"]);
    echo json_encode([
        "success" => true,
        "message" => count($changed_fields) > 0 ? "Preferences updated." : "No preferences updated.",
        "html" => $_SESSION["login"]["account"] -> table_header(true)
    ]);
    return;
}, ["columns" => false, "theme" => false, "emails" => false, "phones" => false]);

/**
 * Return the last modified time of all accounts.
 *
 * @return string - UTC/Epoch account modification time.
 */
$router -> get("/api/kid/poll", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }

    echo filemtime($config["database"]["kids"]);
    return;
});

/**
 * Get Javascript code to create table row for all kids modified after provided UTC/Epoch time.
 * @param  $_POST["since"] - Last time the client has received data.
 * @param  $_POST["append"] - Is this the append request for data.
 * @param  $_POST["editing"] - List of kid ids that are currently being edited.
 * @param  $_POST["hidden"] - Should hidden kids be rendered, or not.
 *
 * @return JSON array of dicts with action and HTML code.
 */
$router -> post("/api/kid/list", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }

    $response = [];
    $groups = load_groups($config["database"]["groups"]);
    $editing = comma_split_to_array($_POST["editing"]);
    foreach (load_kids($config["database"]["kids"]) as $kid){
        if ($kid -> modification_time < $_POST["since"] || in_array($kid -> id, $editing)){
            continue;
        }
        $kid_resp = [
            "id" => $kid -> id,
            "html" => $kid -> to_table_row(false, get_self(), $groups)
        ];
        if (array_key_exists("hidden", $_POST) && get_self() -> has_access("mod")){
            if (!($kid -> hidden)){
                continue;
            }
            $response[] = $kid_resp;
        }
        if (!($kid -> hidden)){
            $response[] = $kid_resp;
        }
    }
    echo json_encode($response);
    return;
}, ["since" => true, "append" => true, "editing" => true, "hidden" => false]);

/**
 * Get HTML table row of kid with the provided ID.
 * @param  $_POST["id"] - ID of kid to get HTML table row for.
 * @param  $_POST["edit"] - Should the HTML table row be editable or not.
 *
 * @return string - HTML table row.
 */
$router -> post("/api/kid/get", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }
    $groups = load_groups($config["database"]["groups"]);
    $kid = get_kid($_POST["id"], $config["database"]["kids"]);
    if ($kid -> hidden){
        if (get_self() -> has_access("mod")){
            echo json_encode([
                "success" => true,
                "html" => $kid -> to_table_row($_POST["edit"], get_self(), $groups)
            ]);
            return;
        }
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Only accounts with an access level of 'mod' can view hidden kids.", "401 Unauthorized");
        return;
    }
    echo json_encode([
        "success" => true,
        "html" => $kid -> to_table_row($_POST["edit"], get_self(), $groups)
    ]);
}, ["id" => true, "edit" => true]);

/**
 * Add new kid.
 * @param  $_POST["first_name"] - First name of new kid.
 * @param  $_POST["last_name"] - Last name of new kid.
 * @param  $_POST["parents"] - Parent(s) of new kid.
 * @param  $_POST["status"] - Status of new kid.
 *
 * @return string - HTML code.
 */
$router -> post("/api/kid/add", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }

    $kids = load_kids($config["database"]["kids"]);

    $new_kid = new Kid(generate_id($config["database"]["ids"]), $_POST["first_name"], $_POST["last_name"], $_POST["parents"], $_POST["status"]);

    $logger -> log(new Creation_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "kid", ["Full Name" => $new_kid -> get_full_name(), "ID" => $new_kid -> id]));

    $kids[] = $new_kid;
    save_kids($kids, $config["database"]["kids"]);
    echo json_encode([
        "success" => true,
        "message" => "Kid successfully added!",
        "html" => $new_kid -> to_table_row(false, get_self())
    ]);
    return;
}, ["first_name" => true, "last_name" => true, "parents" => true, "status" => true]);

/**
 * Delete a kid.
 * @param $_POST["id"] - ID of kid to delete.
 */
$router -> post("/api/kid/delete", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }

    if (!(get_self() -> has_access("admin"))){
        header("HTTP/1.0 403 Forbidden");
        $router -> show_error_page("Only accounts with an access level of 'admin' can delete accounts.", "403 Forbidden");
        return;
    }
    $kid = get_kid($_POST["id"], $config["database"]["kids"]);

    $logger -> log(new Deletion_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "kid", ["Full Name" => $kid -> get_full_name(), "ID" => $kid -> id]));

    $kids = load_kids($config["database"]["kids"]);
    unset($kids[array_search($kid, $kids)]);
    save_kids($kids, $config["database"]["kids"]);
    echo json_encode([
        "success" => true,
        "message" => "Kid '" . $kid -> get_full_name() . "' deleted."
    ]);
    return;
}, ["id" => true]);

/**
 * Change the status of a kid.
 * @param  $_POST["id"] - ID of kid status being changed.
 * @param  $_POST["status"] - New status of kid.
 */
$router -> post("/api/kid/change_status", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
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
        $logger -> log(new Kid_Status_Change_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, ["Full Name" => $kids[$key] -> get_full_name(), "ID" => $kids[$key] -> id], ucfirst($old_status), ucfirst($_POST["status"])));
        if (count($kids[$key] -> groups) === 0){
            break;
        }
        foreach (load_groups($config["database"]["groups"]) as $group){
            if (!(array_key_exists($group -> id, $kids[$key] -> groups))){
                continue;
            }
            foreach (load_accounts($config["database"]["accounts"]) as $account){
                if ($account -> id === get_self() -> id or (!(array_key_exists($account -> id, $group -> leaders)))){
                    continue;
                }
                $account -> contact($kid_id);
            }
        }
        break;
    }
    save_kids($kids, $config["database"]["kids"]);
    echo json_encode([
        "message" => "Status of '" . $kids[$key] -> get_full_name() . "' changed from '" . ucfirst($old_status) . "' to '" . ucfirst($kids[$key] -> status) . "'"
    ]);
    return;
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
$router -> post("/api/kid/edit", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }
    $changed_fields = [];

    $kids = load_kids($config["database"]["kids"]);
    foreach ($kids as $kid_key => $_){
        if ($kids[$kid_key] -> id != $_POST["id"]){
            continue;
        }
        foreach ($_POST as $data_key => $value){
            switch ($data_key){
                case "id":
                    break;

                case "hidden":
                    if (get_self() -> has_access("mod")){
                        if ($kids[$kid_key] -> update_value($data_key, $value)){
                            $changed_fields[] = ucfirst($data_key);
                        }
                    }
                    else{
                        header("HTTP/1.0 403 Forbidden");
                        $router -> show_error_page("Only accounts with access level of 'mod' can change child hidden status", "403 Forbidden");
                    }
                    break;

                case "group":
                    if ($kids[$kid_key] -> update_value($data_key, $value)){

                    }
                    break;

                default:
                    if ($kids[$kid_key] -> update_value($data_key, $value)){
                        $changed_fields[] = ucfirst($data_key);
                    }
                    break;
            }
        }
        $logger -> log(new Edit_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "kid", ["Full Name" => $kids[$kid_key] -> get_full_name(), "ID" => $kids[$kid_key] -> id], $changed_fields));
        break;
    }

    save_kids($kids, $config["database"]["kids"]);
    echo json_encode([
        "success" => true,
        "message" => "Kid '" . $kids[$kid_key] -> get_full_name() . "' edited.",
        "html" => get_kid($_POST["id"], $config["database"]["kids"]) -> to_table_row(false, get_self(), [])
    ]);
    return;
}, ["id" => true, "first_name" => false, "last_name" => false, "parents" => false, "full_name" => false, "hidden" => false]);

$router -> post("/api/group/create", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in.", "401 Unauthorized");
        return;
    }
    if (!get_self() -> has_access("mod")){
        header("HTTP/1.0 403 Forbidden");
        $router -> show_error_page("Only accounts with an access level of 'mod' can create groups.", "403 Forbidden");
        return;
    }

    $new_group = new Group(generate_id($config["database"]["ids"]), $_POST["name"]);

    $groups = load_groups($config["database"]["groups"]);
    foreach ($groups as $key => $_){
        if ($groups[$key] -> name !== $new_group -> name){
            continue;
        }
        $logger -> log(new Custom_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "Failed to create group", "Group named '" . $new_group -> name . "' already exists"));
        echo json_encode([
            "success" => false,
            "message" => "Group name '" . $_POST["name"] . "' already exists!"
        ]);
        return;
    }

    $logger -> log(new Creation_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "group", ["Name" => $new_group -> name]));

    $groups[] = $new_group;
    save_groups($groups, $config["database"]["groups"]);
    echo json_encode([
        "success" => false,
        "message" => "Group Successfully Created!"
    ]);
    return;
}, ["name" => true]);


$router -> post("/api/group/delete", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }

    if (!(get_self() -> has_access("mod"))){
        header("HTTP/1.0 403 Forbidden");
        $router -> show_error_page("Only accounts with an access level of 'mod' can delete groups.", "403 Forbidden");
        return;
    }
    $group = get_group($_POST["id"], $config["database"]["groups"]);

    $logger -> log(new Deletion_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "group", ["Name" => $group -> name, "ID" => $group -> id]));

    $groups = load_groups($config["database"]["groups"]);
    unset($groups[array_search($group, $groups)]);
    save_groups($groups, $config["database"]["groups"]);
    echo json_encode([
        "success" => true,
        "message" => "Group '" . $group -> name . "' deleted."
    ]);
    return;
}, ["id" => true]);


$router -> post("/api/group/get", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }

    $group = get_group($_POST["id"], $config["database"]["groups"]);

    $response = [
        "success" => true,
        "html" => $group -> to_table_row($_POST["edit"]),
        "kids" => [],
        "accounts" => []
    ];

    $kids = load_kids($config["database"]["kids"]);
    $accounts = load_accounts($config["database"]["accounts"]);

    foreach ($group -> kids as $kid_id){
        $response["kids"][] = get_kid($kids) -> to_table_row(false, get_self(), []);
    }
    foreach ($group -> leaders as $account_id){
        $response["accounts"][] = get_account($accounts) -> to_table_row();
    }
    echo json_encode($response);
    return;
}, ["id" => true, "edit" => true]);

$router -> get("/api/group/list", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }
    $response = [];
    foreach (load_groups($config["database"]["groups"]) as $group){
        $response[] = $group -> to_table_row(false);
    }
    echo json_encode($response);
    return;
});

$router -> post("/api/group/edit", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }

    if (!(get_self() -> has_access("mod"))){
        header("HTTP/1.0 403 Forbidden");
        $router -> show_error_page("Only accounts with an access level of 'mod' can edit groups.", "403 Forbidden");
        return;
    }

    $groups = load_groups($config["database"]["groups"]);

    foreach ($groups as $group_key => $_){
        if ($groups[$group_key] -> id != $_POST["id"]){
            continue;
        }
        foreach ($_POST as $data_key => $value){
            switch ($data_key){
                case "id":
                    break;

                default:
                    $groups[$group_key] -> update_value($data_key, $value);
                    break;
            }
        }
        $logger -> log(new Edit_Event($_SERVER["REMOTE_ADDR"], get_self() -> username, "group", ["Name" => $groups[$group_key] -> name, "ID" => $groups[$group_key] -> id]));
        break;
    }

    save_groups($groups, $config["database"]["groups"]);
    echo json_encode([
        "success" => true,
        "message" => "Group '" . $groups[$group_key] -> name . "' edited.",
        "html" => get_group($_POST["id"], $config["database"]["groups"]) -> to_table_row(false)
    ]);
    return;
}, ["id" => true, "name" => false, "kids" => false, "leaders" => false]);

/**
 * Get Javascript code to create table row for all kids modified after provided UTC/Epoch time.
 * @param  $_POST["since"] - Last time the client has received data.\
 *
 * @return string - Javascript code to be evaluated by the client.
 */
$router -> post("/api/history/list", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }

    $response = [];

    $history = $logger -> get_all_history();

    function compare_time($a, $b){
        return $b -> time - $a -> time;
    }
    usort($history, "compare_time");
    foreach ($history as $event){
        $response[] = [
            "html" => $event -> to_table_row()
        ];
    }
    echo json_encode($response);
    return;
}, ["since" => true]);

/**
 * Return the last modified time of the latest log file.
 *
 * @return string - UTC/Epoch account modification time.
 */
$router -> get("/api/history/poll", function($router){
    global $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        $router -> show_error_page("Must be logged in", "401 Unauthorized");
        return;
    }
    echo filemtime($logger -> current_filepath);
    return;
});

session_start();

$router -> match();

?>