<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11006;

class ProductParentPayloadDto
{
	/** @param null|'size_uk'|'size_us' $SizeAttributeCode */
	public function __construct(
		public ?string $SizeAttributeCode,
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
	public function example(ProductParentPayloadDto $productParentPayloadDto): void
	{
		if (null === $productParentPayloadDto->SizeAttributeCode) {
			return;
		}

		$values = [
			'ean' => [
				new StringOrNullAttributeDto(''),
			],
			$productParentPayloadDto->SizeAttributeCode => [
				new StringOrNullAttributeDto(''),
			],
			// This part goes wrong
			'osa_sizes' => [
				new StringAttributeDto(''),
			],
		];

		$productData = new AkeneoUpdateProductDto(
			values: $values,
		);
	}
}
