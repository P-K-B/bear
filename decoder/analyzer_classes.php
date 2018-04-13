<?php

class Logf // Лог файл, который присутствует в каждом бою. Туда пишутся данные о происходящем
{
    public $log; // текст происходящего
    public $timemark; // время произошедшего

    public function __construct()
    {
        $this->log=null;
        $this->timemark=time();
    }
}

class Clan
{
    public $id; // id клана
    public $name; // имя клана
    public $players=array(); // массив игроков состаящих в клане
    public $killers=array();
    public $deads=array();

    public function __construct($i, $n)
    {
        $this->id=$i;
        $this->name=$n;
    }
    public function UpdatePlayers()
    {
        $data=GetClanData($this->id);
        foreach ($data["players"] as $player_tmp) {
            $pl=new Player($player_tmp["id"], $player_tmp["nick"], $player_tmp["frags"], $player_tmp["deaths"], $player_tmp["level"], $this->id);
            $was=0;
            foreach ($this->players as $player) {
                if ($player->id==$pl->id) {
                    $was=1;
                    $tmp=$player->Update($pl);
                    for ($i=0;$i<$tmp["frags"];$i++) {
                        echo $player->nick." is a killer!\n";
                        array_push($this->killers, null);
                        $this->killers[count($this->killers)-1]=$player;
                    }
                    for ($i=0;$i<$tmp["deaths"];$i++) {
                        echo $player->nick." is a dead!\n";
                        array_push($this->deads, null);
                        $this->deads[count($this->deads)-1]=$player;
                    }
                }
            }
            if ($was !=1) {
                array_push($this->players, $pl);
            }
        }
    }

    public function unsett_killer($id)
    {
        foreach ($this->killers as $key => $player) {
            if ($player->id==$id) {
                unset($this->killers[$key]);
            }
        }
    }
    public function unsett_dead($id)
    {
        foreach ($this->dead as $key => $player) {
            if ($player->id==$id) {
                unset($this->dead[$key]);
            }
        }
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

    public function __construct($i, $n, $f, $d, $l, $c)
    {
        $this->id=$i;
        $this->nick=$n;
        $this->frags=$f;
        $this->deaths=$d;
        $this->level=$l;
        $this->clan_id=$c;
    }
    public function Update($pl)
    {
        $ret=["frags"=>$pl->frags-$this->frags,"deaths"=>$pl->deaths-$this->deaths];
        $this->nick=$pl->nick;
        $this->frags=$pl->frags;
        $this->deaths=$pl->deaths;
        $this->level=$pl->level;
        return $ret;
    }
    public function CP()
    {
        $pl=new Player($this->id, $this->nick, $this->frags, $this->deaths, $this->level, $this->clan_id);
        return $pl;
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

class Fight
{
    public $id;
    public $attacker_id; // id атакующего клана
    public $defender_id; // id защищающегося клана
    public $declared; // время, когда был объявлен бой
    public $resolved; // вермя, когда состаится бой
    public $in_progress; // флаг, активен ли бой
    public $log=array();
    public $updated=0;
    public $tickets=array();

    public function __construct($id, $a, $d, $de, $r, $u)
    {
        $this->id=$id;
        $this->attacker_id=$a;
        $this->defender_id=$d;
        $this->declared=$de;
        $this->resolved=$r;
        $this->in_progress=0;
        $this->updated=$u;
    }
}

class Analyzer
{
    use Functions;
    use FunctionsWithDatabase;
    public $config;
    public $Clans=array();
    public $Fights=array();
    public $Time;
    public $connection;
    public $Update_list=array();
    public $log;
    public $delete=0;
    public $attack_id=1;

    public function __construct()
    {
        exec("rm log.txt");
        $this->log = fopen("log.txt", "x+");
        $this->UpdateConfig();
        $this->Connect();
        $this->Check_server();
        $this->Restore();
    }
    public function UpdateConfig()
    {
        $file  = file_get_contents(realpath(dirname(__FILE__))."/../config.json");
        $this->config = json_decode($file, true);
    }
}

class Ticket
{
    public $fight_id;
    public $killers=array();
    public $deads=array();

    public function __construct($id, $kil, $dead)
    {
        $this->fight_id=$id;
        $this->killers=$kil;
        foreach ($this->killers as $key=>$killer) {
            $this->killers[$key]=$killer->CP();
        }
        $this->deads=$dead;
        foreach ($this->deads as $key=>$dead) {
            $this->deads[$key]=$dead->CP();
        }
    }

    public function GetKillers()
    {
        $i=1;
        $ret;
        foreach ($this->killers as $killer) {
            if ($i==1) {
                $ret=$killer->id;
                $i++;
            } else {
                $ret.=";".$killer->id;
            }
        }
        return $ret;
    }

    public function GetDeads()
    {
        $i=1;
        $ret;
        foreach ($this->deads as $dead) {
            if ($i==1) {
                $ret=$dead->id;
                $i++;
            } else {
                $ret.=";".$dead->id;
            }
        }
        return $ret;
    }
}
