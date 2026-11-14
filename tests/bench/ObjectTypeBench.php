<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use ArrayObject;
use DateTime;
use LogicException;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

final class ObjectTypeBench
{

	private ?ObjectType $object = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{class-string}>
	 */
	public static function classes(): iterable
	{
		yield 'class' => [DateTime::class];
		yield 'generic-class' => [ArrayObject::class];
	}

	#[Before]
	public function boot(string $className): void
	{
		BenchContainer::create($this->scratch);
		$this->object = new ObjectType($className);
		$this->object->getClassReflection();
	}

	/**
	 * The type was asked for its class before.
	 */
	#[DataProvider(method: 'classes')]
	#[ExpectedFor(dataSet: 'class', instructions: '741', cycles: '9.321K', memory: '0B')]
	#[ExpectedFor(dataSet: 'generic-class', instructions: '741', cycles: '9.321K', memory: '0B')]
	public function benchGetClassReflection(string $className): ?ClassReflection
	{
		if ($this->object === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->object->getClassReflection();
	}

}
