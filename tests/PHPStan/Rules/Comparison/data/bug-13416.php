<?php declare(strict_types=1);

namespace Bug13416;

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

class AnotherRecord extends MyRecord {}

class PHPStanMinimalBug {
	public function testMinimalBug(): void {
		$msg1 = new MyRecord();
		$msg1->insert();

		assert(
			count(MyRecord::find()) === 1,
			'should have 1 record initially'
		);

		$msg2 = new MyRecord();
		$msg2->insert();

		assert(
			count(MyRecord::find()) === 2,
			'should have 2 messages after adding one'
		);
	}

	public function testMinimalBugChildClass(): void {
		$msg1 = new AnotherRecord();
		$msg1->insert();

		assert(
			count(MyRecord::find()) === 1,
			'should have 1 record initially'
		);

		$msg2 = new AnotherRecord();
		$msg2->insert();

		assert(
			count(MyRecord::find()) === 2,
			'should have 2 messages after adding one'
		);
	}
}
