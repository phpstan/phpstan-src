<?php declare(strict_types = 1);

namespace Bug15120b;

/**
 * @template T of Box<Foo>
 */
class Repository
{

	/**
	 * @param T $box
	 * @return non-empty-string
	 */
	public function describe($box): string
	{
		return $box->get()->get();
	}

}
