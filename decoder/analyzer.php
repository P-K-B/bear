<?php
class Analyzer
{
    public $config;
    public $connection;
    public $json_clan;
    public $json_attacks;
    public $json_clans;
    public $local_dump=array();

    public function __construct()
    {
        $file  = file_get_contents("config.json");
        $this->config = json_decode($file, true);

        $this->Connect();
        $this->Check_server();
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

        $query = "CREATE TABLE IF NOT EXISTS players (
               id INT UNSIGNED NOT NULL UNIQUE,
               nick NVARCHAR(128),
               level SMALLINT UNSIGNED NOT NULL,
               frags SMALLINT UNSIGNED,
               deaths SMALLINT UNSIGNED,
               clan_id INT UNSIGNED NOT NULL
               )";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
        $query = "CREATE TABLE IF NOT EXISTS logs (
               timemark int,
               textt NVARCHAR(128),
               eventt NVARCHAR(128)
               )";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
    }
    
    public function Update_info()
    {
        $result = Check_attacks();
        if ($result !==null) {
          // PARSE $result to get clans id
          $id=171;
          $id2=171;
          $result = $this->Check_players($id);
          $result2 = $this->Check_players($id2);
          if($result !==null){
            // UPDATE LOCAL INFO
          }

        }
    }
    public function Check_players($id){
      $this->json_attacks = file_get_contents($this->config["clan_page"].$id.".json");
      $info = json_decode($this->json_attacks, true);
      if (!empty($info)) {
          print_r( $info["players"]);
          return $info["players"];
      } else {
          echo "Something went wrong...";
          return null;
      }
    }
    public function Check_attacks()
    {
        $this->json_attacks = file_get_contents($this->config["attacks_page"]);
        $info = json_decode($this->json_attacks, true);
        if (!empty($info) && $playingNOW) {
            echo "Some info ";
            return $info;
        } else {
            echo "no current attacks";
            return null;
        }
    }

}
