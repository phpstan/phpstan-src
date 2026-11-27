<?php declare(strict_types = 1);

namespace TemplateArgumentUpperBounds;

use Countable;
use Traversable;
use function PHPStan\Testing\assertType;

/** @template T */
final class Box
{

	/** @param T $item */
	public function add($item): void
	{
	}

}

/** @param Box<covariant int|string> $b */
function intOrString(Box $b): void
{
}

/** @param Box<covariant int|bool> $b */
function intOrBool(Box $b): void
{
}

/** @param Box<covariant bool> $b */
function onlyBool(Box $b): void
{
}

/** @param Box<contravariant int> $b */
function intSink(Box $b): void
{
}

/** @param Box<covariant Countable> $b */
function countable(Box $b): void
{
}

/** @param Box<covariant Traversable<mixed>> $b */
function traversable(Box $b): void
{
}

function upperBounds(): void
{
	$box = new Box();
	intOrString($box);
	intOrBool($box);
	assertType('TemplateArgumentUpperBounds\Box<int>', $box);
}

function upperBoundsSwapped(): void
{
	$box = new Box();
	intOrBool($box);
	intOrString($box);
	assertType('TemplateArgumentUpperBounds\Box<int>', $box);
}

function objectUpperBounds(): void
{
	$box = new Box();
	countable($box);
	traversable($box);
	assertType('TemplateArgumentUpperBounds\Box<Countable&Traversable<mixed, mixed>>', $box);
}

function lowerBoundWithinUpperBound(): void
{
	$box = new Box();
	intOrString($box);
	intSink($box);
	assertType('TemplateArgumentUpperBounds\Box<int>', $box);
}

function addedWithinUpperBound(): void
{
	$box = new Box();
	intOrString($box);
	$box->add(1);
	$box->add('x');
	assertType("TemplateArgumentUpperBounds\Box<1|'x'>", $box);
}

function contradictingUpperBounds(): void
{
	$box = new Box();
	intOrString($box);
	onlyBool($box);
	assertType('TemplateArgumentUpperBounds\Box<int|string>', $box);
}
