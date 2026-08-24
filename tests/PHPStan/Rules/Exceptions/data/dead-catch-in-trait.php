<?php declare(strict_types = 1);

namespace DeadCatchInTrait;

class AlphaException extends \Exception
{

}

class BetaException extends \Exception
{

}

trait ThrowingTrait
{

	/**
	 * @throws AlphaException
	 * @throws BetaException
	 */
	protected function work(): void
	{
		throw new AlphaException();
	}

}

trait DeadInEveryUsingClassTrait
{

	public function run(): void
	{
		try {
			$this->nothingThrown();
		} catch (AlphaException $e) {
		}
	}

	protected function nothingThrown(): void
	{
	}

}

class FirstUser
{

	use DeadInEveryUsingClassTrait;

}

class SecondUser
{

	use DeadInEveryUsingClassTrait;

}

trait UnionCatchTrait
{

	public function run(): void
	{
		try {
			$this->work();
		} catch (AlphaException | BetaException $e) {
		}
	}

}

class ThrowsNeither
{

	use ThrowingTrait, UnionCatchTrait;

	protected function work(): void
	{
	}

}

class ThrowsAlphaOnly
{

	use ThrowingTrait, UnionCatchTrait;

	/**
	 * @throws AlphaException
	 */
	protected function work(): void
	{
		throw new AlphaException();
	}

}
