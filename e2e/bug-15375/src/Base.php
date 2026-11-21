<?php declare(strict_types = 1);

namespace Bug15375;

/**
 * @template T of Entity
 */
abstract class Base implements Named
{

	/** @var list<T> */
	public array $items = [];

}
