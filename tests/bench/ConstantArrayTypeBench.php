<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateTypeReference;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use stdClass;
use Tally\Attribute\Before;
use Tally\Attribute\Constant;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\Attribute\Linear;
use Tally\Attribute\Warmup;
use Tally\ScratchDirectory;
use function array_slice;
use function count;
use function intdiv;

final class ConstantArrayTypeBench
{

	private ?ConstantArrayType $shape = null;

	private ?ConstantArrayType $sameShape = null;

	private ?ConstantStringType $key = null;

	private ?ConstantArrayType $sealed = null;

	private ?ConstantArrayType $sealedWithoutLastKey = null;

	private ?ConstantArrayType $nesting = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function keys(): iterable
	{
		foreach ([16, 64, 256] as $keys) {
			yield 'keys-' . $keys => [$keys];
		}
	}

	#[Before]
	public function boot(int $keys): void
	{
		BenchContainer::create($this->scratch);

		// the counted calls do not autoload, and do not hit what the warm-up memoized
		$warmup = self::shape('warmup', 4, true);
		$warmup->getOffsetValueType(new ConstantStringType('warmup1'));
		$warmup->isSuperTypeOf(self::shape('warmup', 4, true));

		$this->shape = self::shape('key', $keys, false);
		// builds the key index
		$this->shape->getOffsetValueType(new ConstantStringType('key0'));
		$this->sameShape = self::shape('key', $keys, false);
		$this->key = new ConstantStringType('key' . intdiv($keys, 2));

		$this->sealed = self::sealed($this->shape, $keys);
		$this->sealedWithoutLastKey = self::sealed($this->shape, $keys - 1);

		// every offset holds the same shape, which the analysis gets from a type alias
		$nested = self::shape('nested', 16, false);
		$keyTypes = [new ConstantStringType('object')];
		$valueTypes = [new ObjectType(stdClass::class)];
		for ($i = 0; $i < $keys; $i++) {
			$keyTypes[] = new ConstantStringType('key' . $i);
			$valueTypes[] = $nested;
		}
		$this->nesting = new ConstantArrayType($keyTypes, $valueTypes);
		$this->nesting->getReferencedClasses();
		$this->nesting->getReferencedTemplateTypes(TemplateTypeVariance::createCovariant());
	}

	/**
	 * A constant key is looked up in the key index, so the size of the array does not matter.
	 * The index is built on the first lookup, which boot() makes with another key.
	 */
	#[DataProvider(method: 'keys')]
	#[Constant(size: 'keys')]
	public function benchGetOffsetValueType(int $keys): Type
	{
		if ($this->key === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->subject()->getOffsetValueType($this->key);
	}

	#[DataProvider(method: 'keys')]
	#[Linear(size: 'keys')]
	public function benchIsSuperTypeOf(int $keys): bool
	{
		if ($this->sameShape === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->subject()->isSuperTypeOf($this->sameShape)->yes();
	}

	/**
	 * Two sealed shapes where one lacks a key of the other. Why they cannot be intersected is
	 * only described when it is reported.
	 */
	#[DataProvider(method: 'keys')]
	#[ExpectedFor(dataSet: 'keys-16', instructions: '49.30K', cycles: '133.3K', memory: '1.797KiB')]
	#[ExpectedFor(dataSet: 'keys-64', instructions: '164.7K', cycles: '275.5K', memory: '4.070KiB')]
	#[ExpectedFor(dataSet: 'keys-256', instructions: '625.6K', cycles: '871.5K', memory: '20.32KiB')]
	public function benchIsSuperTypeOfSealedWithoutKey(int $keys): bool
	{
		if ($this->sealed === null || $this->sealedWithoutLastKey === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->sealed->isSuperTypeOf($this->sealedWithoutLastKey)->no();
	}

	/**
	 * The verbosity of the reason is only chosen when an offset does not accept.
	 */
	#[DataProvider(method: 'keys')]
	#[ExpectedFor(dataSet: 'keys-16', instructions: '1.920M', cycles: '3.154M', memory: '92.96KiB')]
	#[ExpectedFor(dataSet: 'keys-64', instructions: '3.079M', cycles: '4.727M', memory: '92.96KiB')]
	#[ExpectedFor(dataSet: 'keys-256', instructions: '7.715M', cycles: '11.04M', memory: '96.77KiB')]
	public function benchAcceptsSameShape(int $keys): bool
	{
		if ($this->sameShape === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->subject()->accepts($this->sameShape, true)->yes();
	}

	/**
	 * Asked again of a shape whose offsets share one nested shape.
	 */
	/**
	 * @return list<string>
	 */
	#[DataProvider(method: 'keys')]
	#[Constant(size: 'keys')]
	public function benchGetReferencedClassesAgain(int $keys): array
	{
		return $this->nesting()->getReferencedClasses();
	}

	/**
	 * @return list<TemplateTypeReference>
	 */
	#[DataProvider(method: 'keys')]
	#[Constant(size: 'keys')]
	public function benchGetReferencedTemplateTypesAgain(int $keys): array
	{
		return $this->nesting()->getReferencedTemplateTypes(TemplateTypeVariance::createCovariant());
	}

	/**
	 * Fails before 2.1.34, which added the method, so the first call is a warm-up call of the
	 * benchmark instead of one in boot().
	 */
	#[DataProvider(method: 'keys')]
	#[Constant(size: 'keys')]
	#[Warmup(calls: 1)]
	public function benchHasTemplateOrLateResolvableTypeAgain(int $keys): bool
	{
		return $this->nesting()->hasTemplateOrLateResolvableType();
	}

	private function nesting(): ConstantArrayType
	{
		if ($this->nesting === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->nesting;
	}

	/**
	 * The first keys of the shape, sealed. Before 2.2.0 the constructor ignores the argument.
	 */
	private static function sealed(ConstantArrayType $shape, int $keys): ConstantArrayType
	{
		// positional, so the versions without the parameter ignore it instead of failing
		$arguments = [
			array_slice($shape->getKeyTypes(), 0, $keys),
			array_slice($shape->getValueTypes(), 0, $keys),
			[0],
			[],
			null,
			[new NeverType(true), new NeverType(true)],
		];

		return new ConstantArrayType(...$arguments);
	}

	private function subject(): ConstantArrayType
	{
		if ($this->shape === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->shape;
	}

	public static function shape(string $prefix, int $keys, bool $optional): ConstantArrayType
	{
		$builder = ConstantArrayTypeBuilder::createEmpty();
		for ($i = 0; $i < $keys; $i++) {
			$builder->setOffsetValueType(new ConstantStringType($prefix . $i), new IntegerType(), $optional);
		}

		$arrays = $builder->getArray()->getConstantArrays();
		if (count($arrays) !== 1) {
			throw new LogicException('The builder did not build one constant array.');
		}

		return $arrays[0];
	}

}
