<?php

function GetClans()
{
    $file  = file_get_contents("config.json");
    $config = json_decode($file, true);
    if ($config["source"]==1) {
        $connection; // Объект, отвечающий за подключение к БД
        $connection = new mysqli($config["hostname"].$config["port"], $config["username"], $config["password"]);
        if ($connection->connect_errno) {
            die("Unable to connect to MySQL server:".$connection->connect_errno.$connection->connect_error);
        }
        // Установка параметров соединения (не уверен, что это надо)
        $connection->query("SET NAMES 'utf8'");
        $connection->query("SET CHARACTER SET 'utf8'");
        $connection->query("SET SESSION collation_connection = 'utf8_general_ci'");
        $connection->query("USE {$config["database"]}");
        $result=$connection->query("SELECT * FROM clans");
        $data=array();
        while ($row = $result->fetch_assoc()) {
            array_push($data, $row);
        }
        return $data;
    } else {
        $file  = file_get_contents($config["clans_url"].".json");
        $json = json_decode($file, true);
        return $json;
    }
}

function GetClanData($id)
{
    $file  = file_get_contents("config.json");
    $config = json_decode($file, true);
    if ($config["source"]==1) {
        $connection; // Объект, отвечающий за подключение к БД
        $connection = new mysqli($config["hostname"].$config["port"], $config["username"], $config["password"]);
        if ($connection->connect_errno) {
            die("Unable to connect to MySQL server:".$connection->connect_errno.$connection->connect_error);
        }
        // Установка параметров соединения (не уверен, что это надо)
        $connection->query("SET NAMES 'utf8'");
        $connection->query("SET CHARACTER SET 'utf8'");
        $connection->query("SET SESSION collation_connection = 'utf8_general_ci'");
        $connection->query("USE {$config["database"]}");
        $result=$connection->query("SELECT * FROM clans");
        while ($row = $result->fetch_assoc()) {
            if ($row["id"]==$id) {
                $data=["id"=>$row["id"],"title"=>$row["title"],"players"=>null];
                $tmp=array();
                $result2=$connection->query("SELECT * FROM players WHERE clan_id={$row["id"]}");
                while ($row2 = $result2->fetch_assoc()) {
                    array_push($tmp, $row2);
                }
                $data["players"]=$tmp;
            }
        }
        return $data;
    } else {
        $file  = file_get_contents($config["clan_data_url"].$id.".json");
        $json = json_decode($file, true);
        return $json;
    }
}

function GetFights()
{
    $file  = file_get_contents("config.json");
    $config = json_decode($file, true);
    if ($config["source"]==1) {
        $connection; // Объект, отвечающий за подключение к БД
        $connection = new mysqli($config["hostname"].$config["port"], $config["username"], $config["password"]);
        if ($connection->connect_errno) {
            die("Unable to connect to MySQL server:".$connection->connect_errno.$connection->connect_error);
        }
        // Установка параметров соединения (не уверен, что это надо)
        $connection->query("SET NAMES 'utf8'");
        $connection->query("SET CHARACTER SET 'utf8'");
        $connection->query("SET SESSION collation_connection = 'utf8_general_ci'");
        $connection->query("USE {$config["database"]}");
        $result=$connection->query("SELECT * FROM attacks");
        $result2=$connection->query("SELECT * FROM clans");
        $data=["attacker"=>null,"defender"=>null,"declared"=>null,"resolved"=>null];
        while ($row = $result->fetch_assoc()) {
            while ($row2 = $result2->fetch_assoc()) {
                if ($row["attacker_id"]==$row2["id"]) {
                    $data["attacker"]=$row2["title"];
                }
                if ($row["defender_id"]==$row2["id"]) {
                    $data["defender"]=$row2["title"];
                }
            }
            $data["declared"]=date("Y-m-d H:i:s", $row["declared"]);
            $data["resolved"]=date("Y-m-d H:i:s", $row["resolved"]);
            // array_push($data, $row);
        }
        return $data;
    } else {
        $file  = file_get_contents($config["attacks_url"].".json");
        $json = json_decode($file, true);
        return $json;
    }
}
