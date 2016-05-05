

<?php
session_start();
include(dirname(__FILE__) . "/../resources/config.php");

if (count($_POST) > 0){
    switch ($_POST["action"]){
        case "login":
            # if login is good
            if (True){
                $_SESSION["login"] = [
                    "username" => $_POST["username"],
                    "last_activity" => time()
                ];

                echo "<h3>Successful Login</h3>";

                echo "Loading...";
            }
            else{
                echo "<h3>Failure</h3>";
            }
            break;

        case "last_change":
            echo time();
            break;

        case "content":
            if (is_logged_in($_SESSION)){
                echo "<table>";
                echo "<tr>";
                echo "<th>";
                echo "First Name";
                echo "</th>";
                echo "<th>";
                echo "Last Name";
                echo "</th>";
                echo "<th>";
                echo "Primary Parent";
                echo "</th>";
                echo "<th>";
                echo "Status";
                echo "</th>";
                echo "<th>";
                echo "Status Duration";
                echo "</th>";
                echo "<th>";
                echo "Actions";
                echo "</th>";
                echo "</tr>";
                $kids = get_kids($config);
                foreach ($kids as $kid){
                    echo "<tr id='" . $kid -> id . "'>";
                    $kid -> html();
                    echo "<tr>";
                }
                echo "</table>";
                echo "<br>";
                echo "Name: <input type='text' id='new_kid_name'>";
                echo "<br>";
                echo "Status: <input type='checkbox' id='new_kid_status'>";
                echo "<br>";
                echo "Parents: <input type='text' id='new_kid_parents'>";
                echo "<br>";
                echo "<input type='button' id='add_kid_button' value='Add Kid'>";
            }
            else{
                echo "Username: <input type='text' id='username_input'>";
                echo "<br>";
                echo "Password: <input type='text' id='password_input'>";
                echo "<br>";
                echo "<input type='button' id='login_button' value='Login'>";
            }
            break;

        case "add_kid":
            $kids = get_kids($config);
            while (True){
                $id = rand(1000, 9999);
                $pass = True;
                foreach ($kids as $kid) {
                    if ($kid -> id == $id){
                        $pass = False;
                    }
                }
                if ($pass){
                    $kid = new Kid($id, $_POST["name"], $_POST["parents"], $_POST["status"]);
                    break;
                }
            }
            array_push($kids, $kid);
            file_put_contents($config["data"]["kids"], serialize($kids));
            break;

        case "confirm_edit":
            $kids = get_kids($config);
            foreach ($kids as $key => $kid){
                if ($kid -> id == $_POST["id"]){
                    $kid -> first_name = $_POST["fname"];
                    $kid -> last_name = $_POST["lname"];
                    $kid -> set_parents($_POST["parents"]);
                    $kid -> html();
                    $kids[$key] = $kid;
                }
            }
            save_kids($kids, $config);
            break;

        case "show_edit":
            $kids = get_kids($config);
            foreach ($kids as $kid){
                if ($kid -> id == $_POST["id"]){
                    $kid -> html(True);
                }
            }
            break;

        case "poll":
            if (is_logged_in($_SESSION)){
                echo last_change_time($config);
            }
            else{
                echo "";
            }
            break;

        case "status_change":
            $kids = get_kids($config);
            foreach ($kids as $key => $kid){
                if ($kid -> id == $_POST["id"]){
                    $kid -> set_status($_POST["status"]);
                    $kids[$key] = $kid;
                }
            }
            save_kids($kids, $config);

            break;

        default:
            error_log(print_r("Unknown Post: '" . $_POST . "'", true));
            break;
    }
}

class Kid{
    public $id;
    public $first_name;
    public $last_name;
    public $parents;
    public $status;
    public $last_status_change;

    public function __construct($id, $full_name, $raw_parents, $status){
        $this -> id = $id;
        if (strpos($full_name, " ") !== False){
            $name_segments = explode(" ", $full_name);
            $this -> first_name = $name_segments[0];
            $this -> last_name = implode(" ", array_slice($name_segments, 1, count($name_segments)));
        }
        else{
            $this -> first_name = $full_name;
        }
        $this -> set_parents($raw_parents);
        $this -> status = $this -> set_status($status);
    }

    public function set_status($raw_status){
        $this -> status = ($raw_status === 'true');
    }

    public function set_parents($raw_parents){
        if (strpos($raw_parents, ",") !== False){
            $this -> parents = explode(",", $raw_parents);
        }
        else{
            $this -> parents = [$raw_parents];
        }
    }

    public function html($edit=False){
        echo "<td>";
        if ($edit){
            echo "<input type='text' id='" . $this -> id . "-first_name' value='" . $this -> first_name . "'>";
        }
        else{
            echo $this -> first_name;
        }
        echo "</td>";
        echo "<td>";
        if ($edit){
            echo "<input type='text' id='" . $this -> id . "-last_name' value='" . $this -> last_name . "'>";
        }
        else{
            echo $this -> last_name;
        }
        echo "</td>";
        echo "<td>";
        error_log(print_r($this -> parents, true));
        if ($edit){
            echo "<input type='text' id='" . $this -> id . "-parents' value='" . implode(", ", $this -> parents) . "'>";
        }
        else{
            echo implode(", ", $this -> parents);
        }
        echo "</td>";
        echo "<td>";
        //echo $this -> status;
        echo "<label id='sliderLabel'>";
        error_log(print_r($this -> status, true));
        if ($this -> status == True){
            echo "<input id='" . $this -> id . "'type='checkbox' checked>";
        }
        else{
            echo "<input id='" . $this -> id . "' type='checkbox'>";
        }
        echo "<span id='slider'></span>";
        echo "<span id='sliderOn'>In</span>";
        echo "<span id='sliderOff'>Out</span>";
        echo "</label>";
        echo "</td>";
        echo "<td>";
        //echo $this -> last_status_change;
        echo "</td>";
        echo "<td>";
        if ($edit){
            echo "<input type='button' id='" . $this -> id . "-confirm_edit' value='Confirm'>";
        }
        else{
            echo "<input type='button' id='" . $this -> id . "-edit' value='Edit'>";
        }
        echo "</td>";
    }
}

function is_logged_in($session_instance){
    if (isset($session_instance["login"])){
        return True;
    }
    return False;
}

function get_kids($config){
    if (file_exists($config["data"]["kids"])){
        $kids = unserialize(file_get_contents($config["data"]["kids"]));
        if ($kids == False){
            return $kids = [];
        }
        return $kids;
    }
    else{
        file_put_contents($config["data"]["kids"], serialize([]));
        return [];
    }
}

function last_change_time($config){
    return filemtime($config["data"]["kids"]);
}

function save_kids($kids, $config){
    file_put_contents($config["data"]["kids"], serialize($kids));
}

?>