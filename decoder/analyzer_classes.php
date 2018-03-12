<?php

class Logf // Лог файл, который присутствует в каждом бою. Туда пишутся данные о происходящем
{
    public $log; // текст происходящего
    public $timemark; // время произошедшего

    public function __construct($lg)
    {
        $this->log=NULL
        $this->timemark=time();
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

class Fight
{
    public $attacker_id; // id атакующего клана
    public $defender_id; // id защищающегося клана
    public $declared; // время, когда был объявлен бой
    public $resolved; // вермя, когда состаится бой
    public $in_progress; // флаг, активен ли бой
    public $log=array();

    public function __construct($a, $d, $de, $r, $i)
    {
        $this->attacker_id=$a;
        $this->defender_id=$d;
        $this->declared=$de;
        $this->resolved=$r;
        $this->in_progress=$i;
        $this->log=NULL;
    }
}
