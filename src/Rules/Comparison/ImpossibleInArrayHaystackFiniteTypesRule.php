<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function count;
use function max;
use function sprintf;
use function strtolower;

/**
 * Reports finite-typed values in a constant array haystack passed to in_array(),
 * array_search() or array_keys() that can never be the needle, using
 * Type::getConstantArrays() and Type::getFiniteTypes() instead of inspecting the
 * AST of the array literal.
 *
 * @implements Rule<FuncCall>
 */
#[RegisteredRule(level: 4)]
final class ImpossibleInArrayHaystackFiniteTypesRule implements Rule
{

	/** Argument positions of the needle and the haystack per supported function. */
	private const FUNCTIONS = [
		'in_array' => ['needle' => 0, 'haystack' => 1],
		'array_search' => ['needle' => 0, 'haystack' => 1],
		'array_keys' => ['needle' => 1, 'haystack' => 0],
	];

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		#[AutowiredParameter]
		private bool $treatPhpDocTypesAsCertain,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!$node->name instanceof Node\Name) {
			return [];
		}

		$functionName = strtolower((string) $node->name);
		if (!array_key_exists($functionName, self::FUNCTIONS)) {
			return [];
		}

		$needleArg = self::FUNCTIONS[$functionName]['needle'];
		$haystackArg = self::FUNCTIONS[$functionName]['haystack'];

		$args = $node->getArgs();
		if (count($args) <= max($needleArg, $haystackArg)) {
			return [];
		}

		$needleType = $this->treatPhpDocTypesAsCertain ? $scope->getType($args[$needleArg]->value) : $scope->getNativeType($args[$needleArg]->value);
		$haystackType = $this->treatPhpDocTypesAsCertain ? $scope->getType($args[$haystackArg]->value) : $scope->getNativeType($args[$haystackArg]->value);

		$constantArrays = $haystackType->getConstantArrays();
		if (count($constantArrays) === 0) {
			return [];
		}

		$isStrict = count($args) >= 3
			? ($this->treatPhpDocTypesAsCertain ? $scope->getType($args[2]->value) : $scope->getNativeType($args[2]->value))->isTrue()
			: TrinaryLogic::createNo();

		$deadValueTypes = [];
		$anyPossibleMatch = false;
		foreach ($constantArrays as $constantArray) {
			foreach ($constantArray->getValueTypes() as $valueType) {
				if (!$this->canNeverMatch($needleType, $valueType, $isStrict)) {
					$anyPossibleMatch = true;
					continue;
				}

				if (count($valueType->getFiniteTypes()) === 0) {
					continue;
				}

				$deadValueTypes[$valueType->describe(VerbosityLevel::precise())] = $valueType;
			}
		}

		// When no haystack value can ever match, the whole in_array() call is
		// impossible and reported by ImpossibleCheckTypeFunctionCallRule instead.
		// array_search() has no such companion rule, so keep reporting there.
		if (!$anyPossibleMatch && $functionName === 'in_array') {
			return [];
		}

		$verb = $isStrict->no() ? 'equal to' : 'identical to';

		$errors = [];
		foreach ($deadValueTypes as $valueType) {
			$errors[] = $this->buildError($valueType, $needleType, $functionName, $verb);
		}

		return $errors;
	}

	private function canNeverMatch(Type $needleType, Type $valueType, TrinaryLogic $isStrict): bool
	{
		if ($isStrict->yes()) {
			return $this->initializerExprTypeResolver->resolveIdenticalType($needleType, $valueType)->type->isFalse()->yes();
		}

		if ($isStrict->no()) {
			return $this->initializerExprTypeResolver->resolveEqualType($needleType, $valueType)->type->isFalse()->yes();
		}

		return $this->initializerExprTypeResolver->resolveIdenticalType($needleType, $valueType)->type->isFalse()->yes()
			&& $this->initializerExprTypeResolver->resolveEqualType($needleType, $valueType)->type->isFalse()->yes();
	}

	private function buildError(Type $valueType, Type $needleType, string $functionName, string $verb): IdentifierRuleError
	{
		return RuleErrorBuilder::message(sprintf(
			'Value %s in the haystack passed to %s() can never be %s the needle type %s.',
			$valueType->describe(VerbosityLevel::precise()),
			$functionName,
			$verb,
			$needleType->describe(VerbosityLevel::precise()),
		))->identifier('function.impossibleHaystackValue')->build();
	}

}
