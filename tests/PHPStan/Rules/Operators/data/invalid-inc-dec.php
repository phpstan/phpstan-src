<?php

namespace InvalidIncDec;

function ($a, int $i, ?float $j, string $str, \stdClass $std, \SimpleXMLElement $simpleXMLElement) {
	$a++;

	$b = [1];
	$b[0]++;

	date('j. n. Y')++;
	date('j. n. Y')--;

	$i++;
	$j++;
	$str++;
	$std++;
	$classWithToString = new ClassWithToString();
	$classWithToString++;
	$classWithToString = new ClassWithToString();
	--$classWithToString;
	$arr = [];
	$arr++;
	$arr = [];
	--$arr;

	if (($f = fopen('php://stdin', 'r')) !== false) {
		$f++;
	}

	if (($f = fopen('php://stdin', 'r')) !== false) {
		--$f;
	}

	$bool = true;
	$bool++;
	$bool = false;
	--$bool;
	$null = null;
	$null++;
	$null = null;
	--$null;
	$a = $simpleXMLElement;
	$a++;
	$a = $simpleXMLElement;
	--$a;
};

class ClassWithToString
{
	public function __toString(): string
	{
		return 'foo';
	}
}
