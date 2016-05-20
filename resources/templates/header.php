<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?php echo $page_title; ?></title>

        <link rel="stylesheet" type="text/css" href="css/main.css">
        <script src="js/jquery-2.2.0.js"></script>
    </head>
    <body>
        <span id="navigation">
            <ul>
                <?php if (logged_in()){ ?>
                    <li><a href="/dashboard">Dashboard</a></li>
                    <li><a href="/preferences">Preferences</a></li>
                    <?php if (get_self() -> has_access("admin")){ ?>
                        <li><a href="/admin">Admin</a></li>
                    <?php } ?>
                    <?php if (get_self() -> has_atleast_access("mod")){ ?>
                        <li><a href="/history">History</a></li>
                    <?php } ?>
                <?php }else{ ?>
                    <li><a href="/login">Log In</a></li>
                <?php }?>
                <li><a href="/about">About</a></li>
            </ul>
        </span>
        <br>