<?php declare(strict_types = 1);

namespace Bug3028;

interface Output { }

final class OutputImpl1 implements Output { }
final class OutputImpl2 implements Output { }

/** @psalm-template O of Output */
interface Format
{
	/** @psalm-return O */
	public function output() : Output;

	/** @psalm-param O $o */
	public function replace(Output $o) : void;
}

/** @implements Format<OutputImpl1> */
final class FormatImpl1 implements Format
{
	public OutputImpl1 $o;

	public function __construct() {
		$this->o = new OutputImpl1;
	}

	public function output() : Output
	{
		return new OutputImpl1();
	}

	/**
	 * @param OutputImpl1 $o
	 */
	public function replace(Output $o) : void
	{
		$this->o = $o;
	}
}


/**
 * @psalm-template O of Output
 * @psalm-param    Format<O> $outputFormat
 * @return Format<Output>
 */
function run(Format $outputFormat) : Format {
	return $outputFormat;
}

$a = new FormatImpl1;
run($a)->replace(new OutputImpl2);
