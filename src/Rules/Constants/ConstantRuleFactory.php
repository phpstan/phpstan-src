<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\DependencyInjection\Container;

class ConstantRuleFactory
{

	public function __construct(
		private Container $container,
	)
	{
	}

	public function create(): ConstantRule
	{
		return new ConstantRule(
			$this->container->getParameter('dynamicConstantNames'),
		);
	}

}
