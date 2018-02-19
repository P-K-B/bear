<?php
    define('_JEXEC', 1);
    require_once('server_classes.php');
    // 1 создаем подключение к бд
    $Server=new Server();
    $Time=new Time();
    $was=0;
    while (1) {
      if ($Server->Fights) {
          $was=1;
      }
        if ($Time->DeltaTime()>=$Server->config["add_time"]) {
            if ($was!=1) {
                echo "here1\n";
                $Server->NewFight();
                echo "here2\n";
                $Time->Update();
<<<<<<< HEAD
                if ($Server->Fights){
                  echo "fight created";
=======
                if ($Server->Fights) {
>>>>>>> kirill
                    $was=1;
                }
            }
        }
<<<<<<< HEAD
        $i=0;
        foreach ($Server->Fights as $fight) {
            if (($fight->resolved<=time())&&!($fight->in_progress)){
                $Server->Fights[$i]->StartFight($Server);
=======
        // $i=0;
        // // FIXME Тут явно надо идти с конца. иначе при удалении теряется один бой (сдвиг массива)
        // foreach ($Server->Fights as $fight) {
        //     if (($fight->resolved<=time())&&($fight->in_progress == 0)) {
        //         $Server->Fights[$i]->StartFight($Server);
        //     }
        //     elseif($fight->in_progress == 1){
        //       $Server->Fights[$i]->Move($Server);
        //       if ((count($Server->Fights[$i]->c1) == 0) || (count($Server->Fights[$i]->c2) == 0)){
        //         $Server->EndFight($Server->Fights[$i],$i);
        //       }
        //     }
        //     $i++;
        // }

        for ($i=count($Server->Fights)-1;$i>=0;$i--){
          if (($Server->Fights[$i]->resolved<=time())&&($Server->Fights[$i]->in_progress == 0)) {
              $Server->Fights[$i]->StartFight($Server);
          }
          elseif($Server->Fights[$i]->in_progress == 1){
            $Server->Fights[$i]->Move($Server);
            if ((count($Server->Fights[$i]->c1) == 0) || (count($Server->Fights[$i]->c2) == 0)){
              $Server->EndFight($Server->Fights[$i],$i);
>>>>>>> kirill
            }
          }
        }
<<<<<<< HEAD
        echo "Fights:\n";
        print_r($Server->Fights);
        // break;
        $Server->Backup();
        sleep($Server->getSleepTime());
=======

        print_r($Server->Fights);
        // break;
        $Server->Backup();
        $Server->Sleepp();
>>>>>>> kirill
    }

    // 2 проверяем таблицы
