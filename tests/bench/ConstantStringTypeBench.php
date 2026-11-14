<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\ScratchDirectory;

final class ConstantStringTypeBench
{

	private const KEYS = 256;

	private const DATE_TIME_METHODS = ['createFromFormat', 'getLastErrors', 'format', 'modify', 'setDate', 'setTime', 'getTimestamp', 'getTimezone'];

	/** @var list<ConstantStringType> */
	private array $keys = [];

	/** @var list<ConstantStringType> */
	private array $callables = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);

		(new ConstantStringType('warmup'))->toArrayKey();
		(new ConstantStringType('DateTimeImmutable::format'))->isCallable();
		foreach (self::DATE_TIME_METHODS as $method) {
			(new ConstantStringType('DateTime::' . $method))->isCallable();
		}

		for ($i = 0; $i < self::KEYS; $i++) {
			$this->keys[] = new ConstantStringType('key' . $i);
		}
		foreach (self::DATE_TIME_METHODS as $method) {
			$this->callables[] = new ConstantStringType('DateTime::' . $method);
		}
	}

	/**
	 * Strings that stay strings as array keys.
	 *
	 * @return list<Type>
	 */
	#[Expected(instructions: '302.2K', cycles: '435.1K', memory: '8.422KiB')]
	public function benchToArrayKey(): array
	{
		$arrayKeys = [];
		foreach ($this->keys as $key) {
			$arrayKeys[] = $key->toArrayKey();
		}

		return $arrayKeys;
	}

	/**
	 * 'DateTime::format' and other methods of DateTime, static or not. Since PHP 8 such a string
	 * may name an instance method, so the method is not looked into.
	 */
	#[Expected(instructions: '86.85K', cycles: '204.5K', memory: '952B')]
	public function benchIsCallable(): TrinaryLogic
	{
		$result = TrinaryLogic::createYes();
		foreach ($this->callables as $callable) {
			$result = $result->and($callable->isCallable());
		}

		return $result;
	}

}
