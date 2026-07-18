<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug14311;

final class Node
{

	public int $id = 5;

}

function resolve(bool $flag): ?Node
{
	return $flag ? new Node() : null;
}

function make(): Node
{
	return new Node();
}

function coalesce(bool $flag): int
{
	// $node is nullable, so the ?-> is required, not redundant.
	$node = resolve($flag);

	return $node?->id ?? 0;
}

function neverNullOperand(): int
{
	// make() never returns null, so the ?-> is redundant.
	return make()?->id ?? 0;
}

final class Outer
{

	public ?Node $inner = null;

}

function chain(Outer $outer): int
{
	// $outer is never null so ?->inner is redundant; $outer->inner is
	// nullable so ?->id is required.
	return $outer?->inner?->id ?? 0;
}
