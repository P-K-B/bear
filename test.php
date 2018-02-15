<?php
$a=array();
if ($a) echo "is\n";
array_push($a, "a");
array_push($a, "b");
array_push($a, "c");
array_push($a, "d");

print_r($a[array_rand($a)]);
if ($a) echo "is\n";
