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
	public function value();
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
	 * @var T
	 */
	private $subject;

	/**
	 * @var Closure(T): mixed
	 */
	private $context;

	/**
	 * @param T                 $subject
	 * @param Closure(T): mixed $context
	 */
	public function __construct(
		$subject,
		$context
	)
	{
		$this->subject = $subject;
		$this->context = $context;
	}
	/**
	 * @return T
	 */
	public function value()
	{
		($this->context)($this->subject);
		return $this->subject;
	}
}
