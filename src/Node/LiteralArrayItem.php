<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\ArrayItem;
use PHPStan\Analyser\Scope;

/** @api */
class LiteralArrayItem
{

	public function __construct(private Scope $scope, private ?ArrayItem $arrayItem)
	{
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

	public function getArrayItem(): ?ArrayItem
	{
		return $this->arrayItem;
	}

}
