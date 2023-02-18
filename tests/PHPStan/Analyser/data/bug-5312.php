<?php declare(strict_types = 1);

namespace Bug5312;

/**
 * @template T of Updatable<T>
 */
interface Updatable
{
	/**
	 * @param T $object
	 */
	public function update(Updatable $object): void;
}
