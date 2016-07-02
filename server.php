<?php
require_once __DIR__ . "/resources/config.php";
require_once $config["class"]["router"];
require_once $config["class"]["utility"];
require_once $config["class"]["account"];
require_once $config["class"]["group"];
require_once $config["class"]["kid"];
require_once $config["class"]["logging"];

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
    foreach (load_data($config["database"]["accounts"]) as $account){
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

function as_json($database, $filter=NULL){
    global $config;
    $data = load_data($config["database"][$database]);
    if ($filter !== NULL){
        $data = array_filter($data, $filter);
    }
    return json_encode(array_values($data));
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
    $router -> show_template("dashboard", "Dashboard", ["kid_table", "utility"]);
    return;
});

$router -> get("/groups", function($router){
    if (!is_logged_in()){
        unset($_SESSION["login"]);
        $router -> redirect("/login");
        return;
    }
    $router -> show_template("groups", "Groups", ["kid_table", "utility"]);
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
        $router -> show_template("admin", "Admin", ["kid_table", "utility"]);
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
 * @param string $_POST 'username' - Username of account logging into.
 * @param string $_POST 'password' - Password of account logging into.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     string  'redirect' - URL to redirect to.
 */
$router -> post("/api/account/login", function($router){
    global $config, $logger;

    $accounts = load_data($config["database"]["accounts"]);
    if (count($accounts) === 0){
        $accounts[] = new Account(generate_id($config["database"]["ids"]),
                                  "Owner",
                                  strtolower($_POST["username"]),
                                  $_POST["password"],
                                  "admin");
    }

    $logged_in = false;
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> username_match($_POST["username"])){
            if ($accounts[$key] -> password_match($_POST["password"])){
                $logged_in = true;
                $accounts[$key] -> last_online = time();
                $_SESSION["login"]["account"] = $accounts[$key];
                $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"],
                                                 $accounts[$key] -> username,
                                                 true);
            }
            else{
                $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"],
                                                 $accounts[$key] -> username,
                                                 false);
            }
            break;
        }
    }

    if (!isset($event)){
        $event = new Account_Login_Event($_SERVER["REMOTE_ADDR"],
                                         strtolower($_POST["username"]),
                                         false);
    }

    $logger -> log($event);

    if ($logged_in){
        save_data($accounts, $config["database"]["accounts"]);
        header("HTTP/1.0 200 OK");
        echo json_encode([
            "success" => true,
            "message" => "Logged in successfully!",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/dashboard"
        ]);
        return;
    }
    header("HTTP/1.0 403 Forbidden");
    echo json_encode([
        "success" => false,
        "message" => "Invalid login username or password!"
    ]);
    return;
}, ["username" => true, "password" => true]);

/**
 * Create an account.
 * @param string $_POST 'display_name' - Display name of new account.
 * @param string $_POST 'username'     - Username of new account.
 * @param string $_POST 'password'     - Password of new account.
 * @param string $_POST 'access_level' - Access level of new account.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 */
$router -> post("/api/account/create", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }
    if (!get_self() -> has_access("admin")){
        header("HTTP/1.0 403 Forbidden");
        echo json_encode([
            "success" => false,
            "message" => "Only accounts with an access level of 'admin' can create accounts."
        ]);
        return;
    }

    $new_account = new Account(generate_id($config["database"]["ids"]),
                               $_POST["display_name"],
                               strtolower($_POST["username"]),
                               $_POST["password"],
                               $_POST["access_level"]);

    $accounts = load_data($config["database"]["accounts"]);
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> username !== $new_account -> username){
            continue;
        }
        $logger -> log(new Custom_Event($_SERVER["REMOTE_ADDR"],
                                        get_self() -> username,
                                        "Failed to create account",
                                        "Username '" . $new_account -> username . "' already exists"));
        header("HTTP/1.0 200 OK");
        echo json_encode([
            "success" => false,
            "message" => "Username '" . $_POST["username"] . "' already exists!"
        ]);
        return;
    }

    $logger -> log(new Creation_Event($_SERVER["REMOTE_ADDR"],
                                      get_self() -> username,
                                      "account",
                                      ["Display Name" => $new_account -> display_name,
                                       "Username" => $new_account -> username,
                                       "ID" => $new_account -> id]));

    add_objects([$new_account], $config["database"]["accounts"], $accounts);
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Account Successfully Created!"
    ]);
    return;
}, ["display_name" => true, "username" => true, "password" => true, "access_level" => true]);

