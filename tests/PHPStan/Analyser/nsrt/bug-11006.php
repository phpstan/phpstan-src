<?php declare(strict_types = 1);

namespace Bug11006;

use function PHPStan\Testing\assertType;

class StringOrNullAttributeDto
{
	public function __construct(
		public ?string $data,
	) {
	}
}

class StringAttributeDto
{
	public function __construct(
		public string $data,
	) {
	}
}

class PhpStanProblem
{
	/**
	 * @param null|'size_uk'|'size_us' $sizeAttributeCode
	 */
	public function example(?string $sizeAttributeCode): void
	{
		$values = [
			'ean' => [
				new StringOrNullAttributeDto(''),
			],
			$sizeAttributeCode => [
				new StringOrNullAttributeDto(''),
			],
			'osa_sizes' => [
				new StringAttributeDto(''),
			],
		];

		assertType("array{ean: array{Bug11006\StringOrNullAttributeDto}, ''?: array{Bug11006\StringOrNullAttributeDto}, size_uk?: array{Bug11006\StringOrNullAttributeDto}, size_us?: array{Bug11006\StringOrNullAttributeDto}, osa_sizes: array{Bug11006\StringAttributeDto}}", $values);
	}
}
