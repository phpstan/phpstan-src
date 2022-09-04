<?php // lint >= 8.0

namespace Bug7898;

class FooEnum
{
	public const FOO_TYPE = 'foo';
	public const APPLICABLE_TAX_AND_FEES_BY_TYPE = [
		'US' => [
			'bar' => [
				'sales_tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'city_tax' => [
					'type' => 'both',
					'unit' => 'per-room-per-night',
				],
				'resort_fee' => [
					'type' => 'both',
					'unit' => 'per-room-per-night',
				],
				'additional_tax_or_fee' => [
					'type' => 'both',
					'unit' => 'per-room-per-night',
				],
			],
			'foo' => [
				'tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
			],
		],
		'CA' => [
			'bar' => [
				'goods_and_services_tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'provincial_sales_tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'harmonized_sales_tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'municipal_and_regional_district_tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'additional_tax_or_fee' => [
					'type' => 'both',
					'unit' => 'per-room-per-night',
				],
			],
		],
		'SG' => [
			'bar' => [
				'service_charge' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
			],
		],
		'TH' => [
			'bar' => [
				'service_charge' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'city_tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
			],
		],
		'AE' => [
			'bar' => [
				'vat' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'service_charge' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'municipality_fee' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'tourism_fee' => [
					'type' => 'both',
					'unit' => 'per-room-per-night',
				],
				'destination_fee' => [
					'type' => 'both',
					'unit' => 'per-room-per-night',
				],
			],
		],
		'BH' => [
			'bar' => [
				'vat' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'service_charge' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'city_tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
			],
		],
		'HK' => [
			'bar' => [
				'service_charge' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
				'tax' => [
					'type' => 'rate',
					'unit' => 'per-room-per-night',
				],
			],
		],
		'ES' => [
			'bar' => [
				'city_tax' => [
					'type' => 'both',
					'unit' => 'per-room-per-night',
				],
			],
		],
	];
}

class Country
{
	public function __construct(private string $code)
	{
	}

	public function getCode(): string
	{
		return $this->code;
	}
}

class Foo
{
	public function __construct(private Country $country)
	{
	}

	public function getCountryCode(): string
	{
		return $this->country->getCode();
	}

	public function getHasDaycationTaxesAndFees(): bool
	{
		return array_key_exists(FooEnum::FOO_TYPE, FooEnum::APPLICABLE_TAX_AND_FEES_BY_TYPE[$this->getCountryCode()]);
	}

}
