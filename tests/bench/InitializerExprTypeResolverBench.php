<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\ArrayItem;
use PhpParser\Node\Expr;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

final class InitializerExprTypeResolverBench
{

	private ?InitializerExprTypeResolver $resolver = null;

	private ?Expr $ternary = null;

	private ?Expr $unpack = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function items(): iterable
	{
		foreach ([8, 128] as $items) {
			yield 'items-' . $items => [$items];
		}
	}

	#[Before]
	public function boot(int $items): void
	{
		$this->resolver = BenchContainer::create($this->scratch)->getByType(InitializerExprTypeResolver::class);
		$this->resolver->getType(self::ternary(4), InitializerExprContext::createEmpty());
		$this->resolver->getType(self::unpack(4), InitializerExprContext::createEmpty());

		$this->ternary = self::ternary($items);
		$this->unpack = self::unpack($items);
	}

	/**
	 * A ternary with both branches, `[...] ? 1 : 2`, does not need the type of its condition.
	 */
	#[DataProvider(method: 'items')]
	public function benchTernary(int $items): Type
	{
		if ($this->ternary === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->resolver()->getType($this->ternary, InitializerExprContext::createEmpty());
	}

	/**
	 * `[...['k0' => 0, 'k1' => 1, ...]]`, whose string keys are kept since PHP 8.1
	 */
	#[DataProvider(method: 'items')]
	public function benchUnpackStringKeys(int $items): Type
	{
		if ($this->unpack === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->resolver()->getType($this->unpack, InitializerExprContext::createEmpty());
	}

	private function resolver(): InitializerExprTypeResolver
	{
		if ($this->resolver === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->resolver;
	}

	private static function ternary(int $items): Expr
	{
		return new Expr\Ternary(self::array($items, false), new Int_(1), new Int_(2));
	}

	private static function unpack(int $items): Expr
	{
		return new Expr\Array_([new ArrayItem(self::array($items, true), unpack: true)]);
	}

	private static function array(int $items, bool $stringKeys): Expr\Array_
	{
		$arrayItems = [];
		for ($i = 0; $i < $items; $i++) {
			$arrayItems[] = new ArrayItem(new Int_($i), $stringKeys ? new String_('k' . $i) : null);
		}

		return new Expr\Array_($arrayItems);
	}

}
