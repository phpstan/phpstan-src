<?php declare(strict_types = 1);

namespace Bug7707;

class HelloWorld
{
	public function sayHello(): void
	{
		var_dump(array_diff_uassoc(
			['a' => 1, 'b' => 2],
			['c' => 1, 'd' => 2],
			// keys comparison
			static function (string $a, string $b): int {
				return $a <=> $b;
			}
		));

		var_dump(array_diff_ukey(
			['a' => 1, 'b' => 2],
			['c' => 1, 'd' => 2],
			// keys comparison
			static function (string $a, string $b): int {
				return $a <=> $b;
			}
		));

		var_dump(array_intersect_uassoc(
			['a' => 1, 'b' => 2],
			['c' => 1, 'd' => 2],
			// keys comparison
			static function (string $a, string $b): int {
				return $a <=> $b;
			}
		));

		var_dump(array_intersect_ukey(
			['a' => 1, 'b' => 2],
			['c' => 1, 'd' => 2],
			// keys comparison
			static function (string $a, string $b): int {
				return $a <=> $b;
			}
		));

		var_dump(array_udiff_assoc(
			['a' => 1, 'b' => 2],
			['c' => 1, 'd' => 2],
			// values comparison
			static function (int $a, int $b): int {
				return $a <=> $b;
			}
		));

		var_dump(array_udiff_uassoc(
			['a' => 1, 'b' => 2],
			['c' => 1, 'd' => 2],
			// values comparison
			static function (int $a, int $b): int {
				return $a <=> $b;
			},
			// keys comparison
			static function (string $a, string $b): int {
				return $a <=> $b;
			}
		));

		var_dump(array_uintersect_assoc(
			['a' => 1, 'b' => 2],
			['c' => 1, 'd' => 2],
			// values comparison
			static function (int $a, int $b): int {
				return $a <=> $b;
			}
		));

		var_dump(array_uintersect_uassoc(
			['a' => 1, 'b' => 2],
			['c' => 1, 'd' => 2],
			// values comparison
			static function (int $a, int $b): int {
				return $a <=> $b;
			},
			// keys comparison
			static function (string $a, string $b): int {
				return $a <=> $b;
			}
		));

		var_dump(array_uintersect(
			['a' => 1, 'b' => 2],
			['c' => 1, 'd' => 2],
			// values comparison
			static function (int $a, int $b): int {
				return $a <=> $b;
			}
		));
	}
}
