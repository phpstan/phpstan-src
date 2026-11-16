<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function count;
use function sprintf;
use function strtolower;

/**
 * json_encode() fails on INF and NAN: it returns false, or throws a
 * JsonException with JSON_THROW_ON_ERROR.
 *
 * @implements Rule<FuncCall>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.nonFiniteFloats%',
)]
final class JsonEncodeNonFiniteFloatRule implements Rule
{

	private const ARRAY_DEPTH_LIMIT = 8;

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private NonFiniteFloatHelper $helper,
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

		$name = $this->reflectionProvider->resolveFunctionName($node->name, $scope);
		if ($name === null || strtolower($name) !== 'json_encode') {
			return [];
		}

		$args = $node->getArgs();
		if (count($args) === 0 || $args[0]->unpack) {
			return [];
		}

		$valueType = $scope->getType($args[0]->value);
		$canBe = $this->canContainNonFinite($valueType, 0);
		if (!$this->helper->shouldReport($canBe)) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Parameter #1 $value of function json_encode() %s, which cannot be JSON encoded: %s given.',
				$canBe->yes() ? ($this->helper->canBeNonFinite($valueType)->yes() ? 'is INF or NAN' : 'contains INF or NAN') : 'can contain INF or NAN',
				$valueType->describe(VerbosityLevel::value()),
			))->identifier('nonFiniteFloat.jsonEncode')->build(),
		];
	}

	/**
	 * Yes when the value certainly holds INF or NAN somewhere: itself, or in a
	 * key of an array shape that is not optional, or in every value of an array
	 * that is not empty.
	 */
	private function canContainNonFinite(Type $type, int $depth): TrinaryLogic
	{
		$canBe = $this->helper->canBeNonFinite($type);
		if ($canBe->yes() || $depth >= self::ARRAY_DEPTH_LIMIT || !$type->isArray()->yes()) {
			return $canBe;
		}

		$constantArrays = $type->getConstantArrays();
		if (count($constantArrays) > 0) {
			$results = [];
			foreach ($constantArrays as $constantArray) {
				$result = TrinaryLogic::createNo();
				foreach ($constantArray->getValueTypes() as $i => $valueType) {
					$inValue = $this->canContainNonFinite($valueType, $depth + 1);
					if ($constantArray->isOptionalKey($i)) {
						$inValue = $inValue->and(TrinaryLogic::createMaybe());
					}
					$result = $result->or($inValue);
				}
				$results[] = $result;
			}

			return $canBe->or(TrinaryLogic::extremeIdentity(...$results));
		}

		$inValues = $this->canContainNonFinite($type->getIterableValueType(), $depth + 1);
		if (!$type->isIterableAtLeastOnce()->yes()) {
			$inValues = $inValues->and(TrinaryLogic::createMaybe());
		}

		return $canBe->or($inValues);
	}

}
