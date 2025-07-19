<?php

declare(strict_types = 1);

namespace Bug13271;

$object = new class{
	public string $example_one = "";
	public string $example_two = "";
};

$field = rand() > 0.5 ? 'example_one' : 'example_two';
$result = $object->$field;