/**
 * Delete an account.
 * @param integer $_POST 'id' - ID of account to delete.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 */
$router -> post("/api/account/delete", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }
    if (!(get_self() -> has_access("admin"))){
        header("HTTP/1.0 403 Forbidden");
        echo json_encode([
            "success" => false,
            "message" => "Only accounts with an access level of 'admin' can delete accounts."
        ]);
        return;
    }
    if (get_self() -> id == $_POST["id"]){
        header("HTTP/1.0 400 Bad Request");
        echo json_encode([
            "success" => false,
            "message" => "You can not delete your own account."
        ]);
        return;
    }
    $account = get_objects([$_POST["id"]], $config["database"]["accounts"]);
    if (count($account) === 0){
        header("HTTP/1.0 404 Not Found");
        echo json_encode([
            "success" => false,
            "message" => "Account with ID of '" . $_POST["id"] . "' not found."
        ]);
        return;
    }
    $account = $account[0];

    $logger -> log(new Deletion_Event($_SERVER["REMOTE_ADDR"],
                                      get_self() -> username,
                                      "account",
                                      ["Display Name" => $account -> display_name,
                                       "Username" => $account -> username,
                                       "ID" => $account -> id]));

    remove_objects([$account], $config["database"]["accounts"]);
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Account deleted."
    ]);
    return;
}, ["id" => true]);

/**
 * List accounts.
 *
 * @see docs/account.md - Account documentation
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     array   'data'    - List of HTML of accounts retrived.
 */
$router -> get("/api/account/list", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }
    $data = [];
    foreach (load_data($config["database"]["accounts"]) as $account){
        $data[] = $account -> to_table_row();
    }
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Accounts loaded.",
        "data" => $data
    ]);
    return;
});

/**
 * Edit own account.
 *
 * @see docs/preferences.md Preferences documentation.
 *
 * @param string $_POST 'columns' - List of all column preferences
 * @param string $_POST 'theme'   - Theme chosen.
 * @param string $_POST 'emails'  - Email addresses.
 * @param string $_POST 'phones'  - Phone numbers.
 *
 * @return string HTML <tr>
 */
$router -> post("/api/account/edit", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    $changed_fields = [];

    $accounts = load_data($config["database"]["accounts"]);
    foreach ($accounts as $key => $_){
        if ($accounts[$key] -> id !== get_self() -> id){
            continue;
        }
        foreach ($_POST as $data_key => $value){
            if ($accounts[$key] -> update_preference($data_key, $value)){
                $changed_fields[] = ucfirst($data_key);
            }
        }
        $_SESSION["login"]["account"] = $accounts[$key];
        break;
    }

    $logger -> log(new Edit_Event($_SERVER["REMOTE_ADDR"],
                                  get_self() -> username,
                                  "account",
                                  ["Display Name" => get_self() -> display_name,
                                   "Username" => get_self() -> username,
                                   "ID" => get_self() -> id],
                                  $changed_fields));

    save_data($accounts, $config["database"]["accounts"]);
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => count($changed_fields) > 0 ? "Preferences updated." : "No preferences updated.",
        "data" => get_self() -> table_header(true)
    ]);
    return;
}, ["columns" => false, "theme" => false, "emails" => false, "phones" => false]);

/**
 * Return the last modified time of all kids.
 *
 * @return integer - UTC/Epoch kids modification time.
 */
$router -> get("/api/kid/poll", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    header("HTTP/1.0 200 OK");
    echo filemtime($config["database"]["kids"]);
    return;
});

/**
 * Get JSON data of all kids.
 * @param integer $_POST 'since'  - Last time the client has received kid list.
 * @param boolean $_POST 'hidden' - Should hidden kids be rendered.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     array   'data'    - List of kids received.
 */
