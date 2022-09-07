<?php declare(strict_types = 1);

namespace Bug7103;

/**
 * @phpstan-template T of object
 */
interface Manager
{
    /**
	 * @phpstan-template R of T
	 * @param class-string<R> $class
	 * @return R
	 */
	public function find(string $class, int $id): object;
}

interface Entity {}

/**
 * @phpstan-template T of Entity
 * @phpstan-implements Manager<T>
 */
abstract class MyManager implements Manager
{
	/**
     * @phpstan-template R of T
     * @phpstan-param class-string<R> $class
     * @phpstan-return R
     */
    public function find(string $class, int $id): object
    {
        /** @phpstan-var R $object */
        $object = $this->get($id);

        return $object;
    }

	abstract public function get(int $id): object;
}
