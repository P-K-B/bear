<?php

    define('_JEXEC', 1);
    require_once('server_classes.php');
    require_once("data.php");
    // 1 создаем подключение к бд
    $Server=new Server();
    $Time=new Time();
    $may_add_fight=0;
    while (1) {
        $Server->UpdateConfig();
        if (count($Server->Fights) >= $Server->config["max_fights_at_a_time"]) {
            $may_add_fight=1;
        } else {
            $may_add_fight=0;
        }
        if ($Time->DeltaTime()>=$Server->config["add_time"]) {
            if ($may_add_fight!=1) {
                echo "here1\n";
                $Server->NewFight();
                echo "here2\n";
                $Time->Update();
                if (count($Server->Fights) >= $Server->config["max_fights_at_a_time"]) {
                    $may_add_fight=1;
                }
            }
        }
        print_r($Server->Fights);
        // if (count($Server->Fights) >= 1){
        //     for ($i=count($Server->Fights)-1;$i>=0;$i--) {
        //         if (($Server->Fights[$i]->resolved<=time()) && ($Server->Fights[$i]->in_progress == 0)) {
        //             echo "\n I->$i \n";
        //             $Server->Fights[$i]->StartFight($Server);
        //         } elseif ($Server->Fights[$i]->in_progress == 1) {
        //             $Server->Fights[$i]->Move($Server);
        //             if ((count($Server->Fights[$i]->c1) == 0) || (count($Server->Fights[$i]->c2) == 0)) {
        //                 $Server->EndFight($Server->Fights[$i], $i);
        //             }
        //         }
        //     }
        // }

        if (count($Server->Fights) >= 1) {
            foreach ($Server->Fights as $key => $fight) {
                // for ($i=count($Server->Fights)-1;$i>=0;$i--) {
                if (($fight->resolved<=time()) && ($fight->in_progress == 0)) {
                    echo "\n I->$i \n";
                    $fight->StartFight($Server);
                } elseif ($fight->in_progress == 1) {
                    $fight->Move($Server);
                    if ((count($fight->c1) == 0) || (count($fight->c2) == 0)) {
                        $Server->EndFight($fight);
                        unset($Server->Fights[$key]);
                        sort($Server->Fights);
                    }
                }
            }
        }

        // break;
        $Server->Backup();
        if (count($Server->Fights) < 1) {
            if ($Server->Check_Players()) {
                echo "\nERROR!!! SOMEONE IS IN A FIGHT!!!\n";
            }
        }
        $Server->Sleepp();
    }
    print_r($Server);
        $Server->Backup();

    // 2 проверяем таблицы
