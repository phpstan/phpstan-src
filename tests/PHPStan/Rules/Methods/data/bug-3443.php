<?php

namespace Bug3443;

/**
 * Interface Collection.
 */
interface CollectionInterface
{

	/**
	 * @param mixed $data
	 * @param mixed ...$parameters
	 *
	 * @return mixed
	 */
	public static function with($data = [], ...$parameters);

}


/**
 * Class Collection.
 */
final class Collection implements CollectionInterface
{

	public static function with($data = [], ...$parameters)
	{
		return new self();
	}

}
