<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Api\NodeConnectingVisitorAttributesRule;
use PHPStan\Rules\Api\OldPhpParser4ClassRule;
use PHPStan\Rules\Classes\DuplicateClassDeclarationRule;
use PHPStan\Rules\Classes\InstantiationRule;
use PHPStan\Rules\Comparison\ImpossibleCheckTypeFunctionCallRule;
use PHPStan\Rules\Debug\DumpNativeTypeRule;
use PHPStan\Rules\Debug\DumpPhpDocTypeRule;
use PHPStan\Rules\Debug\DumpTypeRule;
use PHPStan\Rules\Debug\FileAssertRule;
use PHPStan\Rules\Functions\CallToFunctionParametersRule;
use PHPStan\Rules\Functions\DuplicateFunctionDeclarationRule;
use PHPStan\Rules\Functions\ParameterCastableToStringRule;
use PHPStan\Rules\Methods\CallPrivateMethodThroughStaticRule;
use PHPStan\Rules\Methods\CallToMethodStatementWithNoDiscardRule;
use PHPStan\Rules\Methods\CallToStaticMethodStatementWithNoDiscardRule;
use PHPStan\Rules\Methods\FinalPrivateMethodRule;
use PHPStan\Rules\PhpDoc\FunctionConditionalReturnTypeRule;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageRule;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageRule;
use PHPStan\Rules\Rule;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\Attribute\Warmup;
use Tally\ScratchDirectory;
use function array_map;
use function count;
use function implode;
use function range;
use function sprintf;
use function str_repeat;

/**
 * A rule checking the nodes of its type in a piece of code, with the scopes the analysis gave them.
 */
#[Warmup(calls: 1)]
final class RuleBench
{

	/** @var Rule<Node>|null */
	private ?Rule $rule = null;

