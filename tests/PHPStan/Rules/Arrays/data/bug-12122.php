<?php

namespace Bug12122;

function doFoo() {
	$foo = 'mystring';
	var_dump($foo[-1]);
}
