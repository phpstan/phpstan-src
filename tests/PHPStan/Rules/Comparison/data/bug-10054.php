<?php

namespace Bug10054;

function falsePositive(): \Generator {
	while (true) {
		$item = yield;

		yield $item;
	}
}

$generator = falsePositive();

var_dump($generator->send('foo'));
