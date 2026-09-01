<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @extends RuleTestCase<Rule<Node>>
 */
class NodeCallbackScopeResolverRuleTest extends RuleTestCase
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

				$scope->getType($node->getArgs()[0]->value); // on purpose to hit the cache
				$arg0 = $scope->getType($node->getArgs()[0]->value);

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


	/**
	 * @param callable(Node, Scope): list<IdentifierRuleError> $ruleCallback
	 * @param list<array{0: string, 1: int, 2?: string|null}> $expectedErrors
	 * @return void
	 */
	#[DataProvider('dataRule')]
	public function testRule(callable $ruleCallback, array $expectedErrors): void
	{
		$this->ruleCallback = $ruleCallback;
		$this->analyse([__DIR__ . '/data/node-callback-scope-rule.php'], $expectedErrors);
	}

}
