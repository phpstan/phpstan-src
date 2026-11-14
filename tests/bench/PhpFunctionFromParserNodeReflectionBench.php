<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node;
use PhpParser\ParserFactory;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use ReflectionClass;
use ReflectionProperty;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function sprintf;

final class PhpFunctionFromParserNodeReflectionBench
{

	private ?PhpFunctionFromParserNodeReflection $function = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function statements(): iterable
	{
		foreach ([16, 256] as $statements) {
			yield 'statements-' . $statements => [$statements];
		}
	}

	#[Before]
	public function boot(int $statements): void
	{
		BenchContainer::create($this->scratch);
		self::reflect(4)->isGenerator();

		$this->function = self::reflect($statements);
		$this->function->isGenerator();
	}

	/**
	 * Whether a function is a generator, asked again for the same function, which scans the body for
	 * a yield unless the answer is memoized on its node.
	 */
	#[DataProvider(method: 'statements')]
	#[ExpectedFor('statements-16', instructions: '1.268K', cycles: '16.56K', memory: '0B')]
	#[ExpectedFor('statements-256', instructions: '1.268K', cycles: '16.67K', memory: '0B')]
	public function benchIsGeneratorAgain(int $statements): bool
	{
		if ($this->function === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->function->isGenerator();
	}

	/**
	 * A reflection of a function that is not a generator, with only the node isGenerator() reads,
	 * because the constructor differs between versions.
	 */
	private static function reflect(int $statements): PhpFunctionFromParserNodeReflection
	{
		$code = "<?php\n\nfunction values(int \$a): int\n{\n";
		for ($i = 0; $i < $statements; $i++) {
			$code .= sprintf("\t\$a = \$a + %d;\n", $i);
		}
		$code .= "\treturn \$a;\n}\n";
		$function = (new ParserFactory())->createForHostVersion()->parse($code)[0] ?? null;
		if (!$function instanceof Node\Stmt\Function_) {
			throw new LogicException('Not parsed.');
		}

		$reflection = (new ReflectionClass(PhpFunctionFromParserNodeReflection::class))->newInstanceWithoutConstructor();
		(new ReflectionProperty(PhpFunctionFromParserNodeReflection::class, 'functionLike'))->setValue($reflection, $function);

		return $reflection;
	}

}
