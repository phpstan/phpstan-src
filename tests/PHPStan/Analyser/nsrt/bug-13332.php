<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug13332;

use function PHPStan\Testing\assertType;

enum TestEnum {
	case A;
	case B;
}

/**
 * @phpstan-type KeyType string|int|\UnitEnum|object
 *
 * @template K of KeyType
 */
class TestError
{
	/** @param K $key */
	public function __construct(private readonly mixed $key)
	{
	}

	/** @return self<TestEnum> */
	public static function makeEnum(): self
	{
		return new self(TestEnum::A);
	}

	/** @return self<string> */
	public static function makeString(): self
	{
		return new self('foo');
	}
}

/**
 * @template K of string|int|\UnitEnum|object
 */
class TestOk
{
	/** @param K $key */
	public function __construct(private readonly mixed $key)
	{
	}

	/** @return self<TestEnum> */
	public static function makeEnum(): self
	{
		return new self(TestEnum::A);
	}
}

function () {
	$error = TestError::makeEnum();
	assertType('Bug13332\TestError<Bug13332\TestEnum>', $error);

	$errorStr = TestError::makeString();
	assertType('Bug13332\TestError<string>', $errorStr);

	$ok = TestOk::makeEnum();
	assertType('Bug13332\TestOk<Bug13332\TestEnum>', $ok);
};
