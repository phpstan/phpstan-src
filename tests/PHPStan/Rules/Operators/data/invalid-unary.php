<?php
namespace InvalidUnary;
function (
	int $i,
	string $str
) {
	+$i;
	-$i;
	~$i;

	+$str;
	-$str;
	~$str;

	+'123';
	-'123';
	~'123';

	+'bla';
	-'bla';
	~'123';

	$array = [];
	~$array;
	~1.1;
};

/**
 * @param resource $r
 * @param numeric-string $ns
 */
function foo(bool $b, array $a, object $o, float $f, $r, string $ns): void
{
	+$b;
	-$b;
	~$b;

	+$a;
	-$a;
	~$a;

	+$o;
	-$o;
	~$o;

	+$f;
	-$f;
	~$f;

	+$r;
	-$r;
	~$r;

	+$ns;
	-$ns;
	~$ns;

	$null = null;
	+$null;
	-$null;
	~$null;
}
