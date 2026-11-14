<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\TrinaryLogic;
use PHPStan\Type\IsSuperTypeOfResult;
use Tally\Attribute\Before;

/**
 * Almost all results carry no reasons, so these combine results without them, a thousand times.
 */
final class IsSuperTypeOfResultOperationsBench
{

	private const CALLS = 1000;

	private IsSuperTypeOfResult $yes;

	private IsSuperTypeOfResult $maybe;

	private IsSuperTypeOfResult $no;

	#[Before]
	public function boot(): void
	{
		$this->yes = new IsSuperTypeOfResult(TrinaryLogic::createYes(), []);
		$this->maybe = new IsSuperTypeOfResult(TrinaryLogic::createMaybe(), []);
		$this->no = new IsSuperTypeOfResult(TrinaryLogic::createNo(), []);
	}

	public function benchAnd(): IsSuperTypeOfResult
	{
		$result = $this->yes;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->yes->and($this->maybe);
		}

		return $result;
	}

	public function benchOr(): IsSuperTypeOfResult
	{
		$result = $this->no;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->no->or($this->maybe);
		}

		return $result;
	}

	public function benchMaxMin(): IsSuperTypeOfResult
	{
		$result = $this->no;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = IsSuperTypeOfResult::maxMin($this->no, $this->maybe, $this->no);
		}

		return $result;
	}

	public function benchNegate(): IsSuperTypeOfResult
	{
		$result = $this->no;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->maybe->negate();
		}

		return $result;
	}

}
