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
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_merge;
use function array_unique;
use function array_values;
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
#[RegisteredRule(level: 4, enabledBy: '%featureToggles.finiteTypesInHaystack%')]
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
		#[AutowiredParameter(ref: '%treatPhpDocTypesAsCertain%')]
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

		/** @var array<string, array{Type, list<string>, bool}> $deadValueTypes */
		$deadValueTypes = [];
		$anyPossibleMatch = false;
		foreach ($constantArrays as $constantArray) {
			foreach ($constantArray->getValueTypes() as $valueType) {
				$canNeverMatchReasons = $this->findCanNeverMatchReasons($needleType, $valueType, $isStrict);
				if ($canNeverMatchReasons === null) {
					$anyPossibleMatch = true;
					continue;
				}

				if (count($valueType->getFiniteTypes()) === 0) {
					continue;
				}

				$deadValueTypes[$valueType->describe(VerbosityLevel::precise())] = [
					$valueType,
					$canNeverMatchReasons,
					$this->isAnotherValueOfNeedleType($needleType, $valueType),
				];
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
		foreach ($deadValueTypes as [$valueType, $reasons, $anotherValueOfNeedleType]) {
			if ($anyPossibleMatch && $anotherValueOfNeedleType) {
				continue;
			}

			$errors[] = $this->buildError($valueType, $needleType, $functionName, $verb, $reasons);
		}

		return $errors;
	}

	/**
	 * A haystack value that is merely a different value of the needle's own type - another
	 * case of the same enum, another string of a string set - is what set-membership checks
	 * against a constant list are made of, so it is only worth reporting when nothing in the
	 * haystack matches at all. Values of a kind the needle can never have (a foreign enum
	 * case, a string among ints, a null under a not-null needle) point at a genuine mistake
	 * even when another entry does match.
	 */
	private function isAnotherValueOfNeedleType(Type $needleType, Type $valueType): bool
	{
		return $valueType->generalize(GeneralizePrecision::lessSpecific())->isSuperTypeOf($needleType)->yes();
	}

	/**
	 * @return list<string>|null Reasons for the impossibility when the value can never match, null when it can match.
	 */
	private function findCanNeverMatchReasons(Type $needleType, Type $valueType, TrinaryLogic $isStrict): ?array
	{
		if ($isStrict->yes()) {
			$result = $this->initializerExprTypeResolver->resolveIdenticalType($needleType, $valueType);

			return $result->type->isFalse()->yes() ? $result->reasons : null;
		}

		if ($isStrict->no()) {
			$result = $this->initializerExprTypeResolver->resolveEqualType($needleType, $valueType);

			return $result->type->isFalse()->yes() ? $result->reasons : null;
		}

		$identicalResult = $this->initializerExprTypeResolver->resolveIdenticalType($needleType, $valueType);
		if (!$identicalResult->type->isFalse()->yes()) {
			return null;
		}

		$equalResult = $this->initializerExprTypeResolver->resolveEqualType($needleType, $valueType);
		if (!$equalResult->type->isFalse()->yes()) {
			return null;
		}

		return array_values(array_unique(array_merge($identicalResult->reasons, $equalResult->reasons)));
	}

	/**
	 * @param list<string> $reasons
	 */
	private function buildError(Type $valueType, Type $needleType, string $functionName, string $verb, array $reasons): IdentifierRuleError
	{
		return RuleErrorBuilder::message(sprintf(
			'Value %s in the haystack passed to %s() can never be %s the needle type %s.',
			$valueType->describe(VerbosityLevel::getRecommendedLevelByType($valueType)),
			$functionName,
			$verb,
			$needleType->describe(VerbosityLevel::getRecommendedLevelByType($needleType)),
		))->identifier('function.impossibleHaystackValue')
			->acceptsReasonsTip($reasons)
			->build();
	}

}