	/** @var list<array{Node, MutatingScope}> */
	private array $nodes = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{class-string, string, ?int}> the rule, the code, and the PHP version
	 */
	public static function rules(): iterable
	{
		// the rule returns early for constructors before it asks for the PHP version
		yield 'final-private-method-constructors' => [FinalPrivateMethodRule::class, self::classes(static fn (int $i): string => sprintf('class Foo%d { public function __construct() {} }', $i)), null];

		// a method name that is not an identifier ends the check before the PHP version is asked whether it supports #[NoDiscard]
		yield 'call-to-method-statement-with-no-discard-dynamic-name' => [CallToMethodStatementWithNoDiscardRule::class, self::functionBody('\DateTime $date, string $name', '$date->$name();'), 80500];

		yield 'call-to-static-method-statement-with-no-discard-dynamic-name' => [CallToStaticMethodStatementWithNoDiscardRule::class, self::functionBody('string $name', '\\DateTime::$name();'), 80500];

		// a call without arguments cannot dump a type, so the rules do not resolve the function name
		yield 'dump-type-no-arguments' => [DumpTypeRule::class, self::functionBody('', 'time();'), null];
		yield 'dump-native-type-no-arguments' => [DumpNativeTypeRule::class, self::functionBody('', 'time();'), null];
		yield 'dump-phpdoc-type-no-arguments' => [DumpPhpDocTypeRule::class, self::functionBody('', 'time();'), null];
		// the type assertions have two arguments
		yield 'file-assert-one-argument' => [FileAssertRule::class, self::functionBody('string $s', 'strlen($s);'), null];

		// in a final class, static:: is the class itself, so the rule does not look up the method
		yield 'call-private-method-through-static-final-class' => [CallPrivateMethodThroughStaticRule::class, '<?php final class Foo { private static function bar(): void {} public function baz(): void { ' . str_repeat('static::bar(); ', 16) . '} }', null];

		// the rules ask the container for the restricted usage extensions once
		yield 'restricted-method-usage' => [RestrictedMethodUsageRule::class, self::functionBody('\DateTime $date', '$date->format(\'Y\');'), null];
		yield 'restricted-function-usage' => [RestrictedFunctionUsageRule::class, self::functionBody('string $s', 'strlen($s);'), null];
		yield 'instantiation-restricted-constructor' => [InstantiationRule::class, '<?php class Foo { public function __construct(int $i) {} } function test(): void { ' . str_repeat('new Foo(1); ', 16) . '}', null];

		// the rules for extensions of PHPStan check that the code is in a class first
		yield 'old-php-parser4-class-outside-class' => [OldPhpParser4ClassRule::class, self::functionBody('', '$a = PHP_EOL;'), null];

		yield 'node-connecting-visitor-attributes-no-arguments' => [NodeConnectingVisitorAttributesRule::class, self::functionBody('\\SplObjectStorage $s', '$s->getAttribute();'), null];

		// the classes and functions of the analysed code are grouped by name once, not searched for each declaration
		yield 'duplicate-class-declaration' => [DuplicateClassDeclarationRule::class, self::classes(static fn (int $i): string => sprintf('class Foo%d {}', $i)), null];
		yield 'duplicate-function-declaration' => [DuplicateFunctionDeclarationRule::class, '<?php ' . implode(' ', array_map(static fn (int $i): string => sprintf('function foo%d(): void {}', $i), range(1, 64))), null];

		// the return type of the type-checking function is not resolved again
		yield 'impossible-check-type-function-call' => [ImpossibleCheckTypeFunctionCallRule::class, self::functionBody('int|string $v', 'if (is_int($v)) {}'), null];

		// only the values under keys both arrays have are compared as strings
		yield 'parameter-castable-to-string-diff-assoc' => [ParameterCastableToStringRule::class, self::functionBody('', 'array_diff_assoc([' . self::keys('a', 'new \stdClass()') . '], [' . self::keys('b', '1') . ']);'), null];

		// a parameter or return type without late-resolvable types cannot contain a conditional type
		yield 'function-conditional-return-type-without-conditional-types' => [FunctionConditionalReturnTypeRule::class, '<?php ' . self::shapeFunctions(), null];

		// the original parameter and return types are only searched for unresolvable types when the resolved ones have one
		yield 'call-to-function-parameters-resolvable-types' => [CallToFunctionParametersRule::class, '<?php ' . self::shapeFunctions() . ' function test(array $a): void { ' . str_repeat('shape0($a, $a, $a, $a); ', 16) . '}', null];
	}

	/**
	 * @param class-string $rule
	 */
	#[Before]
	public function boot(string $rule, string $code, ?int $phpVersion): void
	{
		$analysed = AnalysedCode::analyse($this->scratch, $code);
		$instance = $analysed->get($rule, $phpVersion === null ? [] : ['phpVersion' => new PhpVersion($phpVersion)]);
		if (!$instance instanceof Rule) {
			throw new LogicException($rule . ' is not a rule.');
		}
		$this->rule = $instance;
		$this->nodes = $analysed->nodes($instance->getNodeType());
	}