$router -> post("/api/kid/list", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    $response = [];
    $groups = load_data($config["database"]["groups"]);
    foreach (load_data($config["database"]["kids"]) as $kid){
        if ($kid -> modification_time < $_POST["since"]){
            continue;
        }
        if (!$kid -> hidden){
            $response[] = $kid;
        }
        elseif ($kid -> hidden && get_self() -> has_access("mod") && array_key_exists("hidden", $_POST) && $_POST["hidden"]){
            $response[] = $kid;
        }
    }
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "data" => $response
    ]);
    return;
}, ["since" => true, "hidden" => false]);

/**
 * Get JSON data of kid by id.
 * @param integer $_POST 'id' - ID of kid to get JSON data of.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     object  'data'    - Data of kid.
 */
$router -> post("/api/kid/get", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }
    $groups = load_data($config["database"]["groups"]);
    $kid = get_objects([$_POST["id"]], $config["database"]["kids"]);
    if (count($kid) === 0){
        header("HTTP/1.0 404 Not Found");
        echo json_encode([
            "success" => false,
            "message" => "Kid with ID of '" . $_POST["id"] . "' not found."
        ]);
        return;
    }
    $kid = $kid[0];

    if ($kid -> hidden){
        if (get_self() -> has_access("mod")){
            header("HTTP/1.0 200 OK");
            echo json_encode([
                "success" => true,
                "message" => "Kid data retrived.",
                "data" => $kid
            ]);
            return;
        }
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Only accounts with an access level of 'mod' can view hidden kids."
        ]);
        return;
    }
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Kid data retrived.",
        "data" => $kid
    ]);
}, ["id" => true]);

/**
 * Add new kid.
 * @param string $_POST["first_name"] - First name of new kid.
 * @param string $_POST["last_name"]  - Last name of new kid.
 * @param string $_POST["parents"]    - Parents of new kid.
 * @param string $_POST["status"]     - Initial status of new kid.
 * @param string $_POST["group"]      - Group to assign new kid to.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     object   'data'    - Data of new kid.
 */
$router -> post("/api/kid/add", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    $kids = load_data($config["database"]["kids"]);

    $new_kid = new Kid(generate_id($config["database"]["ids"]),
                       $_POST["first_name"],
                       $_POST["last_name"],
                       $_POST["parents"],
                       $_POST["status"],
                       $_POST["group"]);

    $groups = load_data($config["database"]["groups"]);
    foreach ($groups as $key => $_){
        if ($groups[$key] -> id != $new_kid -> id){
            continue;
        }
        $groups[$key] -> add_kid($new_kid -> id);
        break;
    }
    save_data($groups, $config["database"]["groups"]);

    $logger -> log(new Creation_Event($_SERVER["REMOTE_ADDR"],
                                      get_self() -> username,
                                      "kid",
                                      ["Full Name" => $new_kid -> get_full_name(),
                                       "ID" => $new_kid -> id]));

    add_objects([$new_kid], $config["database"]["kids"], $kids);
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Kid successfully added.",
        "data" => $new_kid
    ]);
    return;
}, ["first_name" => true, "last_name" => true, "parents" => true, "status" => true, "group" => true]);

/**
 * Delete a kid.
 * @param integer $_POST 'id' - ID of kid to delete.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 */
$router -> post("/api/kid/delete", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    if (!(get_self() -> has_access("admin"))){
        header("HTTP/1.0 403 Forbidden");
        echo json_encode([
            "success" => false,
            "message" => "Only accounts with an access level of 'admin' can delete accounts."
        ]);
        return;
    }
    $kid = get_objects([$_POST["id"]], $config["database"]["kids"]);
    if (count($kid) === 0){
        header("HTTP/1.0 404 Not Found");
        echo json_encode([
            "success" => false,
            "message" => "Kid with ID of '" . $_POST["id"] . "' not found."
        ]);
        return;
    }
    $kid = $kid[0];

    $logger -> log(new Deletion_Event($_SERVER["REMOTE_ADDR"],
                                      get_self() -> username,
                                      "kid",
                                      ["Full Name" => $kid -> get_full_name(),
                                       "ID" => $kid -> id]));
    remove_objects([$kid], $config["database"]["kids"]);
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Kid with ID of '" . $kid -> id . "' deleted."
    ]);
    return;
}, ["id" => true]);

