<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;

class FileTypeMapperPhpDocNodeMap
{

	/** @var array<string, PhpDocNode> */
	private array $resolved = [];

	/**
	 * @param array<string, callable(): PhpDocNode> $map
	 */
	public function __construct(private array $map)
	{
	}

	/**
	 * @return array<string, callable(): PhpDocNode>
	 */
	public function getMap(): array
	{
		return $this->map;
	}

	public function has(string $nameScopeKey): bool
	{
		return array_key_exists($nameScopeKey, $this->map);
	}

	public function get(string $nameScopeKey): PhpDocNode
	{
		if (array_key_exists($nameScopeKey, $this->resolved)) {
			return $this->resolved[$nameScopeKey];
		}
		if (!$this->has($nameScopeKey)) {
			throw new ShouldNotHappenException();
		}
		return $this->resolved[$nameScopeKey] = $this->map[$nameScopeKey]();
	}

}
