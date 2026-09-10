<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

final class VariableSequenceFlow extends VariableFlow
{

	/**
	 * @param VariableFlow::SEQUENCE|VariableFlow::CHOICE $kind
	 * @param list<VariableFlow|null> $children
	 */
	public function __construct(string $kind, public readonly array $children)
	{
		parent::__construct($kind);
	}

}
