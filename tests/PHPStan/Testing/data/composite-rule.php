<?php

namespace CompositeRule;

function doFoo() {
	echo "hi";
	$x = 1;
	doBar();
	$x = 2;
}

function doBar() {
	echo "123";
}
