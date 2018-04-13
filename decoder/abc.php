<?php
define('_JEXEC', 1);
require_once('analyzer_functions.php');
require_once('analyzer_classes.php');
require_once('data.php');
// 1 создаем подключение к бд
$Analyzer=new Analyzer();
$Analyzer->test();
