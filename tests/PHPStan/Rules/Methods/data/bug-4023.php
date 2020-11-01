<?php

namespace Bug4023;

interface A
{
	/**
	 * @template T of object
	 *
	 * @param mixed[]|T       $data
	 *
	 * @return T
	 */
	public function x($data): object;
}

final class B implements A
{
	/**
	 * @template T of object
	 *
	 * @param mixed[]|T       $data
	 *
	 * @return T
	 */
	public function x($data): object
	{
		throw new \Exception();
	}
}
