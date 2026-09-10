<?php declare(strict_types = 1);

namespace InlineCallCollectorTest;

final class FinalGetter
{

	private string $value;

	public function __construct(string $value)
	{
		$this->value = $value;
	}

	public function getValue(): string
	{
		return $this->value;
	}

}

class ClosedWorldGetter
{

	private int $value;

	public function __construct(int $value)
	{
		$this->value = $value;
	}

	public function getValue(): int
	{
		return $this->value;
	}

	public function getOverriddenValue(): int
	{
		return $this->value;
	}

	final public function getFinalValue(): int
	{
		return $this->value;
	}

}

class OverridingGetter extends ClosedWorldGetter
{

	public function getOverriddenValue(): int
	{
		return 42;
	}

}

abstract class AbstractHooks
{

	/** @return list<string> */
	protected function getHooks(): array
	{
		return [];
	}

	private function getSecret(): string
	{
		return 'secret';
	}

	public function run(): string
	{
		return implode(',', $this->getHooks()) . $this->getSecret();
	}

}

/** @api */
class ApiGetter
{

	private string $value;

	public function __construct(string $value)
	{
		$this->value = $value;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	protected function describeAdditionalCacheKey(): string
	{
		return '';
	}

	public function describe(): string
	{
		return $this->value . $this->describeAdditionalCacheKey();
	}

}

/**
 * @api
 */
final class FinalApiGetter
{

	private string $value;

	public function __construct(string $value)
	{
		$this->value = $value;
	}

	public function getValue(): string
	{
		return $this->value;
	}

}

final class ReadsOthers
{

	private ClosedWorldGetter $closedWorld;

	public function __construct(ClosedWorldGetter $closedWorld)
	{
		$this->closedWorld = $closedWorld;
	}

	private function getClosedWorld(): ClosedWorldGetter
	{
		return $this->closedWorld;
	}

	public function doSomething(FinalGetter $finalGetter, ClosedWorldGetter $closedWorldGetter, ApiGetter $apiGetter, FinalApiGetter $finalApiGetter): string
	{
		return $finalGetter->getValue()
			. $closedWorldGetter->getValue()
			. $closedWorldGetter->getOverriddenValue()
			. $closedWorldGetter->getFinalValue()
			. $apiGetter->getValue()
			. $finalApiGetter->getValue()
			. $this->getClosedWorld()->getValue();
	}

}
