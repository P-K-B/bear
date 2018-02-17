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
$display_string .= "<th>Event</th>";
$display_string .= "</tr>";
$query = "USE {$config["database"]}";
$result = $connection->query($query);
$show = $_GET['show'];
$result=$connection->query("SELECT * FROM logs ORDER BY timemark DESC");
if (!$result) {
    die("Error ".$connection->connect_errno.$connection->connect_error);
}
while ($row = $result->fetch_assoc()) {
    if ($show-- >0) {
        $display_string .= "<tr>";
        $display_string .= "<td>$row[eventt]</td>";
        $display_string .= "</tr>";
    }
}
$display_string .= "</table>";
echo $display_string;
