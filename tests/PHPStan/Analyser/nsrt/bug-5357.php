<?php declare(strict_types = 1);

namespace Bug5357;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-template T of object
 */
interface AdminInterface {}

/**
 * @phpstan-template T of ProxyQueryInterface
 */
interface PagerInterface {}

/**
 * @phpstan-template T of ProxyQueryInterface
 * @phpstan-implements PagerInterface<T>
 */
class Pager implements PagerInterface
{}

/**
 * @phpstan-template T of ProxyQueryInterface
 */
interface DatagridInterface
{
	/**
	 * @phpstan-return PagerInterface<T>
	 */
	public function getPager(): PagerInterface;
}

/**
 * @phpstan-template T of ProxyQueryInterface
 * @phpstan-implements DatagridInterface<T>
 */
class Datagrid implements DatagridInterface
{
	/**
	 * @phpstan-return PagerInterface<T>
	 */
	public function getPager(): PagerInterface
	{
		throw new \Exception();
	}
}

interface ProxyQueryInterface {}

class Proxy implements ProxyQueryInterface {}

class MockObject {}

/**
 * @phpstan-template T of ProxyQueryInterface
 */
interface DatagridBuilderInterface
{
	/**
	 * @param AdminInterface<object> $admin
	 * @param array<string, mixed>   $values
	 *
	 * @phpstan-return DatagridInterface<T>
	 */
	public function getBaseDatagrid(AdminInterface $admin, array $values = []): DatagridInterface;
}

/**
 * @phpstan-implements DatagridBuilderInterface<Proxy>
 */
class DatagridBuilder implements DatagridBuilderInterface
{
	/**
	 * @param AdminInterface<object> $admin
	 * @param array<string, mixed>   $values
	 *
	 * @phpstan-return DatagridInterface<Proxy>
	 */
	public function getBaseDatagrid(AdminInterface $admin, array $values = []): DatagridInterface
	{
		throw new \Exception();
	}
}

class HelloWorld
{
	/**
	 * @var MockObject&AdminInterface<object>
	 */
	public $admin;

	public DatagridBuilder $datagridBuilder;

	public function sayHello(): void
	{
		$datagrid = $this->datagridBuilder->getBaseDatagrid($this->admin);
		assert($datagrid instanceof Datagrid);
		assertType('Bug5357\Datagrid&Bug5357\DatagridInterface<Bug5357\Proxy>', $datagrid);
		assertType('Bug5357\PagerInterface<Bug5357\Proxy>', $datagrid->getPager());
	}
}
