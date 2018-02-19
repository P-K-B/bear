<?php
function findInfo($row,$player){
  $data=explode(' killed ',$row);// -> $data[0]=Johanna (id = 216, clan = 8); $data[1]=Iola (id = 109, clan = 4)
  $pl1=explode(' (',$data[0]); // $pl1[0]->nick player 1; $pl1[0]=Johanna; $pl1[1]="id = 216, clan = 8)"
  $pl2=explode(' (',$data[1]); // $pl2[0]->nick player 2; $pl2[0]=Iola; $pl2[1]="id = 109, clan = 4)"
  $pl1[1]=substr ( $pl1[1] , 0, strlen($pl1[1])-1 );  // Name1
  $pl2[1]=substr ( $pl2[1] , 0 , strlen($pl2[1])-1 );
  $data=explode(' ',$pl1[1]);
  $id1=substr ( $data[2] , 0 , strlen($data[2])-1 );
  $clan1=$data[5];//substr ( $data[5] , 0 , strlen($data[5])-1 );
  $data=explode(' ',$pl2[1]);
  $id2=substr ( $data[2] , 0 , strlen($data[2])-1 );
  $clan2=$data[5];//substr ( $data[5] , 0 , strlen($data[5])-1 );
  if($player==1){
      $inf1 =$connection->query("SELECT * FROM players  WHERE id=$id1");
      $clans =$connection->query("SELECT * FROM clans  WHERE id=$clan1");
  }else{
    $inf1 =$connection->query("SELECT * FROM players  WHERE id=$id2");
    $clans =$connection->query("SELECT * FROM clans  WHERE id=$clan2");
  }
  $tmp2=$inf1->fetch_assoc();
  $tmp4=$clans->fetch_assoc();
  $clan1name=$tmp4["title"];
  if($player==1){
      $ret = ("id"=>$id1,"name"=>$pl1[0],"frags"=>$tmp2["frags"],"lvl"=>$tmp2["level"],"deaths"=>$tmp2["death"],"clan_name"=>$clan1name);
  }
  else {
      $ret = ("id"=>$id2,"name"=>$pl2[0],"frags"=>$tmp2["frags"],"lvl"=>$tmp2["level"],"deaths"=>$tmp2["death"],"clan_name"=>$clan1name);
  }
  return $ret;
}
?>

<html>
<head>
</head>
    <body>
        <style>
            table, th, td {
                border: 1px solid black;
            }
        </style>

        <?php

            $connection;
            $file  = file_get_contents("../config.json");
            $config = json_decode($file, true);
            $connection = new mysqli($config["hostname"].$config["port"], $config["username"], $config["password"]);
            if ($connection->connect_errno) {
                die("Unable to connect to MySQL server:".$connection->connect_errno.$connection->connect_error);
            }
            // Установка параметров соединения (не уверен, что это надо)
            $connection->query("SET NAMES 'utf8'");
            $connection->query("SET CHARACTER SET 'utf8'");
            $connection->query("SET SESSION collation_connection = 'utf8_general_ci'");

            $display_string = "<table>";
            $display_string .= "<tr>";
            $display_string .= "<th>Time</th>";
            $display_string .= "<th>Text</th>";
            $display_string .= "<th>Event</th>";
            $display_string .= "</tr>";
            $query = "USE {$config["database"]}";
            $result = $connection->query($query);
            $show = $_GET['show'];
            $result=$connection->query("SELECT * FROM logs ORDER BY timemark DESC");
            if (!$result) {
                die("Error ".$connection->connect_errno.$connection->connect_error);
            }
            $i=0;
            while ($row = $result->fetch_assoc()) {
                if ($show-- >0) {
                    $display_string .= "<tr>";
                    $tm=date("Y-m-d H:i:s", $row[timemark]);
                    $display_string .= "<td>$tm</td>";

                    if(stripos($row[textt],'Fight') !== FALSE){
                      $display_string .= "<td>$row[textt]</td>"; // если строка не об взаимодействии игроков, то выводим целиком
                    }
                      else{
                        $tmp2=findInfo($row[textt],1);
                        $tmp3=findInfo($row[textt],2);
                        $player1info="id=".$tmp2["id"]." frags=".$tmp2["frags"]." death=".$tmp2["death"]." level=".$tmp2["lvl"]." clan=".$tmp2["clan_name"];
                        $player2info="id=".$tmp3["id"]." frags=".$tmp3["frags"]." death=".$tmp3["death"]." level=".$tmp3["lvl"]." clan=".$tmp3["clan_name"];
                        // echo "pl1 -> ".$pl1[0]." id-> ".$id1." clan ->". $clan1;
                        // echo "pl2 -> ".$pl2[0]." id-> ".$id2." clan2->". $clan2;


                        // $data = explode(' killed ',$row[textt]);
                        // $player1 = explode(' (',$data[0]);
                        // $player2 = explode(' (',$data[1]);
                        // $inf1 =$connection->query("SELECT * FROM players  WHERE nick=\"$player1[0]\"");
                        // $tmp2=$inf1->fetch_assoc();
                        // $player1info="id=".$tmp2["id"]." level=".$tmp2["level"];
                        // $inf2 =$connection->query("SELECT * FROM players  WHERE nick=\"$player2[0]\"");
                        // $tmp3=$inf2->fetch_assoc();
                        // $player2info="id=".$tmp3["id"]." level=".$tmp3["level"];
                        $display_string .= "<td><abbr title=\"$player1info\" rel=\"tooltip\">$pl1[0]</abbr> -> <abbr title=\"$player2info\" rel=\"tooltip\">$pl2[0]</abbr></td>";
                        $display_string . "<div id='tooltip'></div>"; // блок всплывающего окна
                  }
                    $display_string .= "<td><a href='fight.html?fight=$row[fight]'> $row[eventt]' </td>";
                    $display_string .= "</tr>";
                }
            }
            $display_string .= "</table>";
            echo $display_string;
        ?>

    </body>
</html>
