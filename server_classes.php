<?php

// TODO: check '=' link for players
// TODO: Веб монитор (вероятно на основе javascropt?)
// TODO: Проверить и утвердить классы
// TODO: Написать коментарии!!!
// TODO: (когданибудь) Наверое, было бы правильно, избавиться от public...

class Server{
    public $Fights;
    public $Time;
    public $Clans=array();
    public $connection;
    public $debug;
    public $fight_rand;
    public $min_add;
    public $max_add;
    public $add_time;


    private $sleep_time;
    private $hostname;
    private $username;
    private $password;
    private $database;
    private $port;

    function __construct() {
        $config  = file_get_contents( "config.json" );
        $data = json_decode( $config, true );
        $this->hostname=$data["hostname"];
        $this->username=$data["username"];
        $this->password=$data["password"];
        $this->database=$data["database"];
        $this->port=$data["port"];
        $this->debug=$data["debug"];
        $this->fight_rand=$data["fight_rand"];
        $this->min_add=$data["min_add"];
        $this->max_add=$data["max_add"];
        $this->sleep_time=$data["sleep_time"];
        $this->add_time=$data["add_time"];

        $this->Connect();
        $this->Check_server();
        $this->Restore();
        if ($this->Clans==NULL){
            $this->NewClans();
        }
    }

    function getSleepTime(){
      return $this->sleep_time;
    }
    function NewFight(){
      echo "here11\n";
        if ($this->Clans){
          echo "here10\n";
            if (rand(0,$this->fight_rand) == 1){
              echo "here100\n";
                $rand1=rand(0,count($this->Clans)-1);
                echo "here30\n";
                rerand:
                $rand2=rand(0,count($this->Clans)-1);
                echo "here40\n";
                if ($rand1==$rand2){
                    goto rerand;
                }
                echo "here50\n";
                $add=rand($this->min_add*60,$this->max_add*60);
                $resolved=time()+$add;
                echo "here20\n";
                $attacker_id=$this->Clans[$rand1]->id;
                $defender_id=$this->Clans[$rand2]->id;
                echo "here3\n";
                $i=0;
                $c1=array();
                foreach ($this->Clans[$rand1]->players as $player) {
                    if ((rand(0,3)==0)&&($player->in_fight!=1)){
                        $this->Clans[$rand1]->players[$i]->in_fight=1;
                        array_push($c1,$this->Clans[$rand1]->players[$i]);
                    }
                    $i++;
                }
                echo "here4\n";
                $i=0;
                $c2=array();
                foreach ($this->Clans[$rand2]->players as $player) {
                    if ((rand(0,3)==0)&&($player->in_fight!=1)){
                      $this->Clans[$rand2]->players[$i]->in_fight=1;
                      array_push($c2,$this->Clans[$rand2]->players[$i]);
                    }
                    $i++;
                }
                $tmp=New Fight($attacker_id,$defender_id,time(),$resolved,0,$c1,$c2);
                array_push($this->Fights,$tmp);
            }
        }
    }

    function Connect(){
        $this->connection = new mysqli($this->hostname.$this->port, $this->username, $this->password);
      	if ($this->connection->connect_errno) die("Unable to connect to MySQL server:".$this->connection->connect_errno.$this->connection->connect_error);
      	$this->connection->query("SET NAMES 'utf8'");
        if ($this->connection && $this->debug) echo ("Connected to MySQL server.\n");
      	$this->connection->query("SET CHARACTER SET 'utf8'");
        if ($this->connection && $this->debug) echo ("Connected to MySQL server.\n");
      	$this->connection->query("SET SESSION collation_connection = 'utf8_general_ci'");
        if ($this->connection && $this->debug) echo ("Connected to MySQL server.\n");
    }

