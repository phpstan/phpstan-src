<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;

final class ArgumentsNormalizerBench
{

	private ?ParametersAcceptor $acceptor = null;

	private ?FuncCall $call = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{bool}>
	 */
	public static function calls(): iterable
	{
		yield 'positional' => [false];
		yield 'named' => [true];
	}

	#[Before]
	public function boot(bool $named): void
	{
		$container = BenchContainer::create($this->scratch);
		$this->acceptor = $container->getByType(ReflectionProvider::class)->getFunction(new Name('str_replace'), null)->getVariants()[0];

		ArgumentsNormalizer::reorderFuncArguments($this->acceptor, self::call(false));
		ArgumentsNormalizer::reorderFuncArguments($this->acceptor, self::call(true));

		$this->call = self::call($named);
	}

	/**
	 * A call without named arguments is returned as it is, a call with them is rebuilt in the order of the parameters.
	 */
	#[DataProvider(method: 'calls')]
	#[ExpectedFor(dataSet: 'named', instructions: '24.75K', cycles: '99.67K', memory: '2.516KiB')]
	#[ExpectedFor(dataSet: 'positional', instructions: '2.670K', cycles: '21.83K', memory: '0B')]
	public function benchReorderFuncArguments(bool $named): ?FuncCall
	{
		if ($this->acceptor === null || $this->call === null) {
			throw new LogicException('boot() did not run.');
		}

		return ArgumentsNormalizer::reorderFuncArguments($this->acceptor, $this->call);
	}

	private static function call(bool $named): FuncCall
	{
		$search = new String_('a');
		$replace = new String_('b');
		$subject = new Variable('subject');
		if (!$named) {
			return new FuncCall(new Name('str_replace'), [new Arg($search), new Arg($replace), new Arg($subject)]);
		}

		return new FuncCall(new Name('str_replace'), [
			new Arg($subject, name: new Identifier('subject')),
			new Arg($search, name: new Identifier('search')),
			new Arg($replace, name: new Identifier('replace')),
		]);
	}

}
