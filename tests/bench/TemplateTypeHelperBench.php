<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

final class TemplateTypeHelperBench
{

	private ?Type $shape = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function keys(): iterable
	{
		foreach ([16, 256] as $keys) {
			yield 'keys-' . $keys => [$keys];
		}
	}

	#[Before]
	public function boot(int $keys): void
	{
		BenchContainer::create($this->scratch);
		self::resolve(self::shape('warmup', 4));
		$this->shape = self::shape('key', $keys);
	}

	/**
	 * An array shape without template types, such as the return type of most functions.
	 */
	#[DataProvider(method: 'keys')]
	public function benchResolveTemplateTypes(int $keys): Type
	{
		if ($this->shape === null) {
			throw new LogicException('boot() did not run.');
		}

		return self::resolve($this->shape);
	}

	private static function resolve(Type $type): Type
	{
		return TemplateTypeHelper::resolveTemplateTypes(
			$type,
			TemplateTypeMap::createEmpty(),
			TemplateTypeVarianceMap::createEmpty(),
			TemplateTypeVariance::createInvariant(),
		);
	}

	private static function shape(string $prefix, int $keys): Type
	{
		$keyTypes = [];
		$valueTypes = [];
		for ($i = 0; $i < $keys; $i++) {
			$keyTypes[] = new ConstantStringType($prefix . $i);
			$valueTypes[] = new IntegerType();
		}

		return new ConstantArrayType($keyTypes, $valueTypes);
	}

}
