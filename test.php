<?php
class A {
    public $foo = 1;
}

$a = new A;
$j = $a;     // $a и $b копии одного идентификатора
             // ($a) = ($b) = <id>
$j->foo = 3;
echo $a->foo."\n";


$c = new A;
$d = &$c;    // $c и $d ссылки
             // ($c,$d) = <id>

$d->foo = 2;
echo $c->foo."\n";


$e = new A;

function foo($obj) {
    // ($obj) = ($e) = <id>
    $obj->foo = 2;
}

foo($e);
echo $e->foo."\n";

?>
