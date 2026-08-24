<?php declare(strict_types = 1);

namespace NodeCallbackScopeDerivedOps;

function probeAssignExpression(string $subject): void
{
}

function probeAssignExpressionAfterRead(string $subject): void
{
}

function probeAssignVariable(string $subject): void
{
}

function probeFilterThenAssign(bool $condition, ?string $subject): void
{
}

function testAssignExpression(string $key): void
{
	probeAssignExpression($key);
}

function testAssignExpressionAfterRead(string $key): void
{
	probeAssignExpressionAfterRead($key);
}

function testAssignVariable(string $key): void
{
	probeAssignVariable($key);
}

function testFilterThenAssign(?string $key): void
{
	probeFilterThenAssign($key !== null, $key);
}
