<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Type\Type;

final class VariableAccessFlow extends VariableFlow
{

	/**
	 * @param VariableFlow::READ|VariableFlow::WRITE|VariableFlow::ESCAPE|VariableFlow::MENTION|VariableFlow::DEFINE|VariableFlow::DISCARD $kind
	 * @param int|string|null $offset
	 */
	public function __construct(
		string $kind,
		public readonly string $name,
		public readonly ?VariableWrite $write = null,
		public readonly ?Type $type = null,
		public readonly ?int $targetId = null,
		public readonly bool $container = false,
		public readonly mixed $offset = null,
	)
	{
		parent::__construct($kind);
	}

}
