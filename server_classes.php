<?php

// TODO: Веб монитор (вероятно на основе javascropt?)
// TODO: Проверить и утвердить классы
// TODO: Написать коментарии!!!
// TODO: (когданибудь) Наверое, было бы правильно, избавиться от public...

class Server
{
    public $Fights; // Массив содержит информацию по боям ан сервере
    public $Time; // Для работы с времинем (в часности, для отслеживания начало боев)
    public $Clans=array(); // Массив содержит информацию по кланам на сервере
    public $connection; // Объект, отвечающий за подключение к БД

    public $config; // Объект, содержащий информацию о пользовательских настройках (подробнее в файле config.json)

    public function Sleepp() // Перевести сервер в спящий режим на вермя (время берется из config)
    {
        sleep($this->config["sleep_time"]);
    }

    public function __construct()
    {
        $file  = file_get_contents("config.json");
        $this->config = json_decode($file, true);

        $this->Connect();
        $this->Check_server();
        $this->Restore();
        if ($this->Clans==null) {
            $this->NewClans();
        }
    }

    public function NewFight() // Функция создание нового боя
    {
        if ($this->Clans) { // Если на сервере нет кланов, то некому сражаться
            if (rand(1, $this->config["fight_rand"]) == 1) {
                $rand1=array_rand($this->Clans); // Случайно выбираем атакующего
                rerand:
                $rand2=array_rand($this->Clans); // Сулчайно выбираем защищающегося
                if ($rand1==$rand2) { // Атакующий не может быть защищающимся
                    goto rerand;
                }
                $add=rand($this->config["min_add"], $this->config["max_add"]); // Определяем кокга состаится бой
                $resolved=time()+$add;
                $attacker_id=$this->Clans[$rand1]->id;
                $defender_id=$this->Clans[$rand2]->id;
                $tmp=new Fight($attacker_id, $defender_id, time(), $resolved, 0, null, null); // Оаздаем объект боя
                array_push($this->Fights, $tmp);
            }
        }
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
    						  textt NVARCHAR(128),
                  eventt NVARCHAR(128),
                  fight NVARCHAR(128))";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
    }

    public function NewClans() // Функция создания новых кланов (если создаем новый сервер и новые БД соответственно)
    {
        if ($this->config["debug"]) {
            echo "Made new!\n";
        }
        $homepage = file_get_contents('names.pkb', true); // возможные имена игроков находятся в файле names.pkb
        $dt = explode("\n", $homepage);
        if ($dt[count($dt)] == null) {
            array_pop($dt);
        }
        if ($this->config["debug"]) {
            print_r($dt);
        }
        for ($i = 0; $i < count($dt); $i++) {
            for ($k = $i + 1; $k < count($dt); $k++) {
                if (($i != $k) && ($dt[$i] == $dt[$k])) {
                    $dt[$k] = null;
                }
            }
        }
        $unc = array();
        for ($i = 0; $i < count($dt); $i++) {
            if ($dt[$i] != null) {
                $unc[] = $dt[$i];
            }
        }
        $c = 0;
        $Clans=array();
        // Имен хватает на создание 10 кланов по 29 игроков в каждом
        for ($i = 1; $i <= 10; $i++) {
            $tmp=new Clan($i, "clan$i");
            for ($k = 0; $k < 29; $k++) {
                $pl=new Player($c, $unc[$c], 0, 0, 0, $i, 0);
                array_push($tmp->players, $pl);
                $c++;
            }
            array_push($Clans, $tmp);
        }
        $this->Clans=$Clans;
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
            $i=0;
            foreach ($Clans as $clan) {
                if ($clan->id==$pl->clan_id) {
                    array_push($Clans[$i]->players, $pl);
                }
                $i++;
            }
        }
        $this->Clans=$Clans;
        $result = $this->connection->query("SELECT * FROM attacks");
        $Fights=array();
        while ($row = $result->fetch_assoc()) {
            $pl1=$this->FindPlayers($row['c1'], $row['attacker_id']);
            $pl2=$this->FindPlayers($row['c2'], $row['defender_id']);
            $tmp=new Fight($row['attacker_id'], $row['defender_id'], $row['declared'], $row['resolved'], $row['in_progress'], $pl1, $pl2);
            array_push($Fights, $tmp);
        }
        $this->Fights=$Fights;
    }

    public function FindPlayers($ids_list, $clan_id) // Поиск игроков по id в объектах Clans для помещения их в объект боя при операции Restore
    {
        $ret=array();
        $ids=explode(',', $ids_list);
        $i=0;
        foreach ($this->Clans as $clan) {
            if ($clan->id==$clan_id) {
                $j=0;
                foreach ($clan->players as $player) {
                    foreach ($ids as $id) {
                        if ($player->id==$id) {
                            array_push($ret, null);
                            $ret[count($ret)-1]=&$this->Clans[$i]->players[$j];
                        }
                    }
                    $j++;
                }
            }
            $i++;
        }
        return $ret;
    }

    public function Backup() // Функция резервного копирования текущего состаяния сервера в БД (для работы функции Restore)
    {
        foreach ($this->Clans as $clan) {
            $data = $this->connection->query("SELECT * FROM clans WHERE id=$clan->id");
            if ($data->num_rows > 0) {
                $query="UPDATE clans SET title=\"$clan->name\" WHERE id=$clan->id";
                $result = $this->connection->query($query);
                if (!$result) {
                    die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                }
            } else {
                $query="INSERT INTO clans (id,title) VALUES ($clan->id,\"$clan->name\")";
                $result = $this->connection->query($query);
                if (!$result) {
                    die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                }
            }
            foreach ($clan->players as $player) {
                $data = $this->connection->query("SELECT * FROM players WHERE id=$player->id");
                if ($data->num_rows > 0) {
                    $query="UPDATE players SET nick=\"$player->nick\",frags=$player->frags,deaths=$player->deaths,level=$player->level,clan_id=$player->clan_id,in_fight=$player->in_fight WHERE id=$player->id";
                    $result = $this->connection->query($query);
                    if (!$result) {
                        die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                    }
                } else {
                    $query="INSERT INTO players (nick,frags,deaths,level,clan_id,id,in_fight) VALUES (\"$player->nick\",$player->frags,$player->deaths,$player->level,$player->clan_id,$player->id,$player->in_fight)";
                    if ($this->config["debug"]) {
                        echo $query;
                    }
                    $result = $this->connection->query($query);
                    if (!$result) {
                        die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                    }
                }
            }
        }
        foreach ($this->Fights as $tab) {
            $c1=$this->MakeList($tab->c1);
            $c2=$this->MakeList($tab->c2);
            $data = $this->connection->query("SELECT * FROM attacks WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared");
            if ($data->num_rows > 0) {
                if ($c1 && !$c2) {
                    $query="UPDATE attacks SET c1=\"$c1\", c2=NULL, in_progress=$tab->in_progress WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                }
                if (!$c1 && $c2) {
                    $query="UPDATE attacks SET c1=NULL, c2=\"$c2\", in_progress=$tab->in_progress WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                }
                if ($c1 &&  $c2) {
                    $query="UPDATE attacks SET c1=\"$c1\", c2=\"$c2\", in_progress=$tab->in_progress WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                }
                if (!$c1 &&  !$c2) {
                    goto abc;
                }
                if ($this->config["debug"]) {
                    echo $query;
                }
                $result = $this->connection->query($query);
                if (!$result) {
                    die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                }
                abc:
            } else {
                if ($c1 && !$c2) {
                    $query="INSERT INTO attacks (timemark,attacker_id,defender_id,declared,resolved,in_progress,c1,c2) VALUES (".time().",$tab->attacker_id,$tab->defender_id,$tab->declared,$tab->resolved,$tab->in_progress,\"$c1\",NULL)";
                }
                if (!$c1 && $c2) {
                    $query="INSERT INTO attacks (timemark,attacker_id,defender_id,declared,resolved,in_progress,c1,c2) VALUES (".time().",$tab->attacker_id,$tab->defender_id,$tab->declared,$tab->resolved,$tab->in_progress,NULL,\"$c2\")";
                }
                if ($c1 &&  $c2) {
                    $query="INSERT INTO attacks (timemark,attacker_id,defender_id,declared,resolved,in_progress,c1,c2) VALUES (".time().",$tab->attacker_id,$tab->defender_id,$tab->declared,$tab->resolved,$tab->in_progress,\"$c1\",\"$c2\")";
                }
                if (!$c1 &&  !$c2) {
                    $query="INSERT INTO attacks (timemark,attacker_id,defender_id,declared,resolved,in_progress,c1,c2) VALUES (".time().",$tab->attacker_id,$tab->defender_id,$tab->declared,$tab->resolved,$tab->in_progress,NULL,NULL)";
                }
                if ($this->config["debug"]) {
                    echo $query;
                }
                $result = $this->connection->query($query);
                if (!$result) {
                    die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                }
            }
        }
    }

    public function MakeList($players) // Функция перевода массива объектов игроков, находящихся в бое, в строку дял Backup
    {
        $res="";
        if ($players) {
            foreach ($players as $player) {
                if (strlen($res)<=0) {
                    $res=$player->id;
                } else {
                    $res=$res.",".$player->id;
                }
            }
        }
        return $res;
    }
    public function Log($text,$event,$fight) // Функция записи сбытия в log БД
    {
        if (strpos($text,"Fight ended")!== false){
          $time=time()+1;
        }
        else{
          $time=time();
        }
        $query="INSERT INTO logs (timemark,textt,eventt,fight) VALUES($time,\"$text\",\"$event\",\"$fight\")";
        if ($this->config["debug"]) {
            echo $query;
        }
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
    }
    public function EndFight($fight, $pos) // Функция завершения боя
    {
        if (count($fight->c1) == 0) {
            $text="Fight ended. Caln $fight->attacker_id won.";
        }
        elseif (count($fight->c2) == 0) {
            $text="Fight ended. Clan $fight->defender_id won.";
        }
        $i=0;
        foreach ($fight->c1 as $player) { // Вывод игроков из состояния "в бою"
            $fight->c1[$i]->in_fight=0;
            $i++;
        }
        $i=0;
        foreach ($fight->c2 as $player) { // Вывод игроков из состояния "в бою"
            $fight->c2[$i]->in_fight=0;
            $i++;
        }
        // Удаляем запись о данном бое из БД
        $query="DELETE FROM attacks WHERE attacker_id=$fight->attacker_id and defender_id=$fight->defender_id and resolved=$fight->resolved and declared=$fight->declared";
        if ($this->config["debug"]) {
            echo $query;
        }
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
        $event="{$this->Fights[$pos]->attacker_id} VS {$this->Fights[$pos]->defender_id} at {$this->Fights[$pos]->resolved}";
        $fight="{$this->Fights[$pos]->attacker_id},{$this->Fights[$pos]->defender_id},{$this->Fights[$pos]->declared},{$this->Fights[$pos]->resolved}";
        $this->Log($text,$event,$fight);
        unset($this->Fights[$pos]);
        sort($this->Fights);
    }
}

class Fight
{
    public $attacker_id; // id атакующего клана
    public $defender_id; // id защищающегося клана
    public $declared; // время, когда был объявлен бой
    public $resolved; // вермя, когда состаится бой
    public $in_progress; // флаг, активен ли бой
    public $c1=array(); // массив атакующих игроков
    public $c2=array(); // массив защищающихся игроков

    public function __construct($a, $d, $de, $r, $i, $c1, $c2)
    {
        $this->attacker_id=$a;
        $this->defender_id=$d;
        $this->declared=$de;
        $this->resolved=$r;
        $this->in_progress=$i;
        $this->c1=$c1;
        $this->c2=$c2;
    }

    public function StartFight($Server) // Функция старта боя
    {
        $i=0;
        $this->c1=array();
        // Выбираем атакующих игроков
        foreach ($Server->Clans[$this->attacker_id-1]->players as $player) {
            if ((rand(1, $this->config["player_add"])==1)&&($player->in_fight!=1)) {
                array_push($this->c1, null);
                $this->c1[count($this->c1)-1]=&$Server->Clans[$this->attacker_id-1]->players[$i];
                $this->c1[count($this->c1)-1]->in_fight=1;
            }
            $i++;
        }
        $i=0;
        $this->c2=array();
        // Выбираем защищающихся игроков
        foreach ($Server->Clans[$this->defender_id-1]->players as $player) {
            if ((rand(1, $this->config["player_add"])==1)&&($player->in_fight!=1)) {
                array_push($this->c2, null);
                $this->c2[count($this->c2)-1]=&$Server->Clans[$this->defender_id-1]->players[$i];
                $this->c2[count($this->c2)-1]->in_fight=1;
            }
            $i++;
        }
        $this->in_progress=1; // Отмечаем, что бой начался
        $text="Fight started";
        $event="$this->attacker_id VS $this->defender_id at $this->resolved";
        $fight="$this->attacker_id,$this->defender_id,$this->declared,$this->resolved";
        $Server->Log($text,$event,$fight);
    }

    public function Move($Server) // Функция "хода" (совершение убийства)
    {
        for ($i=0;$i<rand(1, $Server->config["max_move"]);$i++) { // Выбираем, сколько потенциальныз убийст может произайти
            if ((rand(1, $Server->config["kill_chance"]) ==1)&&($this->c1 && $this->c2)) {
                if (rand(0, 1)==1) { // Убица из первого клаан
                    $killer=array_rand($this->c1); // выбираем убийцу
                    $dead=array_rand($this->c2); // выбираем убитого
                    $this->c1[$killer]->frags++;
                    $this->c2[$dead]->deaths++;
                    $this->c2[$dead]->in_fight=0;
                    $text="{$this->c1[$killer]->nick} (id = {$this->c1[$killer]->id}, clan = {$this->c1[$killer]->clan_id}) killed {$this->c2[$dead]->nick} (id = {$this->c2[$dead]->id}, clan = {$this->c2[$dead]->clan_id})";
                    $event="$this->attacker_id VS $this->defender_id at $this->resolved";
                    $fight="$this->attacker_id,$this->defender_id,$this->declared,$this->resolved";
                    $Server->Log($text,$event,$fight);
                    unset($this->c2[$dead]);
                    sort($this->c2);
                } else { // иначе из второго
                    $killer=array_rand($this->c2); // выбираем убийцу
                    $dead=array_rand($this->c1); // выбираем убитого
                    $this->c2[$killer]->frags++;
                    $this->c1[$dead]->deaths++;
                    $this->c1[$dead]->in_fight=0;
                    $text="{$this->c2[$killer]->nick} (id = {$this->c2[$killer]->id}, clan = {$this->c2[$killer]->clan_id}) killed {$this->c1[$dead]->nick} (id = {$this->c1[$dead]->id}, clan = {$this->c1[$dead]->clan_id})";
                    $event="$this->attacker_id VS $this->defender_id at $this->resolved";
                    $fight="$this->attacker_id,$this->defender_id,$this->declared,$this->resolved";
                    $Server->Log($text,$event,$fight);
                    unset($this->c1[$dead]);
                    sort($this->c1);
                }
            }
        }
    }
}

class Clan
{
    public $id; // id клана
    public $name; // имя клана
    public $players=array(); // массив игроков состаящих в клане

    public function __construct($i, $n)
    {
        $this->id=$i;
        $this->name=$n;
    }
}

class Player
{
    public $id; // id игрока
    public $nick; // ник игрока
    public $frags; // количество его фрагов
    public $deaths; // количество его смертей
    public $level; // его уровень (пока не используется)
    public $clan_id; // id клана, в котором состоит игрок
    public $in_fight; // флаг, находится ли игрок в бою (если да, то он не может попасть в другой бой)

    public function __construct($i, $n, $f, $d, $l, $c, $fgt)
    {
        $this->id=$i;
        $this->nick=$n;
        $this->frags=$f;
        $this->deaths=$d;
        $this->level=$l;
        $this->clan_id=$c;
        $this->in_fight=$fgt;
    }
}

class Time
{
    public $saved_time; // сохраненное время

    public function __construct() // при создании объекта, запоминаем время, когда он был создан
    {
        $this->saved_time=time();
    }
    public function Update() // установить сохраненное время на  текущий момент
    {
        $this->saved_time=time();
        return $this->saved_time;
    }
    public function DeltaTime() // вернуть разницу между текущим времинем и сохраненным (в юникс секундах)
    {
        return time()-$this->saved_time;
    }
}
