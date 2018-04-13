<?php
define('_JEXEC', 1);
require_once('analyzer_Functions.php');
require_once('analyzer_FunctionsWithDatabase.php');
require_once('analyzer_classes.php');
require_once('data.php');
// 1 создаем подключение к бд
$Analyzer=new Analyzer();
$Analyzer->UpdateClans();
$Analyzer->UpdatePlayers();
while ($Analyzer->config["run"]==1) {
    $Analyzer->UpdateConfig();
    $Analyzer->UpdateList();
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
    print_r($Analyzer->Fights);

    echo "+========================+\n";
    $Analyzer->Backup();
    $Analyzer->Sleepp();
}
$Analyzer->Close();
