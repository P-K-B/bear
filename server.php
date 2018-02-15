<?php
    define('_JEXEC', 1);
    require_once('server_classes.php');
    // 1 создаем подключение к бд
    $Server=new Server();
    $Time=new Time();
    $was=0;
    while(1){
        if ($Time->DeltaTime()>=$Server->add_time){
            if (!$was){
                echo "here1\n";
                $Server->NewFight();
                echo "here2\n";
                $Time->Update();
                if ($Server->Fights){
                  echo "fight created";
                    $was=1;
                }
            }
        }
        $i=0;
        foreach ($Server->Fights as $fight) {
            if (($fight->resolved<=time())&&!($fight->in_progress)){
                $Server->Fights[$i]->StartFight($Server);
            }
            $i++;
        }
        echo "Fights:\n";
        print_r($Server->Fights);
        // break;
        $Server->Backup();
        sleep($Server->getSleepTime());
    }

    // 2 проверяем таблицы

?>
