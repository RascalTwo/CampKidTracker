<?php

class Router{
    private $routes = [
        "GET" => [],
        "POST" => [],
        "PUT" => [],
        "PATCH" => [],
        "DELETE" => [],
        "ANY" => [],
    ];

    public function get($path, $handler){
        $this -> addRoute("GET", $path, $handler, NULL);
    }

    public function post($path, $handler, $excepted_params){
        $this -> addRoute("POST", $path, $handler, $excepted_params);
    }

    public function put($path, $handler, $excepted_params){
        $this -> addRoute("PUT", $path, $handler, $excepted_params);
    }

    public function patch($path, $handler, $excepted_params){
        $this -> addRoute("PATCH", $path, $handler, $excepted_params);
    }

    public function delete($path, $handler, $excepted_params){
        $this -> addRoute("DELETE", $path, $handler, $excepted_params);
    }

    public function any($path, $handler, $excepted_params){
        $this -> addRoute("ANY", $path, $handler, $excepted_params);
    }

    private function addRoute($method, $path, $handler, $excepted_params){
        $this -> routes[$method][$path] = ["handler" => $handler, "excepted_params" => $excepted_params];
    }

    private function sanitize($excepted_params){
        if ($excepted_params === NULL){
            return $_POST;
        }
        foreach ($excepted_params as $param => $required){
            if ($required){
                $_POST[$param] = strip_tags(filter_var($_POST[$param], FILTER_SANITIZE_STRING));
            }
            elseif (array_key_exists($param, $_POST)){
                $_POST[$param] = strip_tags(filter_var($_POST[$param], FILTER_SANITIZE_STRING));
            }
        }
        return $_POST;
    }

    public function match(){
        $method = $_SERVER["REQUEST_METHOD"];
        $uri = $_SERVER["REQUEST_URI"];

        error_log(print_r($_SERVER["REMOTE_ADDR"] . " " . $method . " " . $uri, true));

        if (array_key_exists($uri, $this -> routes[$method])){
            $_POST = $this -> sanitize($this -> routes[$method][$uri]["excepted_params"]);
            $this -> routes[$method][$uri]["handler"]();
            return;
        }

        if (file_exists(PUBLIC_HTML . $uri)){
            include PUBLIC_HTML . $uri;
            if (strpos($uri, ".css")){
                header("Content-type: text/css");
            }
            return;
        }

        include RESOURCES . "/templates/error.php";
    }
}

?>