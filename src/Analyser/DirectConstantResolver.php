<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Reflection\ReflectionProvider;

class DirectConstantResolver extends ConstantResolver
{

	/**
	 * @param string[] $dynamicConstantNames
	 */
	public function __construct(
		ReflectionProvider $reflectionProvider,
		private array $dynamicConstantNames,
	)
	{
		parent::__construct($reflectionProvider);
	}

	protected function getDynamicConstantNames(): array
	{
		return $this->dynamicConstantNames;
	}

}
