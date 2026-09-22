<?php

namespace PrintfFormatParserRule;

function doFoo(string $s, int $i, float $f): void
{
	sprintf('%5%', $i);
	sprintf('%1$%', $i);
	sprintf('%ls', $s);
	sprintf('%lX', $i);
	sprintf("%'%5d", $i);
	sprintf('%-+5d', $i);
	sprintf('%*2$d', $i, 5);
	sprintf('%1$*2$d', $i, 5);
	sprintf('%.*3$f', $f, $s, 2);

	sprintf('%5%');
	sprintf('%ls');

	sprintf('%0$s', $s);
	sprintf('%*5d', $i, $i);
	sprintf('%2147483647d', $i);
	sprintf('%99999999999999999999$s', $s);

	sscanf($s, '%a', $i);
}
