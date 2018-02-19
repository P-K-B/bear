        <?php
        class Monitor{
          public $connection; // Объект, отвечающий за подключение к БД
          public $config;
          public function __construct()
          {
              $file  = file_get_contents("../config.json");
              $this->config = json_decode($file, true);
              $this->Connect();
          }
           function Connect() // Функция подключения к БД
          {
              $this->connection = new mysqli($this->config["hostname"].$this->config["port"], $this->config["username"], $this->config["password"]);
              if ($this->connection->connect_errno) {
                  die("Unable to connect to MySQL server:".$this->connection->connect_errno.$this->connection->connect_error);
              }
              // Установка параметров соединения (не уверен, что это надо)
              $this->connection->query("SET NAMES 'utf8'");
              $this->connection->query("SET CHARACTER SET 'utf8'");
              $this->connection->query("SET SESSION collation_connection = 'utf8_general_ci'");
          }

          //  Функция вывода информации об игроке из строки лога
          function findInfo($row,$player){
            $data=explode(' killed ',$row);
            if($player==1){
                $data=$data[0];
            }
            else {
                $data=$data[1];
            }
            $pl=explode(' (',$data);
            $pl[1]=substr ( $pl[1] , 0, strlen($pl[1])-1 );
            $data=explode(' ',$pl[1]);
            $id=substr($data[2],0,strlen($data[2])-1);
            $clan=$data[5];

            $result =$this->connection->query("SELECT * FROM players  WHERE id=$id");
            $tmp=$result->fetch_assoc();
            $result =$this->connection->query("SELECT * FROM clans  WHERE id=$clan");
            $tmp4=$result->fetch_assoc();
            $clanname=$tmp4["title"];
              $ret = array("id"=>$id,
                           "name"=>$pl[0],
                           "frags"=>$tmp["frags"],
                           "lvl"=>$tmp["level"],
                           "deaths"=>$tmp["deaths"],
                           "clan_name"=>$clanname);
            return $ret;
        }

        // Функция вывода таблицы лога
        function displayTable(){
          $show = $_GET['show'];
          $display_string = "<table><tr><th>Time</th><th>Text</><th>Event</th></tr>";
          $query = "USE {$this->config["database"]}";
          $result = $this->connection->query($query);
          if (!$result) {
              die("Error ".$this->connection->connect_errno.$this->connection->connect_error);
            }
          $result=$this->connection->query("SELECT * FROM logs ORDER BY timemark DESC");
          if (!$result) {
            die("Error-> ".$this->connection->connect_errno.$this->connection->connect_error);
          }
          $i=0;
          while ($row = $result->fetch_assoc()) {
              if ($show-- >0) {
                  $tm=date("Y-m-d H:i:s", $row[timemark]);
                    $display_string .= "<tr><td>$tm</td>";

                    if(stripos($row[textt],'Fight') !== FALSE){
                      $display_string .= "<td>$row[textt]</td>"; // если строка не об взаимодействии игроков, то выводим целиком
                    }
                    else{
                        $tmp2=$this->findInfo($row[textt],1);
                        $tmp3=$this->findInfo($row[textt],2);
                        $player1info="id=".$tmp2["id"]." frags=".$tmp2["frags"]." death=".$tmp2["deaths"]." level=".$tmp2["lvl"]." clan=".$tmp2["clan_name"];
                        $player2info="id=".$tmp3["id"]." frags=".$tmp3["frags"]." death=".$tmp3["deaths"]." level=".$tmp3["lvl"]." clan=".$tmp3["clan_name"];
                        $player1 = $tmp2["name"];
                        $player2 = $tmp3["name"];
                        $display_string .= "<td><abbr title=\"$player1info\" rel=\"tooltip\">$player1</abbr> -> <abbr title=\"$player2info\" rel=\"tooltip\">$player2</abbr></td>";
                        $display_string . "<div id='tooltip'></div>"; // блок всплывающего окна
                      }
              $display_string .= "<td><a href='fight.html?fight=$row[fight]'> $row[eventt]' </td>";
              $display_string .= "</tr>";
            }

          }
          $display_string .= "</table>";
          echo $display_string;
          }
  }

  //  Испоняемая часть файла
        $monitor = new Monitor();
        $monitor->displayTable();
        ?>
