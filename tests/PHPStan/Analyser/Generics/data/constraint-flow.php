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
