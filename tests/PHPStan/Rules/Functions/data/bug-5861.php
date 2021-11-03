<?php

namespace Bug5861;

class Foo {
	public function parse(string $rawJson): array
	{
		return (array) json_decode($rawJson, true, 512, JSON_THROW_ON_ERROR);
	}
}
