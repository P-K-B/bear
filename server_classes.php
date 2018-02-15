<?php

// TODO: Веб монитор (вероятно на основе javascropt?)
// TODO: Проверить и утвердить классы
// TODO: Написать коментарии!!!
// TODO: (когданибудь) Наверое, было бы правильно, избавиться от public...

class Server
{
    public $Fights;
    public $Time;
    public $Clans=array();
    public $connection;

    public $config;

    public function Sleepp()
    {
        sleep($this->config["sleep_time"]);
    }

    public function AddTime()
    {
        return $this->config["add_time"];
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

    public function NewFight()
    {
        if ($this->Clans) {
            if (rand(1, $this->config["fight_rand"]) == 1) {
                $rand1=rand(0, count($this->Clans)-1);
                rerand:
                $rand2=rand(0, count($this->Clans)-1);
                if ($rand1==$rand2) {
                    goto rerand;
                }
                $add=rand($this->config["min_add"], $this->config["max_add"]);
                $resolved=time()+$add;
                $attacker_id=$this->Clans[$rand1]->id;
                $defender_id=$this->Clans[$rand2]->id;
                $tmp=new Fight($attacker_id, $defender_id, time(), $resolved, 0, null, null);
                array_push($this->Fights, $tmp);
            }
        }
    }

    public function Connect()
    {
        $this->connection = new mysqli($this->config["hostname"].$this->config["port"], $this->config["username"], $this->config["password"]);
        if ($this->connection->connect_errno) {
            die("Unable to connect to MySQL server:".$this->connection->connect_errno.$this->connection->connect_error);
        }
        $this->connection->query("SET NAMES 'utf8'");
        $this->connection->query("SET CHARACTER SET 'utf8'");
        $this->connection->query("SET SESSION collation_connection = 'utf8_general_ci'");
        if ($this->connection && $this->config["debug"]) {
            echo("Connected to MySQL server.\n");
        }
    }

    public function Check_server()
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
        $query = "CREATE TABLE IF NOT EXISTS log (
                  timemark int,
    						  eventt NVARCHAR(128))";
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
    }

    public function NewClans()
    {
        echo "Made new!\n";
        $homepage = file_get_contents('names.pkb', true);
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

    public function Restore()
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

    public function FindPlayers($ids_list, $clan_id)
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
                            array_push($ret, $this->Clans[$i]->players[$j]);
                        }
                    }
                    $j++;
                }
            }
            $i++;
        }
        return $ret;
    }

    public function Backup()
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
                $query="UPDATE attacks SET c1=$c1, c2=$c2 WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                if ($c1 && !$c2) {
                    $query="UPDATE attacks SET c1=\"$c1\", c2=NULL WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                }
                if (!$c1 && $c2) {
                    $query="UPDATE attacks SET c1=NULL, c2=\"$c2\" WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                }
                if ($c1 &&  $c2) {
                    $query="UPDATE attacks SET c1=\"$c1\", c2=\"$c2\" WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
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

    public function MakeList($players)
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
    public function Log($event)
    {
        $query="INSERT INTO log (timemark,eventt) VALUES(".time().",\"$event\")";
        if ($this->config["debug"]) {
            echo $query;
        }
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
    }
    public function EndFight($fight, $pos)
    {
        $i=0;
        foreach ($fight->c1 as $player) {
            $fight->c1[$i]->in_fight=0;
            $i++;
        }
        $i=0;
        foreach ($fight->c2 as $player) {
            $fight->c2[$i]->in_fight=0;
            $i++;
        }
        $query="DELETE FROM attacks WHERE attacker_id=$fight->attacker_id and defender_id=$fight->defender_id and resolved=$fight->resolved and declared=$fight->declared";
        if ($this->config["debug"]) {
            echo $query;
        }
        $result = $this->connection->query($query);
        if (!$result) {
            die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        }
        unset($this->Fights[$pos]);
        sort($this->Fights);
    }
}

class Fight
{
    public $attacker_id;
    public $defender_id;
    public $declared;
    public $resolved;
    public $in_progress;
    public $c1=array();
    public $c2=array();

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

    public function StartFight($Server)
    {
        $i=0;
        $this->c1=array();
        foreach ($Server->Clans[$this->attacker_id]->players as $player) {
            if ((rand(1, $this->config["player_add"])==1)&&($player->in_fight!=1)) {
                array_push($this->c1, null);
                $this->c1[count($this->c1)-1]=&$Server->Clans[$this->attacker_id]->players[$i];
                $this->c1[count($this->c1)-1]->in_fight=1;
            }
            $i++;
        }
        $i=0;
        $this->c2=array();
        foreach ($Server->Clans[$this->defender_id]->players as $player) {
            if ((rand(1, $this->config["player_add"])==1)&&($player->in_fight!=1)) {
                array_push($this->c2, null);
                $this->c2[count($this->c2)-1]=&$Server->Clans[$this->defender_id]->players[$i];
                $this->c2[count($this->c2)-1]->in_fight=1;
            }
            $i++;
        }
        $this->in_progress=1;
    }

    public function Move($Server)
    {
        for ($i=0;$i<rand(1, $Server->config["max_move"]);$i++) {
            if ((rand(1, $Server->config["kill_chance"]) ==1)&&($this->c1 && $this->c2)) {
                if (rand(0, 1)==1) {
                    $killer=array_rand($this->c1);
                    $dead=array_rand($this->c2);
                    $this->c1[$killer]->frags++;
                    $this->c2[$dead]->deaths++;
                    $this->c2[$dead]->in_fight=0;
                    $event="{$this->c1[$killer]->nick} (id = {$this->c1[$killer]->id}, clan = {$this->c1[$killer]->clan_id}) killed {$this->c2[$dead]->nick} (id = {$this->c2[$dead]->id}, clan = {$this->c2[$dead]->clan_id})";
                    $Server->Log($event);
                    unset($this->c2[$dead]);
                    sort($this->c2);
                } else {
                    $killer=array_rand($this->c2);
                    $dead=array_rand($this->c1);
                    $this->c2[$killer]->frags++;
                    $this->c1[$dead]->deaths++;
                    $this->c1[$dead]->in_fight=0;
                    $event="{$this->c2[$killer]->nick} (id = {$this->c2[$killer]->id}, clan = {$this->c2[$killer]->clan_id}) killed {$this->c1[$dead]->nick} (id = {$this->c1[$dead]->id}, clan = {$this->c1[$dead]->clan_id})";
                    $Server->Log($event);
                    unset($this->c1[$dead]);
                    sort($this->c1);
                }
            }
        }
    }
}

class Clan
{
    public $id;
    public $name;
    public $players=array();

    public function __construct($i, $n)
    {
        $this->id=$i;
        $this->name=$n;
    }
}

class Player
{
    public $id;
    public $nick;
    public $frags;
    public $deaths;
    public $level;
    public $clan_id;
    public $in_fight;

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
    public $saved_time;

    public function __construct()
    {
        $this->saved_time=time();
    }
    public function Update()
    {
        $this->saved_time=time();
        return $this->saved_time;
    }
    public function DeltaTime()
    {
        return time()-$this->saved_time;
    }
}
