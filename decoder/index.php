<?php
$file  = file_get_contents("../config.json");
$config = json_decode($file, true);
$json = file_get_contents($config["clan_page"]);

$getter = json_decode($json,true);
$players= $getter["players"];

foreach ($players as $key ) {
  echo $key["id"]."\n";
}
// foreach ($getter[8] as $key ) {
//   echo $key;
// }
// echo $getter[1];

?>
