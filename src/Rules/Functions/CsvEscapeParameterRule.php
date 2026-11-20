<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use SplFileObject;
use function count;
use function in_array;
use function sprintf;

/**
 * @implements MultipleNodeTypesRule<Expr>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 0),
	if: '%featureToggles.phpLanguageDeprecations%',
)]
final class CsvEscapeParameterRule implements MultipleNodeTypesRule
{

	private const FUNCTIONS = ['fgetcsv', 'fputcsv', 'str_getcsv'];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeTypes(): array
	{
		return [Expr\FuncCall::class, Expr\MethodCall::class];
	}

	public function getNodeType(): string
	{
		return Expr::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getPhpVersion()->deprecatesCsvEscapeParameterDefault()->no()) {
			return [];
		}

		if ($node instanceof Expr\FuncCall) {
			if (!$node->name instanceof Node\Name || $node->isFirstClassCallable()) {
				return [];
			}
			if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
				return [];
			}

			$reflection = $this->reflectionProvider->getFunction($node->name, $scope);
			if (!in_array($reflection->getName(), self::FUNCTIONS, true)) {
				return [];
			}
			$callName = sprintf('%s()', $reflection->getName());
		} elseif ($node instanceof Expr\MethodCall) {
			// SplFileObject::fgetcsv() and fputcsv() use the escape set by setCsvControl()
			if (!$node->name instanceof Node\Identifier || $node->name->toLowerString() !== 'setcsvcontrol' || $node->isFirstClassCallable()) {
				return [];
			}

			$reflection = $scope->getMethodReflection($scope->getType($node->var), $node->name->toString());
			if ($reflection === null || $reflection->getDeclaringClass()->getName() !== SplFileObject::class) {
				return [];
			}
			$callName = sprintf('%s::%s()', SplFileObject::class, $reflection->getName());
		} else {
			return [];
		}

		$args = $node->getArgs();
		$parameterPositions = [];
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $args, $reflection->getVariants(), $reflection->getNamedArgumentsVariants());
		foreach ($parametersAcceptor->getParameters() as $i => $parameter) {
			$parameterPositions[$parameter->getName()] = $i;
		}
		if (!isset($parameterPositions['escape'])) {
			return [];
		}
		$escapePosition = $parameterPositions['escape'];

		$positionalArgsCount = 0;
		foreach ($args as $arg) {
			if ($arg->unpack) {
				return [];
			}
			if ($arg->name === null) {
				$positionalArgsCount++;
				continue;
			}
			// a named argument after $escape makes PHP pass its default value explicitly,
			// an unknown one is an error reported by the argument check
			$position = $parameterPositions[$arg->name->toString()] ?? null;
			if ($position === null || $position >= $escapePosition) {
				return [];
			}
		}

		if ($positionalArgsCount > $escapePosition) {
			return [];
		}

		$appendPositionally = $positionalArgsCount === count($args) && $positionalArgsCount === $escapePosition;

		return [
			RuleErrorBuilder::message(sprintf(
				'Calling %s without the $escape parameter is deprecated in PHP 8.4.',
				$callName,
			))
				->tip('Pass \'\\\\\' to keep the current behaviour, or an empty string to disable the proprietary escape mechanism.')
				->identifier('argument.missingEscape')
				->fixNode($node, static function (Expr\FuncCall|Expr\MethodCall $node) use ($appendPositionally): Expr\FuncCall|Expr\MethodCall {
					$node->args[] = new Node\Arg(
						new Node\Scalar\String_('\\'),
						name: $appendPositionally ? null : new Node\Identifier('escape'),
					);

					return $node;
				})
				->build(),
		];
	}

}
