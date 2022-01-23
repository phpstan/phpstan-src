<?php declare(strict_types = 1);

namespace PHPStan\Dependency;

interface ExportedNode
{

	public function equals(self $node): bool;

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self;

	/**
	 * @param mixed[] $data
	 */
	public static function decode(array $data): self;

}
