<?php // lint >= 7.4

namespace TemplateTypeNativeTypeObject;

class HelloWorld
{
	/**
	 * @var array<string, mixed>
	 */
	private array $instances;

	public function __construct()
	{
		$this->instances = [];
	}

	/**
	 * @phpstan-template T
	 * @phpstan-param class-string<T> $className
	 *
	 * @phpstan-return T
	 */
	public function getInstanceByName(string $className, string $name): object
	{
		$instance = $this->instances["[{$className}]{$name}"];

		\assert($instance instanceof $className);

		return $instance;
	}
}
