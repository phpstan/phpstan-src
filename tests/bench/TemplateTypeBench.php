<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerType;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

/**
 * Template types bound to int.
 */
final class TemplateTypeBench
{

	private ?TemplateType $template = null;

	private ?UnionType $strings = null;

	/** @var list<TemplateType> */
	private array $withDefaults = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function members(): iterable
	{
		foreach ([16, 256] as $members) {
			yield 'members-' . $members => [$members];
		}
	}

	#[Before]
	public function boot(int $members): void
	{
		BenchContainer::create($this->scratch);
		$this->template = TemplateTypeFactory::create(
			TemplateTypeScope::createWithFunction('foo'),
			'T',
			new IntegerType(),
			TemplateTypeVariance::createInvariant(),
		);
		$this->template->inferTemplateTypes(self::strings('warmup', 4));
		$this->strings = self::strings('string', $members);

		self::describeAll(self::withDefaults(4));
		$this->withDefaults = self::withDefaults($members);
	}

	/**
	 * T0 of int = 0, T1 of int = 1, …: the description of a default is guarded against recursion.
	 */
	#[DataProvider(method: 'members')]
	#[ExpectedFor(dataSet: 'members-16', instructions: '150.0K', cycles: '261.1K', memory: '2.820KiB')]
	#[ExpectedFor(dataSet: 'members-256', instructions: '2.385M', cycles: '2.976M', memory: '10.51KiB')]
	public function benchDescribeWithDefault(int $members): string
	{
		return self::describeAll($this->withDefaults);
	}

	/**
	 * @param list<TemplateType> $templates
	 */
	private static function describeAll(array $templates): string
	{
		$description = '';
		foreach ($templates as $template) {
			$description .= $template->describe(VerbosityLevel::typeOnly());
		}

		return $description;
	}

	/**
	 * @return list<TemplateType>
	 */
	private static function withDefaults(int $members): array
	{
		$templates = [];
		for ($i = 0; $i < $members; $i++) {
			$templates[] = TemplateTypeFactory::create(
				TemplateTypeScope::createWithFunction('foo'),
				'T' . $i,
				new IntegerType(),
				TemplateTypeVariance::createInvariant(),
				default: new ConstantIntegerType($i),
			);
		}

		return $templates;
	}

	/**
	 * None of the strings is an int, so T is not inferred from any of them.
	 */
	#[DataProvider(method: 'members')]
	#[ExpectedFor(dataSet: 'members-16', instructions: '72.31K', cycles: '193.8K', memory: '1.469KiB')]
	#[ExpectedFor(dataSet: 'members-256', instructions: '930.9K', cycles: '1.141M', memory: '32.22KiB')]
	public function benchInferTemplateTypesFromUnion(int $members): TemplateTypeMap
	{
		if ($this->template === null || $this->strings === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->template->inferTemplateTypes($this->strings);
	}

	private static function strings(string $prefix, int $members): UnionType
	{
		$strings = [];
		for ($i = 0; $i < $members; $i++) {
			$strings[] = new ConstantStringType($prefix . $i);
		}

		return new UnionType($strings);
	}

}
