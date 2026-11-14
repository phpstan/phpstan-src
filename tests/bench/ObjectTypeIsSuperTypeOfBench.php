<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use DateTime;
use Exception;
use LogicException;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\ObjectType;
use PHPStan\Type\UnionType;
use stdClass;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\ScratchDirectory;

/**
 * Classes that are not related are compared again and again, for example by every instanceof
 * and every union that contains them.
 */
final class ObjectTypeIsSuperTypeOfBench
{

	private const CALLS = 100;

	private ?ObjectType $dateTime = null;

	private ?ObjectType $exception = null;

	private ?UnionType $union = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);

		$this->dateTime = new ObjectType(DateTime::class);
		$this->exception = new ObjectType(Exception::class);
		$this->union = new UnionType([new ObjectType(Exception::class), new ObjectType(stdClass::class)]);
		$this->dateTime->isSuperTypeOf($this->exception);
		$this->dateTime->isSuperTypeOf($this->union);
	}

	#[Expected(instructions: '252.2K', cycles: '311.5K', memory: '368B')]
	public function benchIsSuperTypeOfUnrelatedClass(): IsSuperTypeOfResult
	{
		if ($this->dateTime === null || $this->exception === null) {
			throw new LogicException('boot() did not run.');
		}

		$result = IsSuperTypeOfResult::createYes();
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->dateTime->isSuperTypeOf($this->exception);
		}

		return $result;
	}

	#[Expected(instructions: '850.0K', cycles: '1.075M', memory: '1.578KiB')]
	public function benchIsSuperTypeOfUnionOfUnrelatedClasses(): IsSuperTypeOfResult
	{
		if ($this->dateTime === null || $this->union === null) {
			throw new LogicException('boot() did not run.');
		}

		$result = IsSuperTypeOfResult::createYes();
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->dateTime->isSuperTypeOf($this->union);
		}

		return $result;
	}

}
