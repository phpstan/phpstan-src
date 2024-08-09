<?php

namespace PHPStan\Rules\Methods\data;

use function sprintf;

final class NamedFacet
{
	private const string SEPARATOR = '+++';

	private function __construct(
		/** @var positive-int */
		private int $id,
		/** @var non-empty-string */
		private string $name,
	) {}

	/**
	 * @param positive-int $id
	 * @param non-empty-string $name
	 */
	public static function fromIdAndName(int $id, string $name): self
	{
		return new self($id, $name);
	}

	/** @return positive-int */
	public function id(): int
	{
		return $this->id;
	}

	/** @return non-empty-string */
	public function name(): string
	{
		return $this->name;
	}

	/** @return non-empty-string */
	public function toFacetValue(): string
	{
		return sprintf(
			'%s%s%s',
			$this->name,
			self::SEPARATOR,
			$this->id,
		);
	}
}
