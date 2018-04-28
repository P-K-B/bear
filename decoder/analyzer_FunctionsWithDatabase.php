<?php
// TODO Те обращения к БД, которых много, засунуть в 1 и при необходимости разбивать локально!

 trait FunctionsWithDatabase
 {
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
             // echo("Connected to MySQL server.\n");
         }
     }

     public function Check_server() // Функция проверки состояния баз в БД и самой БД, при необходимости создаем их (если БД "новая")
     {
         if ($this->del==1) {
             $query = "DROP DATABASE {$this->config["analyzer_database"]}";
             $result = $this->connection->query($query);
             if (!$result) {
                 die("Error during creating table1".$this->connection->connect_errno.$this->connection->connect_error);
             }
         }
         $query = "CREATE DATABASE IF NOT EXISTS {$this->config["analyzer_database"]}";
         $result = $this->connection->query($query);
         if (!$result) {
             die("Error during creating table1".$this->connection->connect_errno.$this->connection->connect_error);
         }
         $query = "USE {$this->config["analyzer_database"]}";
         $result = $this->connection->query($query);
         if (!$result) {
             die("Error during creating table2".$this->connection->connect_errno.$this->connection->connect_error);
         }
         $query = "CREATE TABLE IF NOT EXISTS clans (
                 id int  NOT NULL  Primary KEY,
                 title varchar(128) DEFAULT NULL)";
         $result = $this->connection->query($query);
         if (!$result) {
             die("Error during creating table3".$this->connection->connect_errno.$this->connection->connect_error);
         }

         $query = "CREATE TABLE IF NOT EXISTS players (
                 id int  NOT NULL Primary KEY,
                 nick varchar(128) DEFAULT NULL,
                 level int  NOT NULL,
                 frags int  DEFAULT NULL,
                 deaths int  DEFAULT NULL,
                 clan_id int  NOT NULL,
                 Foreign key (clan_id) References clans(id)
                 )";
         $result = $this->connection->query($query);
         if (!$result) {
             die("Error during creating table4".$this->connection->connect_errno.$this->connection->connect_error);
         }

         $query = "CREATE TABLE IF NOT EXISTS attacks (
                 -- id int NOT NULL AUTO_INCREMENT Primary KEY,
                 id int NOT NULL Primary KEY,
                 attacker_id int DEFAULT NULL,
                 defender_id int DEFAULT NULL,
                 declared varchar(128) DEFAULT NULL,
                 resolved varchar(128) DEFAULT NULL,
                 in_progress int DEFAULT NULL,
                 updated int DEFAULT NULL,
                 Foreign key (attacker_id) References clans(id),
                 Foreign key (defender_id) References clans(id)
                 )";
         $result = $this->connection->query($query);
         if (!$result) {
             die("Error during creating table5".$this->connection->connect_errno.$this->connection->connect_error);
         }


         $query = "CREATE TABLE IF NOT EXISTS logs (
                 id int not null AUTO_INCREMENT Primary KEY,
                 timemark int DEFAULT NULL,
                 text1 varchar(128) DEFAULT NULL,
                 text2 varchar(128) DEFAULT NULL,
                 fight int DEFAULT NULL,
                 Foreign key (fight) References attacks(id)
                 )";
         $result = $this->connection->query($query);
         if (!$result) {
             die("Error during creating table6".$this->connection->connect_errno.$this->connection->connect_error);
         }

         $query = "CREATE TABLE IF NOT EXISTS tickets (
                 id int not null AUTO_INCREMENT Primary KEY,
                 timemark int DEFAULT NULL,
                 killers varchar(128) DEFAULT NULL,
                 deads varchar(128) DEFAULT NULL,
                 fight int DEFAULT NULL,
                 Foreign key (fight) References attacks(id)
                 )";
         $result = $this->connection->query($query);
         if (!$result) {
             die("Error during creating table6".$this->connection->connect_errno.$this->connection->connect_error);
         }

         $query = "CREATE TABLE IF NOT EXISTS extra (
                 field varchar(128) DEFAULT NULL,
                 data varchar(128) DEFAULT NULL
                 )";
         $result = $this->connection->query($query);
         if (!$result) {
             die("Error during creating table6".$this->connection->connect_errno.$this->connection->connect_error);
         }
     }

     public function Backup() // Функция резервного копирования текущего состаяния сервера в БД (для работы функции Restore)
     {
         foreach ($this->Clans as $clan) {
             // if ($clan->id!=171) {
             $data = $this->connection->query("SELECT * FROM clans WHERE id=$clan->id");
             if ($data->num_rows > 0) {
                 $query="UPDATE clans SET title=\"$clan->name\" WHERE id=$clan->id";
                 // // echo $query;
                 $result = $this->connection->query($query);
                 if (!$result) {
                     die("Error during creating table999".$this->connection->connect_errno.$this->connection->connect_error);
                 }
             } else {
                 $query="INSERT INTO clans (id,title) VALUES ($clan->id,\"$clan->name\")";
                 // echo $query;
                 $result = $this->connection->query($query);
                 if (!$result) {
                     die("Error during creating table123".$this->connection->connect_errno.$this->connection->connect_error);
                 }
                 // }
             }
             foreach ($clan->players as $player) {
                 $data = $this->connection->query("SELECT * FROM players WHERE id=$player->id");
                 if ($data->num_rows > 0) {
                     $query="UPDATE players SET nick=\"$player->nick\",frags=$player->frags,deaths=$player->deaths,level=$player->level,clan_id=$player->clan_id WHERE id=$player->id";
                     $result = $this->connection->query($query);
                     if (!$result) {
                         die("Error during creating table789".$this->connection->connect_errno.$this->connection->connect_error);
                     }
                 } else {
                     $query="INSERT INTO players (nick,frags,deaths,level,clan_id,id) VALUES (\"$player->nick\",$player->frags,$player->deaths,$player->level,$player->clan_id,$player->id)";
                     if ($this->config["debug"]) {
                         // // echo $query;
                     }
                     $result = $this->connection->query($query);
                     if (!$result) {
                         die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                     }
                 }
             }
         }

         foreach ($this->Fights as $fight) {
             // echo $fight->id;
             $data = $this->connection->query("SELECT * FROM attacks WHERE id=$fight->id");
             if ($data->num_rows > 0) {
                 $query="UPDATE attacks SET in_progress=$fight->in_progress,updated=$fight->updated WHERE id=$fight->id";
                 // // echo $query;
                 $result = $this->connection->query($query);
                 if (!$result) {
                     die("Error during creating table999".$this->connection->connect_errno.$this->connection->connect_error);
                 }
             } else {
                 $query="INSERT INTO attacks (id,attacker_id,defender_id,declared,resolved,in_progress,updated) VALUES "."($fight->id,$fight->attacker_id,$fight->defender_id,$fight->declared,$fight->resolved,$fight->in_progress,$fight->updated)";
                 // echo $query;
                 $result = $this->connection->query($query);
                 if (!$result) {
                     die("Error during creating table123".$this->connection->connect_errno.$this->connection->connect_error);
                 }
             }
             foreach ($fight->log as $log) {
                 $data = $this->connection->query("SELECT * FROM logs WHERE fight=$fight->id AND text1=\"$log\"");
                 if ($data->num_rows > 0) {
                     // $query="UPDATE attacks SET in_progress=$fight->in_progress,updated=$fight->updated WHERE id=$fight->id";
                     // // echo $query;
                     // $result = $this->connection->query($query);
                     // if (!$result) {
                     //     die("Error during creating table999".$this->connection->connect_errno.$this->connection->connect_error);
                     // }
                 } else {
                     $t=time();
                     $query="INSERT INTO logs (id,timemark,text1,text2,fight) VALUES "."(0,$t,\"$log\",NULL,$fight->id)";
                     // // echo $query;
                     $result = $this->connection->query($query);
                     if (!$result) {
                         die("Error during creating table123".$this->connection->connect_errno.$this->connection->connect_error);
                     }
                 }
             }
             foreach ($fight->tickets as $ticket) {
                 $data = $this->connection->query("SELECT * FROM tickets WHERE fight=$fight->id AND killers=\"{$ticket->GetKillers()}\" AND deads=\"{$ticket->GetDeads()}\"");
                 if ($data->num_rows > 0) {
                     // $query="UPDATE attacks SET in_progress=$fight->in_progress,updated=$fight->updated WHERE id=$fight->id";
                     // // echo $query;
                     // $result = $this->connection->query($query);
                     // if (!$result) {
                     //     die("Error during creating table999".$this->connection->connect_errno.$this->connection->connect_error);
                     // }
                 } else {
                     $t=time();
                     $query="INSERT INTO tickets (id,timemark,killers,deads,fight) VALUES "."(0,$t,\"{$ticket->GetKillers()}\",\"{$ticket->GetDeads()}\",$fight->id)";
                     // // echo $query;
                     $result = $this->connection->query($query);
                     if (!$result) {
                         die("Error during creating 98765".$this->connection->connect_errno.$this->connection->connect_error);
                     }
                 }
             }
         }
         $data = $this->connection->query("SELECT * FROM extra WHERE field=\"at_id\"");
         if ($data->num_rows > 0) {
             $query="UPDATE extra SET data=$this->attack_id WHERE field=\"at_id\"";
             // // echo $query;
             $result = $this->connection->query($query);
             if (!$result) {
                 die("Error during creating table999".$this->connection->connect_errno.$this->connection->connect_error);
             }
         } else {
             $query="INSERT INTO extra (field,data) VALUES "."(\"at_id\",$this->attack_id)";
             // // echo $query;
             $result = $this->connection->query($query);
             if (!$result) {
                 die("Error during creating table123".$this->connection->connect_errno.$this->connection->connect_error);
             }
             // }
         }
     }

     public function Restore() // Функция востановления сервера через БД. Если БД пустая (или новая) то ничего не проихайдет
     {
         $Clans=array();
         $result = $this->connection->query("SELECT * FROM clans");
         while ($row = $result->fetch_assoc()) {
             $tmp=new Clan($row['id'], $row['title']);
             array_push($Clans, $tmp);
         }
         $result = $this->connection->query("SELECT * FROM players");
         while ($row = $result->fetch_assoc()) {
             $pl=new Player($row['id'], $row['nick'], $row['frags'], $row['deaths'], $row['level'], $row['clan_id'], $row['in_fight']);
             foreach ($Clans as $key=>$clan) {
                 if ($clan->id==$pl->clan_id) {
                     array_push($Clans[$key]->players, $pl);
                 }
             }
         }
         $this->Clans=$Clans;
         $result = $this->connection->query("SELECT * FROM attacks");
         $Fights=array();
         while ($row = $result->fetch_assoc()) {
             // echo $row;
             $tmp=new Fight($row["id"], $row['attacker_id'], $row['defender_id'], $row['declared'], $row['resolved'], $row['in_progress'], $row["updated"]);
             array_push($Fights, $tmp);
         }
         $this->Fights=$Fights;
         $result = $this->connection->query("SELECT * FROM extra");
         while ($row = $result->fetch_assoc()) {
             $this->attack_id=$row["data"];
         }
     }

     public function Log($textt, $event, $fight) // Функция записи сбытия в log БД
     {
         if (strpos($text1, "Fight ended")!== false) {
             $time=time()+1;
         } else {
             $time=time();
         }
         $query="INSERT INTO logs (timemark,textt,eventt,fight) VALUES($time,\"$textt\",\"$event\",\"$fight\")";
         if ($this->config["debug"]) {
             // // echo $query;
         }
         $result = $this->connection->query($query);
         if (!$result) {
             die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
         }
     }
 }
