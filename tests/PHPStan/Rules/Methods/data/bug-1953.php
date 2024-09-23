<?php

namespace Bug1953;

function foo(string &$i) : void {
	$i = "goodbye";
}

function (): void {
	$a = "hello";
	foo($a);
	echo $a->bar();
};
