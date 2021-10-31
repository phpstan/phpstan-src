<?php

namespace Bug5804;

class Blah {
	/** @var ?int[] */
	public $value;
}

function (Blah $b) {
	$b->value[] = 'hello';
};