	/**
	 * @param class-string $rule
	 */
	#[DataProvider(method: 'rules')]
	#[ExpectedFor(dataSet: 'call-private-method-through-static-final-class', instructions: '59.05K', cycles: '62.60K', memory: '0B')]
	#[ExpectedFor(dataSet: 'call-to-function-parameters-resolvable-types', instructions: '117.0M', cycles: '141.7M', memory: '13.45KiB')]
	#[ExpectedFor(dataSet: 'call-to-method-statement-with-no-discard-dynamic-name', instructions: '20.65K', cycles: '22.46K', memory: '0B')]
	#[ExpectedFor(dataSet: 'call-to-static-method-statement-with-no-discard-dynamic-name', instructions: '20.80K', cycles: '22.27K', memory: '0B')]
	#[ExpectedFor(dataSet: 'dump-native-type-no-arguments', instructions: '21.29K', cycles: '22.59K', memory: '0B')]
	#[ExpectedFor(dataSet: 'dump-phpdoc-type-no-arguments', instructions: '21.29K', cycles: '22.58K', memory: '0B')]
	#[ExpectedFor(dataSet: 'dump-type-no-arguments', instructions: '21.29K', cycles: '22.66K', memory: '0B')]
	#[ExpectedFor(dataSet: 'duplicate-class-declaration', instructions: '116.1K', cycles: '126.1K', memory: '0B')]
	#[ExpectedFor(dataSet: 'duplicate-function-declaration', instructions: '163.1K', cycles: '174.8K', memory: '0B')]
	#[ExpectedFor(dataSet: 'file-assert-one-argument', instructions: '21.25K', cycles: '22.65K', memory: '0B')]
	#[ExpectedFor(dataSet: 'final-private-method-constructors', instructions: '138.2K', cycles: '146.6K', memory: '0B')]
	#[ExpectedFor(dataSet: 'function-conditional-return-type-without-conditional-types', instructions: '143.7K', cycles: '153.7K', memory: '376B')]
	#[ExpectedFor(dataSet: 'impossible-check-type-function-call', instructions: '6.250M', cycles: '8.182M', memory: '5.758KiB')]
	#[ExpectedFor(dataSet: 'instantiation-restricted-constructor', instructions: '3.525M', cycles: '4.949M', memory: '6.531KiB')]
	#[ExpectedFor(dataSet: 'node-connecting-visitor-attributes-no-arguments', instructions: '38.75K', cycles: '40.87K', memory: '40B')]
	#[ExpectedFor(dataSet: 'old-php-parser4-class-outside-class', instructions: '20.21K', cycles: '21.31K', memory: '0B')]
	#[ExpectedFor(dataSet: 'parameter-castable-to-string-diff-assoc', instructions: '6.028M', cycles: '6.950M', memory: '3.172KiB')]
	#[ExpectedFor(dataSet: 'restricted-function-usage', instructions: '282.2K', cycles: '347.2K', memory: '848B')]
	#[ExpectedFor(dataSet: 'restricted-method-usage', instructions: '376.2K', cycles: '477.7K', memory: '256B')]
	public function benchProcessNode(string $rule, string $code, ?int $phpVersion): int
	{
		if ($this->rule === null) {
			throw new LogicException('boot() did not run.');
		}

		$errors = 0;
		foreach ($this->nodes as [$node, $scope]) {
			$errors += count($this->rule->processNode($node, $scope));
		}

		return $errors;
	}

	/**
	 * @param callable(int): string $class
	 */
	private static function classes(callable $class): string
	{
		return '<?php ' . implode(' ', array_map($class, range(1, 64)));
	}

	private static function functionBody(string $parameters, string $statement): string
	{
		return sprintf('<?php function test(%s): void { %s }', $parameters, str_repeat($statement . ' ', 16));
	}

	private static function keys(string $prefix, string $value): string
	{
		return implode(', ', array_map(static fn (int $i): string => sprintf("'%s%d' => %s", $prefix, $i, $value), range(1, 16)));
	}

	/**
	 * Functions whose four parameters and return type are array shapes of 16 keys.
	 */
	private static function shapeFunctions(): string
	{
		$shape = 'array{' . implode(', ', array_map(static fn (int $i): string => sprintf('k%d: int|string', $i), range(1, 16))) . '}';
		$functions = [];
		for ($i = 0; $i < 16; $i++) {
			$functions[] = sprintf(
				"/**\n * @param %1\$s \$a\n * @param %1\$s \$b\n * @param %1\$s \$c\n * @param %1\$s \$d\n * @return %1\$s\n */\nfunction shape%2\$d(array \$a, array \$b, array \$c, array \$d): array { return \$a; }",
				$shape,
				$i,
			);
		}

		return implode(' ', $functions);
	}

}
