<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use DateTime;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ObjectType;
use ReflectionClass;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;
use function count;
use function dirname;
use function file_put_contents;

/**
 * Arrays that call a method of an object, [$dateTime, 'format'], analysed for PHP 7.4, where
 * a callable array may name an instance method statically.
 */
final class ConstantArrayTypePhp74Bench
{

	private const METHODS = ['createFromFormat', 'getLastErrors', 'format', 'modify', 'setDate', 'setTime', 'getTimestamp', 'getTimezone'];

	/** @var list<ConstantArrayType> */
	private array $arrays = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$config = $this->scratch->path() . '/php-7.4.neon';
		file_put_contents($config, "parameters:\n\tphpVersion: 70400\n");
		$containerFactory = new ContainerFactory(dirname((string) (new ReflectionClass(ContainerFactory::class))->getFileName(), 3));
		$containerFactory->create($this->scratch->path(), [$containerFactory->getConfigDirectory() . '/config.level8.neon', $config], []);

		// reflects the methods
		foreach (self::arrays() as $array) {
			$array->findTypeAndMethodNames();
		}
		$this->arrays = self::arrays();
	}

	/**
	 * [$dateTime, 'format'] and the other methods of DateTime, called on an object.
	 */
	public function benchFindTypeAndMethodNames(): int
	{
		$found = 0;
		foreach ($this->arrays as $array) {
			$found += count($array->findTypeAndMethodNames());
		}

		return $found;
	}

	/**
	 * @return list<ConstantArrayType>
	 */
	private static function arrays(): array
	{
		$arrays = [];
		foreach (self::METHODS as $method) {
			$arrays[] = new ConstantArrayType(
				[new ConstantIntegerType(0), new ConstantIntegerType(1)],
				[new ObjectType(DateTime::class), new ConstantStringType($method)],
				[2],
			);
		}

		return $arrays;
	}

}
