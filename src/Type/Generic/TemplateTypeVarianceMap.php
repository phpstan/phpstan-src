<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use function array_key_exists;

/** @api */
class TemplateTypeVarianceMap
{

	private static ?TemplateTypeVarianceMap $empty = null;

	/**
	 * @api
	 * @param array<string, TemplateTypeVariance> $variances
	 */
	public function __construct(private array $variances)
	{
	}

	public static function createEmpty(): self
	{
		$empty = self::$empty;

		if ($empty !== null) {
			return $empty;
		}

		$empty = new self([]);
		self::$empty = $empty;

		return $empty;
	}

	/** @return array<string, TemplateTypeVariance> */
	public function getVariances(): array
	{
		return $this->variances;
	}

	public function hasVariance(string $name): bool
	{
		return array_key_exists($name, $this->getVariances());
	}

	public function getVariance(string $name): ?TemplateTypeVariance
	{
		return $this->getVariances()[$name] ?? null;
	}

}
