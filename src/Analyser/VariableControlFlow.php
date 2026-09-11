<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Type\Type;

final class VariableControlFlow extends VariableFlow
{

	/**
	 * @param VariableFlow::* $kind
	 * @param list<VariableFlow|null> $children
	 * @param list<array{Type, VariableFlow|null}> $catches
	 * @param list<array{VariableFlow|null, VariableFlow|null, bool}> $cases
	 * @param list<VariableWrite> $bindings
	 * @param list<VariableWrite> $ownWrites
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
		public readonly bool $canContainAnyThrowable = false,
		public readonly Foreach_|For_|null $stmt = null,
		public readonly array $bindings = [],
		public readonly array $ownWrites = [],
	)
	{
		parent::__construct($kind);
	}

}
