<?php

declare(strict_types=1);

namespace ExplicitMixedAssignTemplate;

/**
 * @template T
 */
class Foo
{
	/** @var T */
	private $field;

	/** @var T|null */
	private $field2;

	/**
	 * @param T $f
	 */
	public function doFoo($f): void
	{
		$this->field = $f;
		$this->field2 = $f;
	}
}
