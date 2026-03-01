<?php declare(strict_types = 1);

namespace Bug13416;

use function PHPStan\Testing\assertType;

class MyRecord {
	/** @var array<int, self> */
	private static array $storage = [];

	/** @phpstan-impure */
	public function insert(): void {
		self::$storage[] = $this;
	}

	/**
	 * @return array<int, self>
	 * @phpstan-impure
	 */
	public static function find(): array {
		return self::$storage;
	}
}

class TestCase {
	public function testMinimalBug(): void {
		$msg1 = new MyRecord();
		$msg1->insert();

		assert(
			count(MyRecord::find()) === 1,
			'should have 1 record initially'
		);

		$msg2 = new MyRecord();
		$msg2->insert();

		assertType('array<int, Bug13416\MyRecord>', MyRecord::find());
		assertType('int<0, max>', count(MyRecord::find()));

		assert(
			count(MyRecord::find()) === 2,
			'should have 2 messages after adding one'
		);
	}
}
