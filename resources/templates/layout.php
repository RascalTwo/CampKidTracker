<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=320, initial-scale=1">
        <title><?php echo $page_title; ?></title>
        <link rel="stylesheet" type="text/css" href="css/main.css">
        <?php if ($css !== NULL){ ?>
            <link rel="stylesheet" type="text/css" href="css/<?php echo $css ?>.css">
        <?php } ?>
        <script src="js/jquery-2.2.3.min.js"></script>
        <?php
        if ($js !== NULL){
            foreach ($js as $filename){
                echo '<script type="text/javascript" src="js/' . $filename . '.js"></script>';
            }
        }
        ?>
    </head>
    <body>
        <span id="navigation">
            <ul>
                <?php if (is_logged_in()){ ?>
                    <li><a href="/dashboard">Dashboard</a></li>
                    <li><a href="/preferences">Preferences</a></li>
                    <?php if (get_self() -> has_access("admin")){ ?>
                        <li><a href="/admin">Admin</a></li>
                    <?php } ?>
                    <?php if (get_self() -> has_access("mod")){ ?>
                        <li><a href="/history">History</a></li>
                        <li><a href="/groups">Groups</a></li>
                    <?php } ?>
                <?php }else{ ?>
                    <li><a href="/login">Log In</a></li>
                <?php }?>
                <li><a href="/about">About</a></li>
            </ul>
        </span>
        <br><br>
        <?php include $template_path; ?>
    </body>
</html>
