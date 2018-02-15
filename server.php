<?php

    define('_JEXEC', 1);
    require_once('server_classes.php');
    // 1 создаем подключение к бд
    $Server=new Server();
    $Time=new Time();
    while(1){
        if ($Time->DeltaTime()>=$Server->add_time){
          echo "here1\n";
            $Server->NewFight();
            echo "here2\n";
            $Time->Update();
        }
        $i=0;
        foreach ($Server->Fights as $fight) {
            if (($fight->resolved<=time())&&!($fight->in_progress)){
                $Server->Fights[$i]->StartFight();
            }
            $i++;
        }
        print_r($Server->Fights);
        // break;
        $Server->Backup();
        sleep($Server->getSleepTime());
    }

    // 2 проверяем таблицы

?>
