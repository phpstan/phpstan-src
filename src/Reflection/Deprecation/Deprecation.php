<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Deprecation;

/**
 * @api
 */
final class Deprecation
{

	private ?string $description = null;

	private function __construct()
	{
	}

	public static function create(): self
	{
		return new self();
	}

	public function getDescription(): ?string
	{
		return $this->description;
	}

	public function withDescription(?string $description): self
	{
		$clone = clone $this;
		$clone->description = $description;

		return $clone;
	}

}
