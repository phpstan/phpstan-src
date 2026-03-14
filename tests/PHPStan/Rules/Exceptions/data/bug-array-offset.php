<?php

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
