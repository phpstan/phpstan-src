<?php declare(strict_types = 1);

namespace NodeCallbackScopeFilterByValue;

function probeFilter(bool $condition, ?int $subject): void
{
}

function probeChainedFilter(bool $conditionA, bool $conditionB, ?int $subject): void
{
}

function testFilter(?int $x): void
{
	probeFilter($x !== null, $x);
}

function testChainedFilter(?int $a): void
{
	probeChainedFilter($a !== null, $a === 5, $a);
}
