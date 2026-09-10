<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\ArrowFunction;
use PHPStan\Type\Type;

final class VariableControlFlow extends VariableFlow
{

	/**
	 * @param VariableFlow::* $kind
	 * @param list<VariableFlow|null> $children
	 * @param list<array{Type, VariableFlow|null}> $catches
	 * @param list<array{VariableFlow|null, VariableFlow|null, bool}> $cases
	 */
	public function __construct(
		string $kind,
		public readonly array $children = [],
		public readonly ?string $name = null,
		public readonly ?Type $type = null,
		public readonly int $level = 1,
		public readonly bool $atLeastOnce = false,
		public readonly bool $canExit = true,
		public readonly array $catches = [],
		public readonly ?ArrowFunction $arrow = null,
		public readonly array $cases = [],
		public readonly bool $canRepeat = true,
	)
	{
		parent::__construct($kind);
	}

}
