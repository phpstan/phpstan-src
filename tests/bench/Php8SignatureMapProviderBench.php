<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Reflection\SignatureMap\Php8SignatureMapProvider;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

final class Php8SignatureMapProviderBench
{

	private ?Php8SignatureMapProvider $provider = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{list<string>, list<string>}>
	 */
	public static function members(): iterable
	{
		$constants = ['ATTR_AUTOCOMMIT', 'ATTR_CASE', 'ATTR_CLIENT_VERSION', 'ATTR_CONNECTION_STATUS', 'ATTR_CURSOR', 'ATTR_CURSOR_NAME', 'ATTR_DEFAULT_FETCH_MODE', 'ATTR_DRIVER_NAME'];
		$methods = ['beginTransaction', 'commit', 'errorCode', 'errorInfo', 'exec', 'getAttribute', 'inTransaction', 'lastInsertId'];
		yield 'constants-1' => [['ATTR_CASE'], []];
		yield 'constants-8' => [$constants, []];
		yield 'constants-8-methods-8' => [$constants, $methods];
	}

	#[Before]
	public function boot(): void
	{
		$this->provider = BenchContainer::create($this->scratch)->getByType(Php8SignatureMapProvider::class);
		$this->provider->hasClassConstantMetadata('ReflectionMethod', 'IS_PUBLIC');
		$this->provider->hasMethodSignature('ReflectionMethod', 'invoke');
		// reads PDO's stub, which every later question about its members is answered from
		$this->provider->hasClassConstantMetadata('PDO', 'ATTR_ERRMODE');
	}

	/**
	 * Questions about further members of a class whose stub was read already, here PDO's. Each
	 * member looked for on its own used to walk the stub's AST again.
	 *
	 * @param list<string> $constants
	 * @param list<string> $methods
	 */
	#[DataProvider(method: 'members')]
	#[ExpectedFor(dataSet: 'constants-1', instructions: '4.015K', cycles: '36.03K', memory: '136B')]
	#[ExpectedFor(dataSet: 'constants-8', instructions: '28.44K', cycles: '65.63K', memory: '144B')]
	#[ExpectedFor(dataSet: 'constants-8-methods-8', instructions: '55.67K', cycles: '101.5K', memory: '144B')]
	public function benchFindMembers(array $constants, array $methods): int
	{
		if ($this->provider === null) {
			throw new LogicException('boot() did not run.');
		}

		$found = 0;
		foreach ($constants as $constant) {
			$found += (int) $this->provider->hasClassConstantMetadata('PDO', $constant);
		}
		foreach ($methods as $method) {
			$found += (int) $this->provider->hasMethodSignature('PDO', $method);
		}

		return $found;
	}

}