/**
 * Update the status of a kid.
 * @param integer $_POST 'id'     - ID of kid status being changed.
 * @param string $_POST 'status' - New status of kid.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     object  'data'    - New data of kid.
 */
$router -> post("/api/kid/update_status", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    $kids = load_data($config["database"]["kids"]);
    $old_status;
    foreach ($kids as $key => $_){
        if ($kids[$key] -> id != $_POST["id"]){
            continue;
        }
        $old_status = $kids[$key] -> status;
        $kids[$key] -> update_preference("status", $_POST["status"]);
        $logger -> log(new Kid_Status_Change_Event($_SERVER["REMOTE_ADDR"],
                                                   get_self() -> username,
                                                   ["Full Name" => $kids[$key] -> get_full_name(),
                                                    "ID" => $kids[$key] -> id],
                                                    ucfirst($old_status),
                                                    ucfirst($_POST["status"])));
        if (count($kids[$key] -> group) === 0){
            break;
        }
        //TODO - Make sure this is good, not performance killing.
        $accounts = load_data($config["database"]["accounts"]);
        foreach (load_data($config["database"]["groups"]) as $group){
            if (!array_key_exists($group -> id, $kids[$key] -> group)){
                continue;
            }
            foreach ($accounts as $account){
                if ($account -> id === get_self() -> id or (!array_key_exists($account -> id, $group -> leaders))){
                    continue;
                }
                $account -> contact($kid_id); //TODO - Maybe fix this, not sure.
            }
        }
        break;
    }
    save_data($kids, $config["database"]["kids"]);
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Status of '" . $kids[$key] -> get_full_name() . "' changed from '" . ucfirst($old_status) . "' to '" . ucfirst($kids[$key] -> status) . "'",
        "data" => $kids[$key]
    ]);
    return;
}, ["id" => true, "status" => true]);

/**
 * Edit kid information.
 * @param string $_POST 'id'         - ID of kid to change information of.
 * @param string $_POST 'first_name' - New first name of kid.
 * @param string $_POST 'last_name'  - New last name of kid.
 * @param string $_POST 'full_name'  - New full name of kid.
 * @param string $_POST 'parents'    - New parents of kid.
 * @param string $_POST 'hidden'     - New hidden status of kid.
 * @param string $_POST 'group'     - New group of kid.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     object  'data'    - New data of kid.
 */
$router -> post("/api/kid/edit", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }
    $changed_fields = [];
    $kids = load_data($config["database"]["kids"]);
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
                        if ($kids[$kid_key] -> update_preference($data_key, $value)){
                            $changed_fields[] = ucfirst($data_key);
                            $old_value;
                            if ($kids[$kid_key] -> update_preference("group", NULL, $old_value)){
                                $groups = load_data($config["database"]["groups"]);
                                foreach ($groups as $group => $_){
                                    if ($groups[$group] -> id != $old_value){
                                        continue;
                                    }
                                    $groups[$group] -> remove_kid($kids[$kid_key] -> id);
                                    break;
                                }
                                save_data($groups, $config["database"]["groups"]);
                            }
                        }
                    }
                    else{
                        header("HTTP/1.0 403 Forbidden");
                        echo json_encode([
                            "success" => false,
                            "message" => "Only accounts with access level of 'mod' can change child visiabilty."
                        ]);
                        return;
                    }
                    break;

                case "group":
                    $groups = load_data($config["database"]["groups"]);
                    if ($value === ""){
                        $value = NULL;
                    }
                    $old_value;
                    if ($kids[$kid_key] -> update_preference($data_key, $value, $old_value)){
                        if ($old_value !== NULL){
                            foreach ($groups as $group => $_){
                                if ($groups[$group] -> id != $old_value){
                                    continue;
                                }
                                $groups[$group] -> remove_kid($kids[$kid_key] -> id);
                                break;
                            }
                        }
                        if ($value !== NULL){
                            foreach ($groups as $group => $_){
                                if ($groups[$group] -> id != $value){
                                    continue;
                                }
                                $groups[$group] -> add_kid($kids[$kid_key] -> id);
                                break;
                            }
                        }
                        save_data($groups, $config["database"]["groups"]);
                    }
                    break;

                default:
                    if ($kids[$kid_key] -> update_preference($data_key, $value)){
                        $changed_fields[] = ucfirst($data_key);
                    }
                    break;
            }
        }
        $logger -> log(new Edit_Event($_SERVER["REMOTE_ADDR"],
                                      get_self() -> username,
                                      "kid",
                                      ["Full Name" => $kids[$kid_key] -> get_full_name(),
                                       "ID" => $kids[$kid_key] -> id],
                                      $changed_fields));
        break;
    }

    save_data($kids, $config["database"]["kids"]);
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Kid '" . $kids[$kid_key] -> get_full_name() . "' edited.",
        "data" => $kids[$kid_key]
    ]);
    return;
}, ["id" => true, "first_name" => false, "last_name" => false, "parents" => false, "full_name" => false, "hidden" => false, "group" => false]);

