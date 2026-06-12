<?php // lint >= 8.0

namespace BugArrayOffset;

class ParameterNotFoundException extends \Exception {}

interface Container
{
	/**
	 * @throws ParameterNotFoundException
	 */
	public function getParameter(string $key): mixed;
}

class Foo
{
	public function __construct(Container $container)
	{
		$container->getParameter('shipmonkDeadCode')['debug']['usagesOf'];
	}
}

class Foo2
{
	public function __construct(Container $container)
	{
		isset($container->getParameter('shipmonkDeadCode')['debug']['usagesOf']);
	}
}

class Foo3
{
	public function __construct(Container $container)
	{
		unset($container->getParameter('shipmonkDeadCode')['debug']['usagesOf']);
	}
}

class Foo4
{
	public function __construct(Container $container)
	{
		$container->getParameter('shipmonkDeadCode')['debug']['usagesOf'] = 42;
	}
}