    function Check_server() {
        $query = "CREATE DATABASE IF NOT EXISTS $this->database";
        $result = $this->connection->query($query);
        if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
        $query = "USE $this->database";
        $result = $this->connection->query($query);
        if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
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
    	  if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
    	  $query = "CREATE TABLE IF NOT EXISTS clans (
    						  id smallint(5) unsigned NOT NULL,
    						  title varchar(128) DEFAULT NULL)";
        $result = $this->connection->query($query);
    	  if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
    	  $query = "CREATE TABLE IF NOT EXISTS players (
    						  id INT UNSIGNED NOT NULL UNIQUE,
    						  nick NVARCHAR(128),
    						  level SMALLINT UNSIGNED NOT NULL,
    						  frags SMALLINT UNSIGNED,
    						  deaths SMALLINT UNSIGNED,
    						  clan_id INT UNSIGNED NOT NULL,
                  in_fight int)";
        $result = $this->connection->query($query);
        if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
    }

    function NewClans(){
      echo "Made new!\n";
        $homepage = file_get_contents('names.pkb', true);
        $dt = explode("\n", $homepage);
        if ($dt[count($dt)] == NULL) {
            array_pop($dt);
        }
        if ($this->debug) print_r($dt);
        for ($i = 0; $i < count($dt); $i++) {
            for ($k = $i + 1; $k < count($dt); $k++) {
                if (($i != $k) && ($dt[$i] == $dt[$k])) {
                    $dt[$k] = NULL;
                }
            }
        }
        $unc = array();
        foreach ($dt as $i) {
          if ($i != NULL) {
              $unc[] = $i;
          }
        }
        $c = 0;
        $Clans=array();
        for ($i = 1; $i <= 10; $i++) {
            $tmp=new Clan($i,"clan$i");
            for ($k = 0; $k < 29; $k++) {
                $pl=new Player($c,$unc[$c],0,0,0,$i,0);
                array_push($tmp->players,$pl);
                $c++;
            }
            array_push($Clans,$tmp);
        }
        $this->Clans=$Clans;
    }

    function Restore(){
        $Clans=array();
        $result = $this->connection->query( "SELECT * FROM clans" );
        while ( $row = $result->fetch_assoc() ) {
            $tmp=new Clan($row['id'],$row['title']);
            array_push($Clans,$tmp);
        }
        $result = $this->connection->query( "SELECT * FROM players" );
        while ( $row = $result->fetch_assoc() ) {
            $pl=new Player($row['id'],$row['nick'],$row['frags'],$row['deaths'],$row['level'],$row['clan_id'],$row['in_fight']);
            $i=0;
            foreach ($Clans as $clan) {
                if ($clan->id==$pl->clan_id){
                  array_push($Clans[$i]->players,$pl);
                }
                $i++;
            }
        }
        $this->Clans=$Clans;
        $result = $this->connection->query( "SELECT * FROM attacks" );
        $Fights=array();
        while ( $row = $result->fetch_assoc() ) {
            $pl1=$this->FindPlayers($row['c1'],$row['attacker_id']);
            $pl2=$this->FindPlayers($row['c2'],$row['defender_id']);
            $tmp=new Fight($row['attacker_id'],$row['defender_id'],$row['declared'],$row['resolved'],$row['in_progress'],$pl1,$pl2);
            array_push($Fights,$tmp);
        }
        $this->Fights=$Fights;
    }

    function FindPlayers($ids_list,$clan_id){
        $ret=array();
        $ids=explode(',',$ids_list);
        $i=0;
        foreach ($this->Clans as $clan) {
            if ($clan->id==$clan_id){
                $j=0;
                foreach ($clan->players as $player) {
                    foreach ($ids as $id) {
                        if ($player->id==$id){
                            array_push($ret,$this->Clans[$i]->players[$j]);
                        }
                    }
                    $j++;
                }
            }
            $i++;
        }
        return $ret;
    }

    function Backup(){
        foreach ($this->Clans as $clan){
            $data = $this->connection->query( "SELECT * FROM clans WHERE id=$clan->id");
            if ($data->num_rows){
                $query="UPDATE clans SET title=\"$clan->name\" WHERE id=$clan->id";
                $result = $this->connection->query($query);
                if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
            }
            else{
              $query="INSERT INTO clans (id,title) VALUES ($clan->id,\"$clan->name\")";
              $result = $this->connection->query($query);
              if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
            }
            foreach ($clan->players as $player){
                $data = $this->connection->query( "SELECT * FROM players WHERE id=$player->id");
                if ($data->num_rows){
                    $query="UPDATE players SET nick=\"$player->nick\",frags=$player->frags,deaths=$player->deaths,level=$player->level,clan_id=$player->clan_id,in_fight=$player->in_fight WHERE id=$player->id";
                    $result = $this->connection->query($query);
                    if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                }
                else{
                  $query="INSERT INTO players (nick,frags,deaths,level,clan_id,id,in_fight) VALUES (\"$player->nick\",$player->frags,$player->deaths,$player->level,$player->clan_id,$player->id,$player->in_fight)";
                  if ($this->debug) echo $query;
                  $result = $this->connection->query($query);
                  if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                }
            }
        }
        foreach ($this->Fights as $tab){
            $c1=$this->MakeList($tab->c1);
            $c2=$this->MakeList($tab->c2);
            $data = $this->connection->query( "SELECT * FROM attacks WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared");
            if ($data->num_rows){
                $query="UPDATE attacks SET c1=$c1, c2=$c2 WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                if ($c1 && !$c2) $query="UPDATE attacks SET c1=\"$c1\", c2=NULL WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                if (!$c1 && $c2) $query="UPDATE attacks SET c1=NULL, c2=\"$c2\" WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                if ($c1 &&  $c2) $query="UPDATE attacks SET c1=\"$c1\", c2=\"$c2\" WHERE attacker_id=$tab->attacker_id and defender_id=$tab->defender_id and resolved=$tab->resolved and declared=$tab->declared";
                if (!$c1 &&  !$c2) goto abc;
                if ($this->debug) echo $query;
                $result = $this->connection->query($query);
                if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
                abc:
            }
            else{
              if ($c1 && !$c2) $query="INSERT INTO attacks (timemark,attacker_id,defender_id,declared,resolved,in_progress,c1,c2) VALUES (".time().",$tab->attacker_id,$tab->defender_id,$tab->declared,$tab->resolved,$tab->in_progress,\"$c1\",NULL)";
              if (!$c1 && $c2) $query="INSERT INTO attacks (timemark,attacker_id,defender_id,declared,resolved,in_progress,c1,c2) VALUES (".time().",$tab->attacker_id,$tab->defender_id,$tab->declared,$tab->resolved,$tab->in_progress,NULL,\"$c2\")";
              if ($c1 &&  $c2) $query="INSERT INTO attacks (timemark,attacker_id,defender_id,declared,resolved,in_progress,c1,c2) VALUES (".time().",$tab->attacker_id,$tab->defender_id,$tab->declared,$tab->resolved,$tab->in_progress,\"$c1\",\"$c2\")";
              if (!$c1 &&  !$c2) $query="INSERT INTO attacks (timemark,attacker_id,defender_id,declared,resolved,in_progress,c1,c2) VALUES (".time().",$tab->attacker_id,$tab->defender_id,$tab->declared,$tab->resolved,$tab->in_progress,NULL,NULL)";
              if ($this->debug) echo $query;
              $result = $this->connection->query($query);
              if (!$result) die("Error during creating table".$this->connection->connect_errno.$this->connection->connect_error);
            }
        }
    }

    function MakeList($players){
        $res="";
        foreach ($players as $player){
            if (strlen($res)<=0){
                $res=$player->id;
            }
            else{
                $res=$res.",".$player->id;
            }
        }
        return $res;
    }
}

