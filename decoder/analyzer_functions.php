<?php

class Analyzer
{
    public $config;
    public $Clans=array();
    public $Fights=array();
    public $Time;
    public $connection;

    public function Sleepp() // Перевести сервер в спящий режим на вермя (время берется из config)
    {
        sleep($this->config["server_sleep_time"]);
    }

    public function __construct()
    {
        $this->UpdateConfig();
        $this->Connect();
        $this->Check_server();
        $this->Restore();
    }

    public function UpdateConfig()
    {
        $file  = file_get_contents(realpath(dirname(__FILE__))."/../config.json");
        $this->config = json_decode($file, true);
        print_r($this->config);
    }
    public function Connect() // Функция подключения к БД
    {
        $this->connection = new mysqli($this->config["hostname"].$this->config["port"], $this->config["username"], $this->config["password"]);
        if ($this->connection->connect_errno) {
            die("Unable to connect to MySQL server:".$this->connection->connect_errno.$this->connection->connect_error);
        }
        // Установка параметров соединения (не уверен, что это надо)
        $this->connection->query("SET NAMES 'utf8'");
        $this->connection->query("SET CHARACTER SET 'utf8'");
        $this->connection->query("SET SESSION collation_connection = 'utf8_general_ci'");
        if ($this->connection && $this->config["debug"]) {
            echo("Connected to MySQL server.\n");
        }
    }

    public function Check_server() // Функция проверки состояния баз в БД и самой БД, при необходимости создаем их (если БД "новая")
    {
        $query = "CREATE DATABASE IF NOT EXISTS {$this->config["database"]}";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
        $query = "USE {$this->config["database"]}";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
        $query = "CREATE TABLE IF NOT EXISTS attacks (
                  timemark int,
    						  attacker_id NVARCHAR(128),
    						  defender_id NVARCHAR(128),
    						  declared NVARCHAR(128),
    						  resolved NVARCHAR(128),
                  in_progress int,
    						  c1 NVARCHAR(128),
    						  c2 NVARCHAR(128))";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
        $query = "CREATE TABLE IF NOT EXISTS clans (
    						  id smallint(5) unsigned NOT NULL,
    						  title varchar(128) DEFAULT NULL)";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
        $query = "CREATE TABLE IF NOT EXISTS players (
    						  id INT UNSIGNED NOT NULL UNIQUE,
    						  nick NVARCHAR(128),
    						  level SMALLINT UNSIGNED NOT NULL,
    						  frags SMALLINT UNSIGNED,
    						  deaths SMALLINT UNSIGNED,
    						  clan_id INT UNSIGNED NOT NULL,
                  in_fight int)";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
        $query = "CREATE TABLE IF NOT EXISTS logs (
                  timemark int,
    						  text1 NVARCHAR(128),
                  text2 NVARCHAR(128),
                  eventt NVARCHAR(128),
                  fight NVARCHAR(128))";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
    }
}
