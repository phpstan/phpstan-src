<?php

namespace MaxGoryunov\SavingIterator\Src;
/**
 * @template T
 */
interface Scalar
{
	/**
	 * @return T
	 */
	public function value(): mixed;
}

namespace MaxGoryunov\SavingIterator\Fakes;

use Closure;
use MaxGoryunov\SavingIterator\Src\Scalar;

/**
 * @template T
 * @implements Scalar<T>
 */
class The implements Scalar
{
	/**
	 * @param T                 $subject
	 * @param Closure(T): mixed $context
	 */
	public function __construct(
		/**
		 * @var T
		 */
		private mixed $subject,
		/**
		 * @var Closure(T): mixed
		 */
		private Closure $context
	) {
	}
	/**
	 * @return T
	 */
	public function value(): mixed
	{
		($this->context)($this->subject);
		return $this->subject;
	}
}
