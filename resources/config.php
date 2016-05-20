<?php
define("PUBLIC_HTML", realpath(__DIR__ . "/../public_resources"));
define("RESOURCES", realpath(__DIR__ . "/../resources"));

$config = [
    "class" => [
        "router" => RESOURCES . "/libraries/router.php",
        "account" => RESOURCES . "/libraries/account.php",
        "kid" => RESOURCES . "/libraries/kid.php",
        "logging" => RESOURCES . "/libraries/logging.php",
        "utility" => RESOURCES . "/libraries/utility.php"
    ],
    "database" => [
        "accounts" => RESOURCES . "/database/accounts.db",
        "kids" => RESOURCES . "/database/kids.db",
        "ids" => RESOURCES . "/database/ids.db"
    ],
    "path" => [
        "logs" => RESOURCES . "/logs/"
    ],
    "templates" => [
        "login" => RESOURCES . "/templates/login.php",
        "dashboard" => RESOURCES . "/templates/dashboard.php",
        "preferences" => RESOURCES . "/templates/preferences.php",
        "admin" => RESOURCES . "/templates/admin.php",
        "history" => RESOURCES . "/templates/history.php",
        "error" => RESOURCES . "/templates/error.php",
        "about" => RESOURCES . "/templates/about.php"
    ]
];

foreach ($config["database"] as $database){
    if (!file_exists($database)){
        file_put_contents($database, serialize([]));
    }
}

?>