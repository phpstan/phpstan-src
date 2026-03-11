<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PhpBench\Attributes as Bench;

#[Bench\Revs(revs: 1)]
#[Bench\Iterations(iterations: 5)]
#[Bench\Warmup(revs: 1)]
#[Bench\RetryThreshold(retryThreshold: 10.0)]
#[Bench\Assert(expression: '
    (mode(baseline.time.avg) < 50 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 25%)
    or (mode(baseline.time.avg) >= 50 milliseconds and mode(baseline.time.avg) < 500 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 10%)
    or (mode(baseline.time.avg) >= 500 milliseconds and mode(variant.time.avg) < mode(baseline.time.avg) +/- 5%)')]
class RegressionBench extends BenchCase
{

	public function benchBug1388(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-1388.php');
	}

	public function benchBug1447(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-1447.php');
	}

	public function benchBug4308(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-4308.php');
	}

	public function benchBug5081(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-5081.php');
	}

	public function benchBug6265(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-6265.php');
	}

	public function benchBug6936(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-6936.php');
	}

	public function benchBug6948(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-6948.php');
	}

	public function benchBug7581(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-7581.php');
	}

	public function benchBug7637(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-7637.php');
	}

	public function benchBug7901(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-7901.php');
	}

	public function benchBug7903(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-7903.php');
	}

	public function benchBug8146(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-8146b.php');
	}

	public function benchBug8146a(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-8146a.php');
	}

	public function benchBug8147(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-8147.php');
	}

	public function benchBug8215(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-8215.php');
	}

	public function benchBug8503(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-8503.php');
	}

	public function benchBug9690(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-9690.php');
	}

	public function benchBug10772(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-10772.php');
	}

	public function benchBug10979(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-10979.php');
	}

	public function benchBug11263(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-11263.php');
	}

	public function benchBug11283(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-11283.php');
	}

	public function benchBug11297(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-11297.php');
	}

	public function benchBug11913(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-11913.php');
	}

	public function benchBug12159(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-12159.php');
	}

	public function benchBug12787(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-12787.php');
	}

	public function benchBug12800(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-12800.php');
	}

	public function benchBug13310(): void
	{
		require_once __DIR__ . '/data/bug-13310.php';
		$this->runAnalyse(__DIR__ . '/data/bug-13310.php');
	}

	public function benchBug13352(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-13352.php');
	}

	public function benchBug13685(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-13685.php');
	}

	public function benchBug13933(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-13933.php');
	}

	public function benchBug14207(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-14207.php');
	}

	public function benchBug14207And(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-14207-and.php');
	}

	public function benchBug3686(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-3686.php');
	}

	public function benchBug4300(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-4300.php');
	}

	public function benchBug5231(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-5231.php');
	}

	public function benchBug5231Two(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-5231_2.php');
	}

	public function benchBug6442(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-6442.php');
	}

	public function benchBug7140(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-7140.php');
	}

	public function benchBug7214(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-7214.php');
	}

	public function benchBug10147(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-10147.php');
	}

	public function benchBug10538(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-10538.php');
	}

	public function benchBug12671(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-12671.php');
	}

	public function benchBug13218(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-13218.php');
	}

	public function benchConditionalExpressionInfiniteLoop(): void
	{
		$this->runAnalyse(__DIR__ . '/data/conditional-expression-infinite-loop.php');
	}

	public function benchProcessCalledMethodInfiniteLoop(): void
	{
		$this->runAnalyse(__DIR__ . '/data/process-called-method-infinite-loop.php');
	}

	public function benchBug5390(): void
	{
		$this->runAnalyse(__DIR__ . '/data/bug-5390.php');
	}

}
