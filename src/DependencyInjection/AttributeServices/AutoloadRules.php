<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use function array_merge;
use function array_merge_recursive;

/**
 * Autoload rules of one composer.json `autoload` (or `autoload-dev`) section,
 * with all paths absolutized.
 */
final class AutoloadRules
{

	/**
	 * @param array<string, list<string>> $psr4 namespace prefix => base directories
	 * @param list<string> $classmapPaths
	 * @param array<string, list<string>> $psr0
	 * @param list<string> $files
	 */
	public function __construct(
		public array $psr4,
		public array $classmapPaths,
		public array $psr0,
		public array $files,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self([], [], [], []);
	}

	public function union(self $other): self
	{
		return new self(
			array_merge_recursive($this->psr4, $other->psr4),
			array_merge($this->classmapPaths, $other->classmapPaths),
			array_merge_recursive($this->psr0, $other->psr0),
			array_merge($this->files, $other->files),
		);
	}

}
