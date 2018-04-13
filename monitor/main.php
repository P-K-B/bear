        <?php
        class Monitor
        {
            public $connection; // Объект, отвечающий за подключение к БД
            public $config;
            public function __construct()
            {
                $file  = file_get_contents(realpath(dirname(__FILE__))."/../config.json");
                $this->config = json_decode($file, true);
                $this->Connect();
            }
            public function Connect() // Функция подключения к БД
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
            public function findInfo($row, $player)
            {
                $data=explode(';', $row);
                $ret = array("id"=>$data[1],
                           "name"=>$data[0],
                           "frags"=>$data[3],
                           "lvl"=>$data[2],
                           "deaths"=>$data[4],
                           "clan_name"=>$data[5]);
                return $ret;
            }

            // Функция вывода таблицы лога
            public function displayTable()
            {
                $show = $_GET['show'];
                $query = "USE {$this->config["emulator_database"]}";
                $result = $this->connection->query($query);
                if (!$result) {
                    die("Error ".$this->connection->connect_errno.$this->connection->connect_error);
                }


                $display_string = "<table>";
                $display_string .= "<tr>";
                $display_string .= "<th>Attacker</th>";
                $display_string .= "<th>Defender</th>";
                $display_string .= "<th>Declared</th>";
                $display_string .= "<th>Resolved</th>";
                $display_string .= "<th>In_progress</th>";
                $display_string .= "<th>C1</th>";
                $display_string .= "<th>C2</th>";
                $display_string .= "<th>Watch</th>";
                $display_string .= "</tr>";
                // $query = "USE {$config["database"]}";
                // $result = $connection->query($query);
                // $show = $_GET['show'];
                $result=$this->connection->query("SELECT * FROM attacks ORDER BY timemark DESC");
                if (!$result) {
                    die("Error ".$this->connection->connect_errno.$this->connection->connect_error);
                }
                while ($row = $result->fetch_assoc()) {
                    $display_string .= "<tr>";
                    $tm=date("Y-m-d H:i:s", $row[timemark]);
                    $display_string .= "<td>$row[attacker_id]</td>";
                    $display_string .= "<td>$row[defender_id]</td>";
                    $display_string .= "<td>$row[declared]</td>";
                    $display_string .= "<td>$row[resolved]</td>";
                    $display_string .= "<td>$row[in_progress]</td>";
                    $display_string .= "<td>$row[c1]</td>";
                    $display_string .= "<td>$row[c2]</td>";
                    $display_string .= "<td><a href='fight.html?fight=$row[attacker_id],$row[defender_id],$row[declared],$row[resolved]'> fight </td>";
                    // $display_string .= "<td> <div id='form'> <form action='fight.html?fight=$row[fight]&show=$show' method='post'>
                    // <input type='submit' name='submit' value='$row[eventt]' >
                    // </div> </td>";
                    $display_string .= "</tr>";
                }
                $display_string .= "</table>";

                echo $display_string;
                echo "<h3> ";


                $display_string = "<table><tr><th>Time</th><th>Text</><th>Event</th></tr>";
                $result=$this->connection->query("SELECT * FROM logs ORDER BY timemark DESC");
                if (!$result) {
                    die("Error-> ".$this->connection->connect_errno.$this->connection->connect_error);
                }
                $i=0;
                while ($row = $result->fetch_assoc()) {
                    if ($show-- >0) {
                        $tm=date("Y-m-d H:i:s", $row[timemark]);
                        $display_string .= "<tr><td>$tm</td>";

                        if (stripos($row[text1], 'Fight') !== false) {
                            $data=explode('.', $row[text1]);
                            $display_string .= "<td><font color='magenta'>$data[0]"; // если строка не об взаимодействии игроков, то выводим целиком
                      $display_string .= "<font color='black'>.$data[1]</td>"; // если строка не об взаимодействии игроков, то выводим целиком
                        } else {
                            $tmp2=$this->findInfo($row[text1], 1);
                            $tmp3=$this->findInfo($row[text2], 2);
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
