<html>
    <body>
        <style>
            table, th, td {
                border: 1px solid black;
            }
        </style>

        <?php
            $data = $_GET['fight'];
            $dt=explode(',', $data);
            $attacker=$dt[0];
            $defender=$dt[1];
            $declared=$dt[2];
            $resolved=$dt[3];
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
            $display_string .= "<th>Attacker</th>";
            $display_string .= "<th>Defender</th>";
            $display_string .= "<th>Declared</th>";
            $display_string .= "<th>Resolved</th>";
            $display_string .= "<th>In_progress</th>";
            $display_string .= "<th>C1</th>";
            $display_string .= "<th>C2</th>";
            $display_string .= "</tr>";
            $query = "USE {$config["database"]}";
            $result = $connection->query($query);
            $show = $_GET['show'];
            $result=$connection->query("SELECT * FROM attacks WHERE attacker_id=$attacker AND defender_id=$defender AND declared=$declared AND resolved=$resolved ORDER BY resolved DESC");
            if (!$result) {
                die("Error ".$connection->connect_errno.$connection->connect_error);
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
                // $display_string .= "<td> <div id='form'> <form action='fight.html?fight=$row[fight]&show=$show' method='post'>
                // <input type='submit' name='submit' value='$row[eventt]' >
                // </div> </td>";
                $display_string .= "</tr>";
            }
            $display_string .= "</table>";
            if ($result->num_rows==0){
                echo "<h1>This fight has already ended :(";
            }
            else{
                echo $display_string;
            }
            echo "<h3> ";
            $display_string = "<table>";
            $display_string .= "<tr>";
            $display_string .= "<th>Time</th>";
            $display_string .= "<th>Text</th>";
            $display_string .= "<th>Event</th>";
            $display_string .= "</tr>";
            $show = $_GET['show'];
            $result=$connection->query("SELECT * FROM logs WHERE fight=\"$data\" ORDER BY timemark ASC");
            if (!$result) {
                die("Error2 ".$connection->connect_errno.$connection->connect_error);
            }
            while ($row = $result->fetch_assoc()) {
                    $display_string .= "<tr>";
                    $tm=date("Y-m-d H:i:s", $row[timemark]);
                    $display_string .= "<td>$tm</td>";
                    $display_string .= "<td>$row[textt]</td>";
                    $display_string .= "<td>$row[eventt]</td>";
                    $display_string .= "</tr>";
            }
            $display_string .= "</table>";
            echo $display_string;
        ?>

    </body>
</html>
