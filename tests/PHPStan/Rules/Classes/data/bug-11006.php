<?php declare(strict_types = 1);

namespace Bug11006;

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

class AkeneoUpdateProductDto
{
	/**
	 * @param array{
	 *     ean?: array<StringOrNullAttributeDto>,
	 *     osa_sizes?: array<StringAttributeDto>,
	 *     size_uk?: array<StringOrNullAttributeDto>,
	 *     size_us?: array<StringOrNullAttributeDto>,
	 * } $values
	 */
	public function __construct(
		public array $values,
	) {
	}
}

class ProductParentPayloadDto
{
	/**
	 * @param 'size_uk'|'size_us' $SizeAttributeCode
	 */
	public function __construct(
		public string $SizeAttributeCode,
	) {
	}
}

class PhpStanProblem
{
	public function example(ProductParentPayloadDto $parentPayload): void
	{
		$values = [
			'ean' => [
				new StringOrNullAttributeDto(''),
			],
			$parentPayload->SizeAttributeCode => [
				new StringOrNullAttributeDto(''),
			],
			'osa_sizes' => [
				new StringAttributeDto(''),
			],
		];

		new AkeneoUpdateProductDto($values);
	}
}
