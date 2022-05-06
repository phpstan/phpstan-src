<?php declare(strict_types = 1);

namespace Bug6917;

use stdClass;
use function PHPStan\Testing\assertType;

/** @phpstan-template T of object */
interface AdminInterface
{
	/** @phpstan-return T */
	public function getObject(): object;
}

interface HelloInterface
{
	/**
	 * @phpstan-template T of object
	 * @phpstan-param AdminInterface<T> $admin
	 * @phpstan-return T
	 */
	public function setAdmin(AdminInterface $admin): object;
}

class Hello implements HelloInterface
{
	/** @inheritdoc */
	public function setAdmin(AdminInterface $admin): object
	{
		return $admin->getObject();
	}
}

class MockObject {}

class Foo
{
	/**
     * @var MockObject&AdminInterface<stdClass>
     */
    public $admin;

	public function test(): void
	{
		$hello = new Hello();
		assertType('stdClass', $hello->setAdmin($this->admin));
	}
}
