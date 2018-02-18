<html>
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
                    $display_string .= "<td>$row[textt]</td>";
                    $display_string .= "<td> <div id='form".$i++."'> <form name=".$i++." action='fight.html?fight=$row[fight]' method='post'>
                    <input type='submit' name='submit' value='$row[eventt]' >
                    </div> </td>";
                    $display_string .= "</tr>";
                }
            }
            $display_string .= "</table>";
            echo $display_string;
        ?>

    </body>
</html>
