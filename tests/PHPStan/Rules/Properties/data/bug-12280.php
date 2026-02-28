<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug12280;

final readonly class A
{
	public function __construct(
		public \DateTime $date,
	) {}
}

class B
{
	public function __construct(
		private \DateTime $date,
	) {}

	/**
	 * @param list<A> $a
	 * @param list<B> $b
	 * @return list<\DateTime>
	 */
	public static function test1(array $a, array $b): array
	{
		$getDate = static function(A|self $value): \DateTime {
			return $value->date;
		};

		return [
			...array_map($getDate(...), $a),
			...array_map($getDate(...), $b),
		];
	}

	/**
	 * @param list<A> $a
	 * @param list<B> $b
	 * @return list<\DateTime>
	 */
	public static function test2(array $a, array $b): array
	{
		$getDate = static function(self|A $value): \DateTime {
			return $value->date;
		};

		return [
			...array_map($getDate(...), $a),
			...array_map($getDate(...), $b),
		];
	}
}
