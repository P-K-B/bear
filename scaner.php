<?php


function GetClans()
{
    $file  = file_get_contents("http://berserktcg.ru/api/export/clans.json");
    $json = json_decode($file, true);
    return $json;
}

function GetClanData($id)
{
    $file  = file_get_contents("config.json");
    $config = json_decode($file, true);
    $file  = file_get_contents("http://berserktcg.ru/api/export/clan/".$id.".json");
    $json = json_decode($file, true);
    return $json;
}

function GetFights()
{
    $file  = file_get_contents("config.json");
    $config = json_decode($file, true);
    $file  = file_get_contents("http://berserktcg.ru/api/export/attacks.json");
    $json = json_decode($file, true);
    return $json;
}



$slp=60;

while (1) {
    $time= date('l-jS-\of-F-Y-h:i:s-A');
    $clans=GetClans();
    print_r($clans);
    $path=realpath(dirname(__FILE__))."/DATA/$time";
    exec("mkdir $path");
    $query=" wget -O \"$path/clans_$time.json\" http://berserktcg.ru/api/export/clans.json";
    exec($query);
    $query=" wget -O \"$path/fights_$time.json\" http://berserktcg.ru/api/export/attacks.json";
    exec($query);
    foreach ($clans as $clan) {
        $query=" wget -O \"$path/clan[{$clan['id']}]_$time.json\" http://berserktcg.ru/api/export/clan/".$clan['id'].".json";
        exec($query);
    }
    for ($i=1;$i<=$slp;$i++) {
        echo "$time Sleeping for $i of $slp seconds\n";
        sleep(1);
    }
}