/**
 * Create a group.
 * @param string $_POST 'name' - Name of new group
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     object  'data'    - Data of new group.
 */
$router -> post("/api/group/create", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }
    if (!get_self() -> has_access("mod")){
        header("HTTP/1.0 403 Forbidden");
        echo json_encode([
            "success" => false,
            "message" => "Only accounts with an access level of 'mod' can create groups."
        ]);
        return;
    }

    $new_group = new Group(generate_id($config["database"]["ids"]), $_POST["name"]);

    $groups = load_data($config["database"]["groups"]);
    foreach ($groups as $key => $_){
        if ($groups[$key] -> name !== $new_group -> name){
            continue;
        }
        $logger -> log(new Custom_Event($_SERVER["REMOTE_ADDR"],
                                       get_self() -> username,
                                       "Failed to create group",
                                       "Group named '" . $new_group -> name . "' already exists"));
        header("HTTP/1.0 400 Bad Request");
        echo json_encode([
            "success" => false,
            "message" => "A Group named '" . $_POST["name"] . "' already exists!"
        ]);
        return;
    }

    $logger -> log(new Creation_Event($_SERVER["REMOTE_ADDR"],
                                      get_self() -> username,
                                      "group",
                                      ["Name" => $new_group -> name]));

    add_objects([$new_group], $config["database"]["groups"], $groups);

    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Group Successfully Created!",
        "data" => $new_group
    ]);
    return;
}, ["name" => true]);

/**
 * Delete a group.
 * @param integer $_POST 'id' - ID of group to delete.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 */
$router -> post("/api/group/delete", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    if (!(get_self() -> has_access("mod"))){
        header("HTTP/1.0 403 Forbidden");
        echo json_encode([
            "success" => false,
            "message" => "Only accounts with an access level of 'mod' can delete groups."
        ]);
        return;
    }

    $group = get_objects([$_POST["id"]], $config["database"]["groups"]);
    if (count($group) === 0){
        header("HTTP/1.0 404 Not Found");
        echo json_encode([
            "success" => false,
            "message" => "Group with ID of '" . $_POST["id"] . "' not found."
        ]);
        return;
    }
    $group = $group[0];

    $logger -> log(new Deletion_Event($_SERVER["REMOTE_ADDR"],
                                      get_self() -> username, "group",
                                      ["Name" => $group -> name,
                                       "ID" => $group -> id]));
    remove_objects([$group], $config["database"]["groups"]);
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Group '" . $group -> name . "' deleted."
    ]);
    return;
}, ["id" => true]);

/**
 * Get JSON data of group by id.
 * @param integer $_POST 'id' - ID of group to get JSON data of.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     object  'data'    - Data of group.
 */
