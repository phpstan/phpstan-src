<?php declare(strict_types = 1);

namespace PHPStan\Node\Property;

use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyAssignNode;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'propertyAssign')]
final class PropertyAssign
{

	public function __construct(
		private PropertyAssignNode $assign,
		private Scope $scope,
	)
	{
	}

	public function getAssign(): PropertyAssignNode
	{
		return $this->assign;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
