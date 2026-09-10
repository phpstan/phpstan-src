<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\Type;

/** Live variables at the surrounding control-flow destinations. */
final class VariableFlowContext
{

	/**
	 * @param array<string, true> $return
	 * @param list<array<string, true>> $breaks
	 * @param list<array<string, true>> $continues
	 * @param list<array{Type, array<string, true>}> $catches
	 * @param array<string, true> $uncaught
	 */
	public function __construct(
		public readonly array $return,
		public readonly array $breaks = [],
		public readonly array $continues = [],
		public readonly array $catches = [],
		public readonly array $uncaught = [],
	)
	{
	}

}
