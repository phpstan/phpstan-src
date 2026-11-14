<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use Closure;
use LogicException;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;
use ReflectionMethod;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

final class ParametersAcceptorSelectorBench
{

	/** @var (Closure(ParametersAcceptor): bool)|null */
	private ?Closure $hasTemplateOrLateResolvableType = null;

	private ?ParametersAcceptor $acceptor = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int, bool}>
	 */
	public static function returnTypes(): iterable
	{
		yield 'template-first-16' => [16, true];
		yield 'template-first-256' => [256, true];
		yield 'no-template-256' => [256, false];
	}

	#[Before]
	public function boot(int $items, bool $templateFirst): void
	{
		BenchContainer::create($this->scratch);
		// private before 2.1.34, selectFromArgs() asks it for every call with a single variant
		$this->hasTemplateOrLateResolvableType = (new ReflectionMethod(ParametersAcceptorSelector::class, 'hasAcceptorTemplateOrLateResolvableType'))->getClosure();
		($this->hasTemplateOrLateResolvableType)(self::acceptor(4, true));
		($this->hasTemplateOrLateResolvableType)(self::acceptor(4, false));

		$this->acceptor = self::acceptor($items, $templateFirst);
	}

	/**
	 * Whether a variant needs its template types resolved, for a function returning an array shape
	 * of nested shapes. The search can stop at a template type in the first item.
	 */
	#[DataProvider(method: 'returnTypes')]
	public function benchHasAcceptorTemplateOrLateResolvableType(int $items, bool $templateFirst): bool
	{
		if ($this->hasTemplateOrLateResolvableType === null || $this->acceptor === null) {
			throw new LogicException('boot() did not run.');
		}

		return ($this->hasTemplateOrLateResolvableType)($this->acceptor);
	}

	private static function acceptor(int $items, bool $templateFirst): ParametersAcceptor
	{
		$keys = [];
		$values = [];
		for ($i = 0; $i < $items; $i++) {
			$keys[] = new ConstantStringType('item' . $i);
			$values[] = $i === 0 && $templateFirst
				? TemplateTypeFactory::create(TemplateTypeScope::createWithFunction('f'), 'T', null, TemplateTypeVariance::createInvariant())
				: self::shape($i);
		}

		return new FunctionVariant(TemplateTypeMap::createEmpty(), null, [], false, new ConstantArrayType($keys, $values));
	}

	private static function shape(int $i): Type
	{
		return new ConstantArrayType(
			[new ConstantStringType('id'), new ConstantStringType('name'), new ConstantStringType('position')],
			[new ConstantIntegerType($i), new ConstantStringType('name' . $i), new ConstantIntegerType($i * 2)],
		);
	}

}
