<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Node\Variable\VariableWrite;
use PHPStan\Type\Type;

final class VariableAccessFlow extends VariableFlow
{

	/** @param VariableFlow::READ|VariableFlow::WRITE|VariableFlow::ESCAPE|VariableFlow::MENTION $kind */
	public function __construct(
		string $kind,
		public readonly string $name,
		public readonly ?VariableWrite $write = null,
		public readonly ?Type $type = null,
	)
	{
		parent::__construct($kind);
	}

}
