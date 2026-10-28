<?php

declare(strict_types=1);

namespace Bug15487;

/** @template T */
class Box {}

/** @extends Box<array<mixed>> */
final class ArrBox extends Box {}

/** @param Box<array<mixed>> $b */
function wantsArrBox(Box $b): void {}

function run(): void
{
	wantsArrBox(new ArrBox());
}

/** @extends Box<list<mixed>> */
final class ListBox extends Box {}

/** @param Box<list<mixed>> $b */
function wantsListBox(Box $b): void {}

/** @extends Box<iterable<int>> */
final class IterableBox extends Box {}

/** @param Box<iterable<int>> $b */
function wantsIterableBox(Box $b): void {}

/** @extends Box<array<string>> */
final class StringArrBox extends Box {}

/** @param Box<array<string>> $b */
function wantsStringArrBox(Box $b): void {}

/** @extends Box<array<positive-int>> */
final class PositiveIntArrBox extends Box {}

/** @param Box<array<int>> $b */
function wantsIntArrBox(Box $b): void {}

/**
 * @param Box<array<mixed>> $declared
 * @param Box<array<positive-int>> $positiveInts
 */
function runOthers(Box $declared, Box $positiveInts): void
{
	wantsArrBox($declared);
	wantsListBox(new ListBox());
	wantsIterableBox(new IterableBox());
	wantsStringArrBox(new StringArrBox());

	wantsIntArrBox(new PositiveIntArrBox());
	wantsIntArrBox($positiveInts);
	wantsIntArrBox(new ArrBox());
	wantsIntArrBox($declared);
}

/** @template-covariant T */
class CovariantBox {}

/** @extends CovariantBox<array<positive-int>> */
final class PositiveIntArrCovariantBox extends CovariantBox {}

/** @extends CovariantBox<array<mixed>> */
final class ArrCovariantBox extends CovariantBox {}

/** @param CovariantBox<array<int>> $b */
function wantsIntArrCovariantBox(CovariantBox $b): void {}

function runCovariant(): void
{
	wantsIntArrCovariantBox(new PositiveIntArrCovariantBox());
	wantsIntArrCovariantBox(new ArrCovariantBox());
}
