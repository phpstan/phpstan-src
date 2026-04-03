<?php

declare(strict_types = 1);

namespace Bug3028;

use function PHPStan\Testing\assertType;

interface Output { }
final class OutputImpl1 implements Output { }
final class OutputImpl2 implements Output { }

/** @template O of Output */
interface Format
{
	/** @return O */
	public function output() : Output;

	/** @param O $o */
	public function replace(Output $o) : void;
}

/** @implements Format<OutputImpl1> */
final class FormatImpl1 implements Format
{
	public OutputImpl1 $o;

	public function __construct() { $this->o = new OutputImpl1; }

	public function output() : Output { return new OutputImpl1(); }

	/** @param OutputImpl1 $o */
	public function replace(Output $o) : void { $this->o = $o; }
}

/**
 * @template O of Output
 * @param Format<O> $outputFormat
 * @return Format<Output>
 */
function run(Format $outputFormat) : Format {
	return $outputFormat;
}

function test(): void
{
	$a = new FormatImpl1;
	assertType('Bug3028\FormatImpl1', $a);
	assertType('Bug3028\Format<Bug3028\Output>', run($a));
	assertType('Bug3028\Output', run($a)->output());
}
