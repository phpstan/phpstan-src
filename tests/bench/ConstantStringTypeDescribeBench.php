<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\VerbosityLevel;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;
use Tally\ScratchDirectory;

final class ConstantStringTypeDescribeBench
{

	private const STRINGS = 256;

	/** @var list<ConstantStringType> */
	private array $strings = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		BenchContainer::create($this->scratch);

		for ($i = 0; $i < self::STRINGS; $i++) {
			$string = new ConstantStringType('The value of an enum case number ' . $i);
			$string->describe(VerbosityLevel::value());
			$string->describe(VerbosityLevel::precise());
			$this->strings[] = $string;
		}
	}

	/**
	 * Strings already described, as the members of a union of an enum's values are again and again.
	 */
	#[Expected(instructions: '513.6K', cycles: '726.7K', memory: '0B')]
	public function benchDescribeAgain(): string
	{
		$description = '';
		foreach ($this->strings as $string) {
			$string->describe(VerbosityLevel::value());
			$description = $string->describe(VerbosityLevel::precise());
		}

		return $description;
	}

}
