<?php

namespace IncompatibleSelfOutType;

/**
 * @template T
 */
interface A
{
	/** @phpstan-self-out self */
	public function one();

	/**
	 * @template NewT
	 * @param NewT $param
	 * @phpstan-self-out self<NewT>
	 */
	public function two($param);

	/**
	 * @phpstan-self-out int
	 */
	public function three();
}
