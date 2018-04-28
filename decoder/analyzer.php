<?php
define('_JEXEC', 1);
require_once('analyzer_Functions.php');
require_once('analyzer_FunctionsWithDatabase.php');
require_once('analyzer_classes.php');
require_once('data.php');
require_once('console/console.php');
// define('_JEXEC', 1);
require_once('server_classes.php');
// require_once("data.php");
// 1 создаем подключение к бд

$del=0;
$long=0;
$total=0;
foreach ($argv as $value) {
    if ($value == "-d") {
        $del=1;
    }
    if ($value == "-l") {
        $long=1;
    }
}

$Server=new Server($del);
$Time=new SV_Time();
$may_add_fight=0;
// 1 создаем подключение к бд
$Analyzer=new Analyzer($del);
$Analyzer->UpdateClans();
$Analyzer->UpdatePlayers();

$debug_killers=array();
$debug_deads=array();
$run=0;
$killers_errors=0;
$deads_errors=0;
$total_errors=0;
while ($Analyzer->config["run"]==1) {
    $Server->Step($may_add_fight, $Time);


    $Analyzer->UpdateConfig();
    $Analyzer->UpdateList();
    $Analyzer->UpdateClans();
    // foreach ($Analyzer->Update_list as $clan) {
    //     $clan->UpdatePlayers();
    // }
    $Analyzer->Match2();
    // foreach ($Analyzer->Update_list as $clan) {
    // echo "for clan $clan->id:\n";
    // $vars=$Analyzer->GetVars($clan->id);
    // print_r($vars);
    // if (count($clan->killers)!=0) {
    //     throw new Exception('Ктото в бою!');
    // }
    // if (count($clan->deads)!=0) {
    //     throw new Exception('Ктото в бою!');
    // }
    // }
    $Analyzer->UpdateFights();
    foreach ($Analyzer->Fights as $fight) {
        if (($fight->resolved<=time()) && ($fight->in_progress !=1)) {
            $fight->in_progress=1;
        }
    }
    // print_r($Analyzer->Update_list);
    // print_r($Analyzer->Fights);

    $Analyzer->Backup();




    // foreach ($Server->debug_info[0] as $value) {
    //     array_push($debug_killers, $value);
    // }
    // foreach ($Server->debug_info[1] as $value) {
    //     array_push($debug_deads, $value);
    // }

    // print_r($debug_killers);
    // print_r($debug_deads);
    if (count($Server->debug_info[0])!=count($Server->debug_info[1])) {
        echo Console::log("ERROR!!\n", 'red');
        echo Console::bell();
        break;
    }
    if (count($Server->debug_info[0])!=count($Analyzer->debug_info[0])) {
        echo Console::log("ERROR!!\n", 'red');
        echo Console::bell();
        $killers_errors+=abs(count($Server->debug_info[0])-count($Analyzer->debug_info[0]));
    }
    if (count($Server->debug_info[1])!=count($Analyzer->debug_info[1])) {
        echo Console::log("ERROR!!\n", 'red');
        echo Console::bell();
        $deads_errors+=abs(count($Server->debug_info[1])-count($Analyzer->debug_info[1]));
    }
    $total+=count($Server->debug_info[0]);
    if ($total!=$Analyzer->total) {
        echo Console::log("ERROR!!\n", 'red');
        echo Console::bell();
        $total_errors+=abs($total-$Analyzer->total);
    }
    $run++;
    

    // $total+=count($Server->debug_info[1]);

    echo "+========================+\n";
    echo "Run: {$run}; Fights played: $Analyzer->fights_played\n";
    echo "Server has ".count($Server->debug_info[0])." killers and analyzer has ".count($Analyzer->debug_info[0])." killers; errors: $killers_errors \n";
    echo "Server has ".count($Server->debug_info[1])." deads and analyzer has ".count($Analyzer->debug_info[1])." deads; errors: $deads_errors \n";
    echo "Total server killes: $total. Total analyzer killes: $Analyzer->total; errors: $total_errors\n";
    // $Server->Pause();

//     $Analyzer->Sleepp();
}
// $Analyzer->Close();


$Server->Backup();
