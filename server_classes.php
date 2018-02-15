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
          echo "creating new clans";
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
          echo $this->fight_rand;
            if (rand(0,$this->fight_rand) == 1){
              echo "here100\n";
                $rand1=rand(0,count($this->Clans)-1);
                echo "here30\n";
                rerand:
                $rand2=rand(0,count($this->Clans)-1);
                echo count($this->Clans)-1;
                echo "here40\n";
                if ($rand1==$rand2){
                    goto rerand;
                }
                echo "here50\n";
                $add=rand($this->min_add,$this->max_add);
                $resolved=time()+$add;
                echo "here20\n";
                $attacker_id=$this->Clans[$rand1]->id;
                $defender_id=$this->Clans[$rand2]->id;
                echo "here3\n";

                $tmp=New Fight($attacker_id,$defender_id,time(),$resolved,0,NULL,NULL);
                array_push($this->Fights,$tmp);

            }
        }
    }

    function Connect(){
        $this->connection = new mysqli($this->hostname.$this->port, $this->username, $this->password);
      	if ($this->connection->connect_errno) die("Unable to connect to MySQL server:".$this->connection->connect_errno.$this->connection->connect_error);
      	$this->connection->query("SET NAMES 'utf8'");
        if ($this->connection && $this->debug) echo ("done Set names.\n");
      	$this->connection->query("SET CHARACTER SET 'utf8'");
        if ($this->connection && $this->debug) echo ("done Set character set.\n");
      	$this->connection->query("SET SESSION collation_connection = 'utf8_general_ci'");
        if ($this->connection && $this->debug) echo ("done SET SESSION.\n");
    }

    function Check_server() {

        $query = "CREATE DATABASE IF NOT EXISTS $this->database";
        $result = $this->connection->query($query);
        if (!$result) die("Error during creating table in Check_server".$this->connection->connect_errno.$this->connection->connect_error);
        $query = "USE $this->database";
        $result = $this->connection->query($query);
        if (!$result) die("Error during creating table in Check_server".$this->connection->connect_errno.$this->connection->connect_error);
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
    	  if (!$result) die("Error during creating table in Check_server".$this->connection->connect_errno.$this->connection->connect_error);
    	  $query = "CREATE TABLE IF NOT EXISTS clans (
    						  id smallint(5) unsigned NOT NULL,
    						  title varchar(128) DEFAULT NULL)";
        $result = $this->connection->query($query);
    	  if (!$result) die("Error during creating table in Check_server".$this->connection->connect_errno.$this->connection->connect_error);
    	  $query = "CREATE TABLE IF NOT EXISTS players (
    						  id INT UNSIGNED NOT NULL UNIQUE,
    						  nick NVARCHAR(128),
    						  level SMALLINT UNSIGNED NOT NULL,
    						  frags SMALLINT UNSIGNED,
    						  deaths SMALLINT UNSIGNED,
    						  clan_id INT UNSIGNED NOT NULL,
                  in_fight int)";
        $result = $this->connection->query($query);
        if (!$result) die("Error during creating table in Check_server".$this->connection->connect_errno.$this->connection->connect_error);
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
                $tmp->addPlayer($pl);
                // array_push($tmp->players,$pl);
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
                  $Clans[$i]->addPlayer($pl);
                  // array_push($Clans[$i]->players,$pl);
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
            $id=$clan->id;
            $name=$clan->name;
            $data = $this->connection->query( "SELECT * FROM clans WHERE id=$id");
            if ($data->num_rows){
                $query="UPDATE clans SET title=\"$name\" WHERE id=$id";
                $result = $this->connection->query($query);
                if (!$result) die("Error during creating table in Backup 1".$this->connection->connect_errno.$this->connection->connect_error);
            }
            else{
              $query="INSERT INTO clans (id,title) VALUES ($id,\"$name\")";
              echo $query;
              $result = $this->connection->query($query);
              if (!$result) die("Error during creating table in Backup 2".$this->connection->connect_errno.$this->connection->connect_error);
            }
            foreach ($clan->players as $player){
              $nick = $player->nick;
              $frags = $player->frags;
              $deaths =$player->deaths;
              $level=$player->level;
              $clan_id=$player->clan_id;
              $in_fight=$player->in_fight;
              $id=$player->id;
                $data = $this->connection->query( "SELECT * FROM players WHERE id=$id");
                if ($data->num_rows){
                    $query="UPDATE players SET nick=\"$nick\",frags=$frags,deaths=$deaths,level=$level,clan_id=$clan_id,in_fight=$in_fight WHERE id=$id";
                    $result = $this->connection->query($query);
                    if (!$result) die("Error during creating table in Backup 3".$this->connection->connect_errno.$this->connection->connect_error);
                }
                else{
                  $query="INSERT INTO players (nick,frags,deaths,level,clan_id,id,in_fight) VALUES (\"$nick\",$frags,$deaths,$level,$clan_id,$id,$in_fight)";
                  if ($this->debug) echo $query;
                  $result = $this->connection->query($query);
                  echo "$result\n";
                  if (!$result) die("Error during creating table in Backup 4".$this->connection->connect_errno.$this->connection->connect_error);
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
        if ($players){
            foreach ($players as $player){
                if (strlen($res)<=0){
                    $res=$player->id;
                }
                else{
                    $res=$res.",".$player->id;
                }
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

    function StartFight($Server){
        $i=0;
        $this->c1=array();
        foreach ($Server->Clans[$this->attacker_id]->players as $player) {
            if ((rand(0,3)==0)&&(!$player->in_fight)){
                array_push($this->c1,NULL);
                $this->c1[count($this->c1)-1]=&$Server->Clans[$this->attacker_id]->players[$i];
                $this->c1[count($this->c1)-1]->$in_fight=1;
            }
            $i++;
        }
        echo "\n";
        $i=0;
        $this->c2=array();
        foreach ($Server->Clans[$this->defender_id]->players as $player) {
            if ((rand(0,3)==0)&&(!$player->in_fight)){
              array_push($this->c2,NULL);
              $this->c2[count($this->c2)-1]=&$Server->Clans[$this->defender_id]->players[$i];
              $this->c2[count($this->c2)-1]->$in_fight=1;
            }
            $i++;
        }
        $this->in_progress=1;
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

    function removePlayer($id){
      $key = array_search($id,$this->players);

      if($key){
        unset($this->players[$key]);
        sort($this->players);
        return 0;
      }
      else return -1;

    }
    function addPlayer($id){
      $key = array_search($id,$this->players);
      if($key) return -1;
      else {
        array_push($this->players, $id);
        sort($this->players);
        return 0;
      }
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

    function Killed(){
      $this->frags++;
    }
    function Dead(){
      $this->in_fight=0;
      $this->deaths++;
    }
    function LevelUp(){
      $this->level++;
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