$router -> post("/api/group/get", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    $group = get_objects([$_POST["id"]], $config["database"]["groups"]);
    if (count($group) === 0){
        header("HTTP/1.0 404 Not Found");
        echo json_encode([
            "success" => false,
            "message" => "Group with ID of '" . $_POST["id"] . "' not found."
        ]);
        return;
    }
    $group = $group[0];

    $response = [
        "success" => true,
        "message" => "Group retrived.",
        "data" => [
            "group" => $group -> to_table_row($_POST["edit"]),
            "kids" => [],
            "leaders" => []
        ]
    ];

    foreach (get_objects($group -> kids, $config["database"]["kids"]) as $kid){
        $response["data"]["kids"][] = $kid;
    }
    foreach (get_objects($group -> leaders, $config["database"]["accounts"]) as $leader){
        $response["data"]["leaders"][] = $leader;
    }

    header("HTTP/1.0 200 OK");
    echo json_encode($response);
    return;
}, ["id" => true]);

/**
 * Get JSON data of all groups.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     array   'data'    - List of groups received.
 */
$router -> get("/api/group/list", function($router){
    global $config;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    $response = [];
    foreach (load_data($config["database"]["groups"]) as $group){
        $response[] = $group -> to_table_row(false);
    }

    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "Groups retrived.",
        "data" => $response
    ]);
    return;
});

/**
 * Edit group information.
 * @param integer $_POST 'id'   - ID of group to change information of.
 * @param string $_POST 'name' - New group name.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     object  'data'    - New data of group.
 */
$router -> post("/api/group/edit", function($router){
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    if (!(get_self() -> has_access("mod"))){
        header("HTTP/1.0 403 Forbidden");
        echo json_encode([
            "success" => false,
            "message" => "Only accounts with an access level of 'mod' can edit groups."
        ]);
        return;
    }

    $groups = load_data($config["database"]["groups"]);

    foreach ($groups as $group_key => $_){
        if ($groups[$group_key] -> id != $_POST["id"]){
            continue;
        }
        foreach ($_POST as $data_key => $value){
            switch ($data_key){
                case "id":
                    break; //Replace with if-continue if there are no more cases.

                default:
                    $groups[$group_key] -> update_preference($data_key, $value);
                    break;
            }
        }
        $logger -> log(new Edit_Event($_SERVER["REMOTE_ADDR"],
                                      get_self() -> username,
                                      "group",
                                      ["Name" => $groups[$group_key] -> name,
                                       "ID" => $groups[$group_key] -> id],
                                        array_keys($_POST)));
        break;
    }

    save_data($groups, $config["database"]["groups"]);

    echo json_encode([
        "success" => true,
        "message" => "Group '" . $groups[$group_key] -> name . "' edited.",
        "data" => $groups[$group_key] -> to_table_row(false)
    ]);
    return;
}, ["id" => true, "name" => true]);

/**
 * Get JSON data for all events.
 * @param string $_POST["since"] - Last time the client has received data.
 *
 * @return string JSON
 *     boolean 'success'
 *     string  'message'
 *     array   'data'    - List of logs received.
 */
$router -> post("/api/history/list", function($router){ //Overhall along with logging.
    global $config, $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }

    $response = [];

    $history = $logger -> get_all_history();

    function compare_time($a, $b){
        return $b -> time - $a -> time;
    }
    usort($history, "compare_time");
    foreach ($history as $event){
        $response[] = $event;
    }
    header("HTTP/1.0 200 OK");
    echo json_encode([
        "success" => true,
        "message" => "History loaded.",
        "data" => $response
    ]);
    return;
}, ["since" => true]);

/**
 * Return the last modified time of the latest log file.
 *
 * @return integer - UTC/Epoch account modification time.
 */
$router -> get("/api/history/poll", function($router){
    global $logger;
    if (!is_logged_in()){
        header("HTTP/1.0 401 Unauthorized");
        echo json_encode([
            "success" => false,
            "message" => "Must be logged in.",
            "redirect" => "http://" . $_SERVER["HTTP_HOST"] . "/login"
        ]);
        return;
    }
    header("HTTP/1.0 200 OK");
    echo filemtime($logger -> current_filepath);
    return;
});

session_start();

$router -> match();

?>