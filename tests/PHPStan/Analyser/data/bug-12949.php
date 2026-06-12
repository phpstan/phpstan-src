<?php // lint >= 8.3

namespace Bug12949;

function doFoo():void {
	$b = '0';
	${$b} = 1;

	echo "";
}

function doBar(object $o):void {
	$b = '0';
	$o->{$b}();
	$o::{$b}();
	echo $o::{$b};

	echo "";
}
