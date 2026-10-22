<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

/**
 * @api
 */
final class RestrictedUsage
{

	private function __construct(
		public readonly string $errorMessage,
		public readonly string $identifier,
	)
	{
	}

	public static function create(
		string $errorMessage,
		string $identifier,
	): self
	{
		return new self($errorMessage, $identifier);
	}

}
