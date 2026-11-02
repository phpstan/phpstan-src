<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

/**
 * A `rules` or `services` entry of a NEON config file reduced to what the attributes can express.
 */
final class ServiceDefinition
{

	/**
	 * @param array<mixed> $arguments
	 * @param list<string> $tags
	 * @param string|null $generatedFactory interface of a generated factory, the class is the result type of its create() method
	 */
	public function __construct(
		public string $className,
		public ?string $name,
		public array $arguments,
		public ?string $factory,
		public mixed $autowired,
		public array $tags,
		public ?string $generatedFactory,
	)
	{
	}

}
