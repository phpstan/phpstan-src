<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Fiber;

use PhpParser\Node;
use PHPStan\Analyser\ExpressionResultFactory;
use PHPStan\Analyser\ExprHandler\Helper\ImplicitToStringCallHelper;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\DirectExtensionsCollection;
use PHPStan\File\FileHelper;
use PHPStan\PhpDoc\PhpDocInheritanceResolver;
use PHPStan\Reflection\ClassReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Properties\ReadWritePropertiesExtension;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\MethodParameterClosureThisExtension;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\MethodParameterOutTypeExtension;
use PHPStan\Type\StaticMethodParameterClosureThisExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterOutTypeExtension;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<Rule<Node>>
 */
#[RequiresPhp('>= 8.1.0')]
class FiberNodeScopeResolverRuleTest extends RuleTestCase
{

	/** @var callable(Node, Scope): list<IdentifierRuleError> */
	private $ruleCallback;

	protected function getRule(): Rule
	{
		return new class ($this->ruleCallback) implements Rule {

			/**
			 * @param callable(Node, Scope): list<IdentifierRuleError> $ruleCallback
			 */
			public function __construct(private $ruleCallback)
			{
			}

			public function getNodeType(): string
			{
				return Node::class;
			}

			public function processNode(Node $node, Scope $scope): array
			{
				return ($this->ruleCallback)($node, $scope);
			}

		};
	}

	public static function dataRule(): iterable
	{
		yield [
			static fn (Node $node, Scope $scope) => [],
			[],
		];
		yield [
			static function (Node $node, Scope $scope) {
				if (!$node instanceof Node\Expr\MethodCall) {
					return [];
				}

				$arg0 = $scope->getType($node->getArgs()[0]->value);
				$arg0 = $scope->getType($node->getArgs()[0]->value); // on purpose to hit the cache

				return [
					RuleErrorBuilder::message($arg0->describe(VerbosityLevel::precise()))->identifier('fnsr.rule')->build(),
					RuleErrorBuilder::message($scope->getType($node->getArgs()[1]->value)->describe(VerbosityLevel::precise()))->identifier('fnsr.rule')->build(),
					RuleErrorBuilder::message($scope->getType($node->getArgs()[2]->value)->describe(VerbosityLevel::precise()))->identifier('fnsr.rule')->build(),
				];
			},
			[
				['1', 21],
				['2', 21],
				['3', 21],
			],
		];
		yield [
			static function (Node $node, Scope $scope) {
				if (!$node instanceof Node\Expr\MethodCall) {
					return [];
				}

				$synthetic = $scope->getType(new Node\Scalar\String_('foo'));
				$synthetic2 = $scope->getType(new Node\Scalar\String_('bar'));

				return [
					RuleErrorBuilder::message($synthetic->describe(VerbosityLevel::precise()))->identifier('fnsr.rule')->build(),
					RuleErrorBuilder::message($synthetic2->describe(VerbosityLevel::precise()))->identifier('fnsr.rule')->build(),
				];
			},
			[
				['\'foo\'', 21],
				['\'bar\'', 21],
			],
		];
	}

	protected function createNodeScopeResolver(): NodeScopeResolver
	{
		$readWritePropertiesExtensions = $this->getReadWritePropertiesExtensions();
		$reflectionProvider = $this->createReflectionProvider();
		$typeSpecifier = $this->getTypeSpecifier();

		return new FiberNodeScopeResolver(
			self::getContainer(),
			$reflectionProvider,
			self::getContainer()->getByType(InitializerExprTypeResolver::class),
			self::getReflector(),
			self::getContainer()->getByType(ClassReflectionFactory::class),
			self::getContainer()->getExtensionsCollection(FunctionParameterOutTypeExtension::class),
			self::getContainer()->getExtensionsCollection(MethodParameterOutTypeExtension::class),
			self::getContainer()->getExtensionsCollection(StaticMethodParameterOutTypeExtension::class),
			$this->getParser(),
			self::getContainer()->getByType(FileTypeMapper::class),
			self::getContainer()->getByType(PhpDocInheritanceResolver::class),
			self::getContainer()->getByType(FileHelper::class),
			$typeSpecifier,
			$readWritePropertiesExtensions !== [] ? new DirectExtensionsCollection($readWritePropertiesExtensions) : self::getContainer()->getExtensionsCollection(ReadWritePropertiesExtension::class),
			self::getContainer()->getExtensionsCollection(FunctionParameterClosureThisExtension::class),
			self::getContainer()->getExtensionsCollection(MethodParameterClosureThisExtension::class),
			self::getContainer()->getExtensionsCollection(StaticMethodParameterClosureThisExtension::class),
			self::getContainer()->getExtensionsCollection(FunctionParameterClosureTypeExtension::class),
			self::getContainer()->getExtensionsCollection(MethodParameterClosureTypeExtension::class),
			self::getContainer()->getExtensionsCollection(StaticMethodParameterClosureTypeExtension::class),
			self::createScopeFactory($reflectionProvider, $typeSpecifier),
			$this->shouldPolluteScopeWithLoopInitialAssignments(),
			$this->shouldPolluteScopeWithAlwaysIterableForeach(),
			self::getContainer()->getParameter('featureToggles')['narrowForeachBodyNonEmpty'],
			self::getContainer()->getParameter('polluteScopeWithBlock'),
			self::getContainer()->getParameter('exceptions')['implicitThrows'],
			$this->shouldTreatPhpDocTypesAsCertain(),
			self::getContainer()->getByType(ImplicitToStringCallHelper::class),
			self::getContainer()->getByType(ExpressionResultFactory::class),
		);
	}

	/**
	 * @param callable(Node, Scope): list<IdentifierRuleError> $ruleCallback
	 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
	 * @return void
	 */
	#[DataProvider('dataRule')]
	public function testRule(callable $ruleCallback, array $expectedErrors): void
	{
		$this->ruleCallback = $ruleCallback;
		$this->analyse([__DIR__ . '/data/rule.php'], $expectedErrors);
	}

}
