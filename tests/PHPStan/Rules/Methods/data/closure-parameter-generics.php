<?php

namespace ClosureParameterGenerics;

use Closure;

class Transaction
{

}

class RetryableTransaction extends Transaction
{

}

class Foo
{

	/**
	 * @param Closure(RetryableTransaction $transaction): T $callback
	 * @return T
	 *
	 * @template T
	 */
	public function retryableTransaction(Closure $callback)
	{

	}

	public function doFoo(): void
	{
		$this->retryableTransaction(function (Transaction $tr) {
			return $tr;
		});
	}

}
