<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Stmt\For_;
use PhpParser\Node\Stmt\Foreach_;
use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/VariableControlFlow.cpp')]
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
		public readonly array $children,
		public readonly ?string $name,
		public readonly ?Type $type,
		public readonly int $level,
		public readonly bool $atLeastOnce,
		public readonly bool $canExit,
		public readonly array $catches,
		public readonly ?ArrowFunction $arrow,
		public readonly array $cases,
		public readonly bool $canRepeat,
		public readonly bool $canContainAnyThrowable,
		public readonly Foreach_|For_|null $stmt,
		public readonly array $bindings,
		public readonly array $ownWrites,
	)
	{
		parent::__construct($kind);
	}

}
