<?php // lint >= 8.0

namespace InstantiationArgumentsByName;

class Configuration
{

	/**
	 * @optional-arguments-by-name
	 */
	public function __construct(
		string $dialect,
		array $groups = [],
		bool $allowExtraAttributes = true,
	)
	{
	}

}

class ChildConfiguration extends Configuration
{

}

/**
 * @arguments-by-name
 */
class ClassLevel
{

	public function __construct(string $dialect, array $groups = [])
	{
	}

}

function (): void {
	new Configuration('openapi-3.1', groups: ['read']);
	new Configuration('openapi-3.1', ['read']);
	new Configuration(dialect: 'openapi-3.1');
	new ChildConfiguration('openapi-3.1', ['read']);
	new ClassLevel(dialect: 'openapi-3.1', groups: ['read']);
	new ClassLevel('openapi-3.1');
};
