<?php
$DOCUMENT_ROOT = realpath(__DIR__ . "/..") . "/";
$RESOURCES = realpath(__DIR__ . "/../resources") . "/";
if (getenv("OPENSHIFT_DATA_DIR") == false){
    $DATA_DIR = $DOCUMENT_ROOT . "/data/";
}
else{
    $DATA_DIR = getenv("OPENSHIFT_DATA_DIR");
}
$config = [
    "class" => [
        "router" => $RESOURCES . "libraries/router.php",
        "account" => $RESOURCES . "libraries/account.php",
        "group" => $RESOURCES . "libraries/group.php",
        "kid" => $RESOURCES . "libraries/kid.php",
        "logging" => $RESOURCES . "libraries/logging.php",
        "utility" => $RESOURCES . "libraries/utility.php"
    ],
    "database" => [
        "accounts" => $DATA_DIR . "database/accounts.db",
        "groups" => $DATA_DIR . "database/groups.db",
        "kids" => $DATA_DIR . "database/kids.db",
        "ids" => $DATA_DIR . "database/ids.db"
    ],
    "path" => [
        "assets" => $DOCUMENT_ROOT . "assets/",
        "templates" => $RESOURCES . "templates/",
        "logs" => $DATA_DIR . "logs/",
        "data" => $DATA_DIR
    ]
];

foreach ($config["database"] as $database){
    if (!file_exists($database)){
        file_put_contents($database, serialize([]));
    }
}

foreach(["logs", "database"] as $data_subdir){
    if (file_exists($DATA_DIR . $data_subdir) && is_dir($DATA_DIR . $data_subdir)){
        continue;
    }
    mkdir($DATA_DIR . $data_subdir);
}

unset($DATA_DIR);
unset($RESOURCES);
unset($DOCUMENT_ROOT);

?>