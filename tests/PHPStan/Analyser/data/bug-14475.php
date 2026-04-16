<?php declare(strict_types = 1);

namespace Bug14475;

final class Foo
{
	public const TYPE_A = 'type_a';
	public const TYPE_B = 'type_b';

	public const CATEGORY_A = 'category_a';
	public const CATEGORY_B = 'category_b';
	public const CATEGORY_C = 'category_c';
	public const CATEGORY_D = 'category_d';
	public const CATEGORY_E = 'category_e';
	public const CATEGORY_F = 'category_f';
	public const CATEGORY_G = 'category_g';
	public const CATEGORY_H = 'category_h';
	public const CATEGORY_I = 'category_i';
	public const CATEGORY_J = 'category_j';
	public const CATEGORY_K = 'category_k';
	public const CATEGORY_L = 'category_l';
	public const CATEGORY_M = 'category_m';
	public const CATEGORY_N = 'category_n';
	public const CATEGORY_O = 'category_o';
	public const CATEGORY_P = 'category_p';
	public const CATEGORY_Q = 'category_q';
	public const CATEGORY_R = 'category_r';
	public const CATEGORY_S = 'category_s';
	public const CATEGORY_T = 'category_t';
	public const CATEGORY_U = 'category_u';
	public const CATEGORY_V = 'category_v';

	public const STATUS_A = 'status_a';
	public const STATUS_B = 'status_b';
	public const STATUS_C = 'status_c';

	public const PAGE_A = 'page_a';
	public const PAGE_B = 'page_b';
	public const PAGE_C = 'page_c';
}

final class AssertHelper
{
	/**
	 * @param list<string> $haystack
	 */
	public static function stringInArray(string $value, array $haystack): void
	{
	}
}

final class MinCase
{
	/**
	 * @phpstan-param array{
	 *     type: Foo::TYPE_*,
	 *     category: Foo::CATEGORY_*|Foo::STATUS_*,
	 *     page?: Foo::PAGE_*,
	 *     flag: bool
	 * } $input
	 */
	public static function run(array $input): void
	{
		AssertHelper::stringInArray(
			$input['category'],
			[
				Foo::CATEGORY_A,
				Foo::CATEGORY_B,
				Foo::CATEGORY_C,
				Foo::CATEGORY_D,
				Foo::CATEGORY_E,
				Foo::CATEGORY_F,
				Foo::CATEGORY_G,
				Foo::CATEGORY_H,
				Foo::CATEGORY_I,
				Foo::CATEGORY_J,
				Foo::CATEGORY_K,
				Foo::CATEGORY_L,
				Foo::CATEGORY_M,
				Foo::STATUS_A,
				Foo::STATUS_B,
				Foo::STATUS_C,
				Foo::CATEGORY_N,
				Foo::CATEGORY_O,
				Foo::CATEGORY_P,
				Foo::CATEGORY_Q,
				Foo::CATEGORY_R,
				Foo::CATEGORY_S,
				Foo::CATEGORY_T,
				Foo::CATEGORY_U,
				Foo::CATEGORY_V,
			]
		);

		if ($input['category'] === Foo::CATEGORY_C) {
		}

		if ($input['category'] === Foo::CATEGORY_F) {
		}

		if ($input['category'] === Foo::CATEGORY_G) {
		}

		if ($input['category'] === Foo::CATEGORY_H) {
		}

		if ($input['category'] === Foo::CATEGORY_I) {
		}

		if ($input['category'] === Foo::CATEGORY_B) {
			AssertHelper::stringInArray(
				$input['page'] ?? '',
				[
					Foo::PAGE_A,
					Foo::PAGE_B,
					Foo::PAGE_C,
				]
			);
		}
	}
}
