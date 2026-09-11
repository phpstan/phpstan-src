<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

final class VariableInputFlow extends VariableFlow
{

	public function __construct(
		public readonly int $writeId,
		public readonly ?int $targetId,
	)
	{
		parent::__construct(self::SEQUENCE);
	}

}
