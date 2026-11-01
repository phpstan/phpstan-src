<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/VariableAccessFlow.cpp')]
final class VariableAccessFlow extends VariableFlow
{

	/**
	 * @param VariableFlow::READ|VariableFlow::WRITE|VariableFlow::ESCAPE|VariableFlow::MENTION|VariableFlow::DEFINE|VariableFlow::DISCARD $kind
	 * @param int|string|null $offset
	 */
	public function __construct(
		string $kind,
		public readonly string $name,
		public readonly ?VariableWrite $write,
		public readonly ?Type $type,
		public readonly ?int $targetId,
		public readonly bool $container,
		public readonly mixed $offset,
	)
	{
		parent::__construct($kind);
	}

}
