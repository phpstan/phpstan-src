<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generator;

use PhpParser\Node;
use PHPStan\Analyser\NodeCallbackInvoker;
use PHPStan\Analyser\Scope;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use function sprintf;

/**
 * @extends RuleTestCase<Rule<node>>
 */
#[RequiresPhp('>= 8.1')]
class GeneratorNodeScopeResolverRuleTest extends RuleTestCase
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
					RuleErrorBuilder::message($arg0->describe(VerbosityLevel::precise()))->identifier('gnsr.rule')->build(),
					RuleErrorBuilder::message($scope->getType($node->getArgs()[1]->value)->describe(VerbosityLevel::precise()))->identifier('gnsr.rule')->build(),
					RuleErrorBuilder::message($scope->getType($node->getArgs()[2]->value)->describe(VerbosityLevel::precise()))->identifier('gnsr.rule')->build(),
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
					RuleErrorBuilder::message($synthetic->describe(VerbosityLevel::precise()))->identifier('gnsr.rule')->build(),
					RuleErrorBuilder::message($synthetic2->describe(VerbosityLevel::precise()))->identifier('gnsr.rule')->build(),
				];
			},
			[
				['\'foo\'', 21],
				['\'bar\'', 21],
			],
		];
		yield [
			static function (Node $node, Scope $scope) {
				/** @var Scope&NodeCallbackInvoker $scope */
				if ($node instanceof Node\Stmt\Echo_) {
					$echoExprType = $scope->getType($node->exprs[0]);
					return [
						RuleErrorBuilder::message($echoExprType->describe(VerbosityLevel::precise()))
							->identifier('gnsr.rule')
							->build(),
					];
				}
				if (!$node instanceof Node\Expr\MethodCall) {
					return [];
				}

				$scope->invokeNodeCallback(new Node\Stmt\Echo_([
					$node->getArgs()[0]->value,
				], $node->getAttributes()));

				return [];
			},
			[
				['1', 21],
				['\'foo\'', 23],
			],
		];
		yield [
			static function (Node $node, Scope $scope) {
				/** @var Scope&NodeCallbackInvoker $scope */
				if ($node instanceof Node\Stmt\Echo_) {
					$scope->invokeNodeCallback(new Node\Expr\Print_($node->exprs[0], $node->getAttributes()));
					return [];
				}

				if ($node instanceof Node\Expr\Print_) {
					$scope->invokeNodeCallback(new Node\Expr\MethodCall(new Node\Expr\Variable('foo'), 'doFoo', [new Node\Arg($node->expr)], $node->getAttributes()));
					return [
						RuleErrorBuilder::message(sprintf('Virtual node invoked: %s, %s', $scope->getType($node->expr)->describe(VerbosityLevel::value()), $scope->getType(new Node\Expr\BinaryOp\Plus(new Node\Scalar\Int_(1), new Node\Scalar\Int_(2)))->describe(VerbosityLevel::value())))
							->identifier('gnsr.rule')
							->build(),
					];
				}

				if ($node instanceof Node\Expr\Exit_) {
					return [
						RuleErrorBuilder::message('exit through')->line(666)->identifier('gnsr.rule')->build(),
					];
				}

				if (!$node instanceof Node\Expr\MethodCall) {
					return [];
				}

				$arg0 = $scope->getType($node->getArgs()[0]->value);
				$scope->invokeNodeCallback(new Node\Expr\Exit_());
				$arg0 = $scope->getType($node->getArgs()[0]->value);

				return [
					RuleErrorBuilder::message(sprintf('Called on %s, arg: %s', $scope->getType($node->var)->describe(VerbosityLevel::precise()), $arg0->describe(VerbosityLevel::precise())))->identifier('gnsr.rule')->build(),
				];
			},
			[
				['exit through', 666],
				['Called on GeneratorNodeScopeResolverRule\\Foo, arg: 1', 21],
				['exit through', 666],
				['Virtual node invoked: \'foo\', 3', 23],
				['Called on GeneratorNodeScopeResolverRule\\Foo, arg: \'foo\'', 23],
			],
		];
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

	protected function createNodeScopeResolver(): GeneratorNodeScopeResolver
	{
		$container = self::getContainer();
		return new GeneratorNodeScopeResolver(
			$container->getByType(ExprPrinter::class),
			$container,
		);
	}

}
