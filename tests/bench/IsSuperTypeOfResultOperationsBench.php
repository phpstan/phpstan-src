<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\TrinaryLogic;
use PHPStan\Type\IsSuperTypeOfResult;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;

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

	#[Expected(instructions: '3.151M', cycles: '3.208M', memory: '576B')]
	public function benchAnd(): IsSuperTypeOfResult
	{
		$result = $this->yes;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->yes->and($this->maybe);
		}

		return $result;
	}

	#[Expected(instructions: '3.151M', cycles: '3.209M', memory: '576B')]
	public function benchOr(): IsSuperTypeOfResult
	{
		$result = $this->no;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->no->or($this->maybe);
		}

		return $result;
	}

	#[Expected(instructions: '5.538M', cycles: '5.600M', memory: '792B')]
	public function benchMaxMin(): IsSuperTypeOfResult
	{
		$result = $this->no;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = IsSuperTypeOfResult::maxMin($this->no, $this->maybe, $this->no);
		}

		return $result;
	}

	#[Expected(instructions: '1.807M', cycles: '1.851M', memory: '144B')]
	public function benchNegate(): IsSuperTypeOfResult
	{
		$result = $this->no;
		for ($i = 0; $i < self::CALLS; $i++) {
			$result = $this->maybe->negate();
		}

		return $result;
	}

}