class Fight{
    public $attacker_id;
    public $defender_id;
    public $declared;
    public $resolved;
    public $in_progress;
    public $c1=array();
    public $c2=array();

    function __construct($a,$d,$de,$r,$i,$c1,$c2) {
        $this->attacker_id=$a;
        $this->defender_id=$d;
        $this->declared=$de;
        $this->resolved=$r;
        $this->in_progress=$i;
        $this->c1=$c1;
        $this->c2=$c2;
    }

    function StartFight(){
        // 1) выбираем играков из первого клана


      // $data = $db_server->query( "SELECT * FROM attacks" );
      // $ext=0;
      // while ( $row = $data->fetch_assoc() ) {
      //   $c1=Get_clan_title($id1);
      //   $c2=Get_clan_title($id2);
      //   if (($row['attacker']==$c1) && ($row['defender']==$c2) && ($row['resolved']==$resolved)){
      //     // echo "\n FOUND!\n";
      //     $ext=1;
      //   }
      // }
      // if ($ext){
      //   $text='';
      //   $data = $db_server->query( "SELECT * FROM c".$id1 );
      //   while ( $row = $data->fetch_assoc() ) {
      //     if (((int)rand(0,1) == 1) && ($row['infight'] != 1)){
      //       // игрок будет играть
      //       $text=$text.(string)$row['id'].',';
      //       $db_server->query( "UPDATE c{$id1} SET infight = 1 WHERE id='{$row['id']}'");
      //     }
      //     // $db_server->query( "UPDATE c{$id1} SET infight = 0 WHERE id='{$row['id']}'");
      //   }
      //   // echo "\n".$text."\n";
      //   if ( !$db_server->query( "UPDATE attacks SET c1 = '{$text}' WHERE attacker='{$c1}' AND defender='{$c2}' AND resolved='{$resolved}'" ) ) {
      //     echo "Не удалось создать или обновить запись: (" . $db_server->errno . ") " . $db_server->error;
      //   }
      //   $bcp1=$text;
      //   $text='';
      //   $data = $db_server->query( "SELECT * FROM c".$id2 );
      //   while ( $row = $data->fetch_assoc() ) {
      //     if (((int)rand(0,1) == 1) && ($row['infight'] != 1)){
      //       // игрок будет играть
      //       $text=$text.(string)$row['id'].',';
      //       $db_server->query( "UPDATE c{$id2} SET infight = 1 WHERE id='{$row['id']}'");
      //     }
      //     // $db_server->query( "UPDATE c{$id2} SET infight = 0 WHERE id='{$row['id']}'");
      //   }
      //   // echo "\n".$text."\n";
      //   $bcp2=$text;
      //   if ( !$db_server->query( "UPDATE attacks SET c2 = '{$text}' WHERE attacker='{$c1}' AND defender='{$c2}' AND resolved='{$resolved}'" ) ) {
      //     echo "Не удалось создать или обновить запись: (" . $db_server->errno . ") " . $db_server->error;
      //   }
    }
}

class Clan{
    public $id;
    public $name;
    public $players=array();

    function __construct($i,$n) {
        $this->id=$i;
        $this->name=$n;
    }
}

class Player{
    public $id;
    public $nick;
    public $frags;
    public $deaths;
    public $level;
    public $clan_id;
    public $in_fight;

    function __construct($i,$n,$f,$d,$l,$c,$fgt) {
        $this->id=$i;
        $this->nick=$n;
        $this->frags=$f;
        $this->deaths=$d;
        $this->level=$l;
        $this->clan_id=$c;
        $this->in_fight=$fgt;
    }
}

class Time{
    private $saved_time;

    function __construct() {
        $this->saved_time=time();
    }
    function Update(){
        $this->saved_time=time();
        return $this->saved_time;
    }
    function DeltaTime(){
        return time()-$this->saved_time;
    }
}

?>
