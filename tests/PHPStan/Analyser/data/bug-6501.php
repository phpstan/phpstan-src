<?php

declare(strict_types=1);

namespace Bug6501;

/**
 * @template T of \stdClass
 * @template R of \stdClass|\Exception
 */
abstract class AbstractWithOptionsHydrator
{
	/** @var string */
	protected const OPTION_OWNER_PROPERTY = '';

	/**
	 * @param T $entity
	 *
	 * @return R
	 */
	protected function hydrateOption(\stdClass $entity)
	{
		/** @var R $option */
		$option   = new \stdClass();
		$callable = [$option, 'set' . static::OPTION_OWNER_PROPERTY];
		if (!is_callable($callable)) {
			return $option;
		}

		call_user_func($callable, $entity);

		return $option;
	}
}
