<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta\TypeMapping;

use LogicException;
use PhpParser\Node as ParserNode;
use function array_key_exists;
use function sprintf;

final class ReturnTypeMap implements CallReturnTypeOverride
{

	/** @var array<string, string|ParserNode\Name\FullyQualified> */
	private array $map = [];

	public function addMapping(string $argumentName, string|ParserNode\Name\FullyQualified $returnTypeMapping): void
	{
		if (array_key_exists($argumentName, $this->map)) {
			throw new LogicException(sprintf("Return type for argument '%s' already specified", $argumentName));
		}
		$this->map[$argumentName] = $returnTypeMapping;
	}

	public function getMappingForArgument(string $argumentName): string|ParserNode\Name\FullyQualified|null
	{
		return $this->map[$argumentName] ?? null;
	}

	public static function merge(self ...$maps): self
	{
		$result = new self();

		foreach ($maps as $map) {
			foreach ($map->map as $argumentName => $returnTypeMapping) {
				$result->map[$argumentName] = $returnTypeMapping;
			}
		}

		return $result;
	}

}
