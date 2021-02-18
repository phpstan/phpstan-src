<?php

namespace Bug1871;

interface I {}

class A implements I {}

function(): void {
	$objects = [
		new A()
	];

	foreach($objects as $object) {
		var_dump(is_subclass_of($object, '\C'));
	}
};
