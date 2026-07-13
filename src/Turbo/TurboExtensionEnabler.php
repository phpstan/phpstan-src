<?php declare(strict_types = 1);

namespace PHPStan\Turbo;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\UnaryMinus;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Expr\Yield_;
use PhpParser\Node\Expr\YieldFrom;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\VariadicPlaceholder;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Analyser\ConditionalExpressionHolder;
use PHPStan\Analyser\ExpressionTypeHolder;
use PHPStan\Node\Expr\IntertwinedVariableByReferenceWithExpr;
use PHPStan\Node\VirtualNode;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use PHPStanTurbo\Runtime;
use function extension_loaded;
use function getenv;
use function phpversion;

final class TurboExtensionEnabler
{

	/**
	 * The native classes must match the PHP implementations exactly, so the
	 * extension is only enabled when its version is the expected one. The
	 * version is the short SHA of the last commit touching turbo-ext/src/,
	 * enforced by the turbo-ext.yml version job.
	 */
	public const EXPECTED_EXTENSION_VERSION = '8d04889';

	public static function isLoaded(): bool
	{
		return extension_loaded('phpstan_turbo');
	}

	public static function enableIfLoaded(): void
	{
		if (!self::isLoaded()) {
			return;
		}

		if (getenv('PHPSTAN_TURBO') === '0') {
			return;
		}

		if (phpversion('phpstan_turbo') !== self::EXPECTED_EXTENSION_VERSION) {
			return;
		}

		// Class names the extension needs at runtime, passed as ::class
		// constants so they stay correct under the scoped phar. The *Impl
		// entries name the classes the extension instantiates — these are the
		// stub subclasses loaded below, so that every object satisfies the
		// original type hints.
		Runtime::configure([
			'typeCombinator' => TypeCombinator::class,
			'booleanType' => BooleanType::class,
			'constantBooleanType' => ConstantBooleanType::class,
			'shouldNotHappenException' => ShouldNotHappenException::class,
			'verbosityLevel' => VerbosityLevel::class,
			'variable' => Variable::class,
			'funcCall' => FuncCall::class,
			'virtualNode' => VirtualNode::class,
			'node' => Node::class,
			'name' => Name::class,
			'expr' => Expr::class,
			'propertyFetch' => PropertyFetch::class,
			'intertwinedVariableByReferenceWithExpr' => IntertwinedVariableByReferenceWithExpr::class,
			'arrayDimFetch' => ArrayDimFetch::class,
			'methodCall' => MethodCall::class,
			'functionLike' => FunctionLike::class,
			'callLike' => CallLike::class,
			'staticCall' => StaticCall::class,
			'newExpr' => New_::class,
			'classStmt' => Class_::class,
			'variadicPlaceholder' => VariadicPlaceholder::class,
			'errorType' => ErrorType::class,
			'scalar' => Scalar::class,
			'arrayExpr' => Array_::class,
			'unaryMinus' => UnaryMinus::class,
			'yield' => Yield_::class,
			'yieldFrom' => YieldFrom::class,
			'stmt' => Stmt::class,
			'nodeVisitorAbstract' => NodeVisitorAbstract::class,
			'closureExpr' => Closure::class,
			'arrowFunction' => ArrowFunction::class,
			'trinaryLogicImpl' => TrinaryLogic::class,
			'expressionTypeHolderImpl' => ExpressionTypeHolder::class,
			'conditionalExpressionHolderImpl' => ConditionalExpressionHolder::class,
		]);

		// Shadow the PHP implementations with stubs extending the extension's
		// native classes. The stubs are declared before the Composer autoloader
		// registers, so later references to the original names resolve to them.
		require_once __DIR__ . '/../../turbo-ext/stubs/TrinaryLogic.php';
		require_once __DIR__ . '/../../turbo-ext/stubs/ExpressionTypeHolder.php';
		require_once __DIR__ . '/../../turbo-ext/stubs/ConditionalExpressionHolder.php';
		require_once __DIR__ . '/../../turbo-ext/stubs/CombinationsHelper.php';
		require_once __DIR__ . '/../../turbo-ext/stubs/NodeTraverser.php';
		require_once __DIR__ . '/../../turbo-ext/stubs/ScopeOps.php';
		require_once __DIR__ . '/../../turbo-ext/stubs/NodeScanner.php';
		require_once __DIR__ . '/../../turbo-ext/stubs/ParserRunner.php';
	}

}
