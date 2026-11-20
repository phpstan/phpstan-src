<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Php\PrintfFormatParser;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_values;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

/**
 * Formatting a float that is not finite prints "NaN", "INF", "-INF" or
 * "inf" with the float placeholders and number_format(), and an unspecified
 * number with the int placeholders.
 *
 * @implements Rule<FuncCall>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 5),
	if: '%featureToggles.nonFiniteFloats%',
)]
final class FormatNonFiniteFloatRule implements Rule
{

	private const FORMAT_ARGUMENT_POSITIONS = [
		'printf' => 0,
		'sprintf' => 0,
		'fprintf' => 1,
	];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private PrintfFormatParser $printfFormatParser,
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
		if ($name === null) {
			return [];
		}
		$name = strtolower($name);

		$args = $node->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack) {
				return [];
			}
		}

		if ($name === 'number_format') {
			if (count($args) === 0) {
				return [];
			}

			$error = $this->check(
				$this->helper->canBeNonFinite($scope->getType($args[0]->value)),
				$scope->getType($args[0]->value),
				'Parameter #1 $num of function number_format()',
				'"nan" or "inf"',
				'it is not a finite float',
			);

			return $error === null ? [] : [$error];
		}

		if (!array_key_exists($name, self::FORMAT_ARGUMENT_POSITIONS)) {
			return [];
		}

		$formatPosition = self::FORMAT_ARGUMENT_POSITIONS[$name];
		if (!isset($args[$formatPosition])) {
			return [];
		}

		$formats = $scope->getType($args[$formatPosition]->value)->getConstantStrings();
		if (count($formats) !== 1) {
			return [];
		}

		$uses = $this->printfFormatParser->parse($formats[0]->getValue(), $scope->getPhpVersion());
		if ($uses === null) {
			return [];
		}

		$errors = [];
		foreach ($uses as $use) {
			if ($use['kind'] !== 'value') {
				continue;
			}
			$argIndex = $formatPosition + 1 + $use['index'];
			if (!isset($args[$argIndex])) {
				continue;
			}

			$type = $scope->getType($args[$argIndex]->value);
			$label = sprintf('Placeholder "%s" of function %s()', $use['placeholder'], $name);
			if (in_array($use['specifier'], ['e', 'E', 'f', 'F', 'g', 'G', 'h', 'H'], true)) {
				$error = $this->check($this->helper->canBeNonFinite($type), $type, $label, '"NaN", "INF" or "-INF"', 'it is not a finite float');
			} elseif (in_array($use['specifier'], ['d', 'u', 'c', 'o', 'x', 'X', 'b'], true)) {
				$error = $this->check($this->helper->canBeOutOfIntRange($type), $type, $label, 'an unspecified number', 'it is NAN, INF or out of the int range');
			} elseif ($use['specifier'] === 's' && $scope->getPhpVersion()->warnsOnNonFiniteFloatConversion()->yes()) {
				$error = $this->check($this->helper->canBeNan($type), $type, $label, '"NAN" with a warning', 'it is NAN');
			} else {
				continue;
			}

			// a placeholder used twice, like `%1$f %1$f`, is reported once
			if ($error === null) {
				continue;
			}

			$errors[$error->getMessage()] = $error;
		}

		return array_values($errors);
	}

	private function check(TrinaryLogic $canBe, Type $type, string $label, string $output, string $condition): ?IdentifierRuleError
	{
		if (!$this->helper->shouldReport($canBe)) {
			return null;
		}

		return RuleErrorBuilder::message(sprintf(
			'%s formats %s, which prints %s%s.',
			$label,
			$type->describe(VerbosityLevel::value()),
			$output,
			$canBe->yes() ? '' : ' when ' . $condition,
		))->identifier('nonFiniteFloat.format')->build();
	}

}
