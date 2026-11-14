<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\GenericParametersAcceptorResolver;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateTypeFactory;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Type;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;

final class GenericParametersAcceptorResolverBench
{

	private ?ParametersAcceptor $acceptor = null;

	/** @var list<Type> */
	private array $argTypes = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function parameters(): iterable
	{
		foreach ([2, 16] as $parameters) {
			yield 'parameters-' . $parameters => [$parameters];
		}
	}

	#[Before]
	public function boot(int $parameters): void
	{
		BenchContainer::create($this->scratch);
		[$acceptor, $argTypes] = self::call(2);
		GenericParametersAcceptorResolver::resolve($argTypes, $acceptor);

		[$this->acceptor, $this->argTypes] = self::call($parameters);
	}

	/**
	 * A variant that is not an ExtendedParametersAcceptor, such as one an extension returns, is
	 * resolved into an extended one with all of its parameters copied.
	 */
	#[DataProvider(method: 'parameters')]
	public function benchResolve(int $parameters): ParametersAcceptor
	{
		if ($this->acceptor === null) {
			throw new LogicException('boot() did not run.');
		}

		return GenericParametersAcceptorResolver::resolve($this->argTypes, $this->acceptor);
	}

	/**
	 * @return array{ParametersAcceptor, list<Type>} function f<T>(T $p0, T $p1, ...): T called with integers
	 */
	private static function call(int $parameters): array
	{
		$template = TemplateTypeFactory::create(TemplateTypeScope::createWithFunction('f'), 'T', null, TemplateTypeVariance::createInvariant());
		$params = [];
		$argTypes = [];
		for ($i = 0; $i < $parameters; $i++) {
			$params[] = new NativeParameterReflection('p' . $i, false, $template, PassedByReference::createNo(), false, null);
			$argTypes[] = new ConstantIntegerType($i);
		}

		return [new FunctionVariant(new TemplateTypeMap(['T' => $template]), null, $params, false, $template), $argTypes];
	}

}
