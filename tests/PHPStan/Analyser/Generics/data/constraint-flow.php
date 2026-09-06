<?php declare(strict_types = 1);

namespace TemplateArgumentConstraintFlow;

use function PHPStan\Testing\assertType;

/** @template T */
class Box
{
	/** @param T $value */
	public function add($value): void
	{
	}
}

/** @param Box<int> $box */
function consume(Box $box): bool
{
	return true;
}

function returningBranch(bool $condition): void
{
	$box = new Box();
	assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
	if ($condition) {
		consume($box);
		return;
	}
	assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
}

function throwingBranch(bool $condition): void
{
	$box = new Box();
	try {
		if ($condition) {
			consume($box);
			throw new \RuntimeException();
		}
	} catch (\RuntimeException $e) {
	} finally {
		assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
	}
	assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
}

function loop(bool $condition): void
{
	$box = new Box();
	while ($condition) {
		$box->add(1);
		break;
	}
	assertType('TemplateArgumentConstraintFlow\Box<1>', $box);
}

function closure(): void
{
	$box = new Box();
	$callback = function () use ($box): void {
		consume($box);
	};
	assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
}

function arrow(): void
{
	$box = new Box();
	$callback = fn () => consume($box);
	assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
}

function shortCircuit(bool $condition): void
{
	$box = new Box();
	$condition && consume($box);
	assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
}

function acceptCallback(callable $callback): void
{
}

function arrowArgument(): void
{
	$box = new Box();
	acceptCallback(fn () => consume($box));
	assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
}

/** @param Box<int> $box */
function terminate(Box $box): never
{
	exit;
}

function terminatingExpressions(bool $condition, ?bool $nullable): void
{
	$and = new Box();
	$condition && terminate($and);
	assertType('TemplateArgumentConstraintFlow\Box<int>', $and);

	$or = new Box();
	$condition || terminate($or);
	assertType('TemplateArgumentConstraintFlow\Box<int>', $or);

	$coalesce = new Box();
	$nullable ?? terminate($coalesce);
	assertType('TemplateArgumentConstraintFlow\Box<int>', $coalesce);

	$ternaryIf = new Box();
	$condition ? terminate($ternaryIf) : false;
	assertType('TemplateArgumentConstraintFlow\Box<int>', $ternaryIf);

	$ternaryElse = new Box();
	$condition ? true : terminate($ternaryElse);
	assertType('TemplateArgumentConstraintFlow\Box<int>', $ternaryElse);

	$match = new Box();
	match ($condition) {
		true => terminate($match),
		false => false,
	};
	assertType('TemplateArgumentConstraintFlow\Box<int>', $match);
}

function foreachLoop(): void
{
	foreach ([30, 7] as $day) {
		$box = new Box();
		consume($box);
		assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
	}
}

function unreachableLoops(): void
{
	$while = new Box();
	while (false) {
		consume($while);
	}
	assertType('TemplateArgumentConstraintFlow\Box<int>', $while);

	$for = new Box();
	for (; false;) {
		consume($for);
	}
	assertType('TemplateArgumentConstraintFlow\Box<int>', $for);

	$foreach = new Box();
	foreach ([] as $unused) {
		consume($foreach);
	}
	assertType('TemplateArgumentConstraintFlow\Box<int>', $foreach);
}

function switchTermination(bool $condition): void
{
	$box = new Box();
	switch ($condition) {
		case true:
			terminate($box);
		default:
			break;
	}
	assertType('TemplateArgumentConstraintFlow\Box<int>', $box);
}
