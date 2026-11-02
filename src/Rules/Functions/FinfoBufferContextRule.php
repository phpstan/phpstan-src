<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use finfo;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\MultipleNodeTypesRule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

/**
 * @implements MultipleNodeTypesRule<Expr>
 */
#[RegisteredRule(level: 0)]
final class FinfoBufferContextRule implements MultipleNodeTypesRule
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PhpVersion $phpVersion,
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
		if (!$this->phpVersion->deprecatesFinfoBufferContext()) {
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
			if ($reflection->getName() !== 'finfo_buffer') {
				return [];
			}
			$callName = 'finfo_buffer()';
		} elseif ($node instanceof Expr\MethodCall) {
			if (!$node->name instanceof Node\Identifier || $node->name->toLowerString() !== 'buffer' || $node->isFirstClassCallable()) {
				return [];
			}

			$reflection = $scope->getMethodReflection($scope->getType($node->var), $node->name->toString());
			if ($reflection === null || $reflection->getDeclaringClass()->getName() !== finfo::class) {
				return [];
			}
			$callName = sprintf('%s::%s()', finfo::class, $reflection->getName());
		} else {
			return [];
		}

		$args = $node->getArgs();
		$contextPosition = null;
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs($scope, $args, $reflection->getVariants(), $reflection->getNamedArgumentsVariants());
		foreach ($parametersAcceptor->getParameters() as $i => $parameter) {
			if ($parameter->getName() === 'context') {
				$contextPosition = $i;
				break;
			}
		}
		if ($contextPosition === null) {
			return [];
		}

		foreach ($args as $i => $arg) {
			if ($arg->unpack) {
				return [];
			}

			$isContext = $arg->name === null ? $i === $contextPosition : $arg->name->toString() === 'context';
			if (!$isContext) {
				continue;
			}

			$errorBuilder = RuleErrorBuilder::message(sprintf(
				'Passing the $context parameter to %s is deprecated in PHP 8.5.',
				$callName,
			))
				->tip('The parameter has no effect, remove it.')
				->identifier('argument.unused');

			// removing other expressions would drop their side effects
			if (
				$arg->value instanceof Expr\ConstFetch
				|| $arg->value instanceof Expr\Variable
				|| $arg->value instanceof Node\Scalar
			) {
				$errorBuilder->removeNode($arg);
			}

			return [$errorBuilder->build()];
		}

		return [];
	}

}
