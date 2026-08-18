<?php declare(strict_types = 1);

namespace PHPStan\Build;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function array_key_exists;
use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * The single-pass engine must not price expressions through Scope::getType()
 * and friends - every walked node's type lives in its ExpressionResult
 * (threaded via ArgsResult, storage, or gathered statement scopes). A scope
 * read in engine code either re-walks a node on demand or silently diverges
 * from the stored result. The allowlist below names the documented seams
 * (rule-facing bridge asks and ask-ahead-of-walk reads).
 *
 * @implements Rule<MethodCall>
 */
final class NoScopeTypeReadInEngineRule implements Rule
{

	private const ENGINE_NAMESPACES = [
		'PHPStan\\Analyser\\NodeScopeResolver',
		'PHPStan\\Analyser\\StmtHandler\\',
		'PHPStan\\Analyser\\ExprHandler\\',
	];

	private const BANNED_METHODS = ['getType', 'getNativeType', 'getKeepVoidType'];

	private const ALLOWED_SEAMS = [
		// rule-facing bridge asks: the scope carries no storage
		'PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureTypeResolver' => ['readExprType', 'resolveArrowFunctionReturnType'],
		'PHPStan\\Analyser\\ExprHandler\\Helper\\CountNarrowingHelper' => ['isNormalCountCall'],
		// immediately invoked closures: the invocation args are walked after the closure
		'PHPStan\\Analyser\\ExprHandler\\Helper\\ClosureTypeResolver#buildParametersAndAcceptors' => [],
	];

	public function getNodeType(): string
	{
		return MethodCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Identifier) {
			return [];
		}
		$methodName = $node->name->toString();
		if (!in_array($methodName, self::BANNED_METHODS, true)) {
			return [];
		}

		$currentClassReflection = $scope->getClassReflection();
		if ($currentClassReflection === null) {
			return [];
		}
		$namespace = $currentClassReflection->getName();
		$inEngine = false;
		foreach (self::ENGINE_NAMESPACES as $prefix) {
			if ($namespace === $prefix || str_starts_with($namespace, $prefix)) {
				$inEngine = true;
				break;
			}
		}
		if (!$inEngine) {
			return [];
		}

		$calledOnType = $scope->getType($node->var);
		if ($calledOnType->getObjectClassNames() === []) {
			return [];
		}
		$isScope = false;
		foreach ($calledOnType->getObjectClassReflections() as $classReflection) {
			if ($classReflection->is(Scope::class)) {
				$isScope = true;
				break;
			}
		}
		if (!$isScope) {
			return [];
		}

		$function = $scope->getFunction();
		$functionName = $function !== null ? $function->getName() : null;
		if (
			array_key_exists($namespace, self::ALLOWED_SEAMS)
			&& $functionName !== null
			&& in_array($functionName, self::ALLOWED_SEAMS[$namespace], true)
		) {
			return [];
		}
		if ($functionName !== null && array_key_exists($namespace . '#' . $functionName, self::ALLOWED_SEAMS)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Engine code must not price expressions via Scope::%s() - read the expression\'s stored ExpressionResult (ArgsResult, storage, gathered scopes) instead.',
				$methodName,
			))->identifier('phpstanBuild.scopeTypeReadInEngine')->build(),
		];
	}

}
