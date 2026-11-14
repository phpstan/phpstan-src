<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantStringType;
use function array_key_exists;
use function array_values;
use function count;
use function in_array;
use function sprintf;
use function str_starts_with;
use function strtoupper;

/**
 * @implements Rule<FuncCall>
 */
#[Conditional(
	attribute: new RegisteredRule(level: 0),
	if: '%featureToggles.phpLanguageDeprecations%',
)]
final class MbstringDeprecatedEncodingRule implements Rule
{

	/** Functions configuring mbstring do not report the deprecation. */
	private const IGNORED_FUNCTIONS = [
		'mb_internal_encoding',
		'mb_http_output',
		'mb_detect_order',
		'mb_preferred_mime_name',
		'mb_regex_encoding',
	];

	private const ENCODINGS = [
		'HTML-ENTITIES' => 'HTML entities',
		'HTML' => 'HTML entities',
		'BASE64' => 'Base64',
		'UUENCODE' => 'Uuencode',
		'QUOTED-PRINTABLE' => 'QPrint',
		'QPRINT' => 'QPrint',
	];

	private const REPLACEMENTS = [
		'HTML entities' => 'htmlspecialchars(), htmlentities(), or mb_encode_numericentity()/mb_decode_numericentity()',
		'Base64' => 'base64_encode()/base64_decode()',
		'Uuencode' => 'convert_uuencode()/convert_uudecode()',
		'QPrint' => 'quoted_printable_encode()/quoted_printable_decode()',
	];

	public function __construct(
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if ($scope->getPhpVersion()->deprecatesMbstringPseudoEncodings()->no()) {
			return [];
		}

		if (!$node->name instanceof Node\Name || count($node->getArgs()) === 0) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		$functionName = $functionReflection->getName();
		if (!str_starts_with($functionName, 'mb_') || in_array($functionName, self::IGNORED_FUNCTIONS, true)) {
			return [];
		}

		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$node->getArgs(),
			$functionReflection->getVariants(),
			$functionReflection->getNamedArgumentsVariants(),
		);
		$normalizedFuncCall = ArgumentsNormalizer::reorderFuncArguments($parametersAcceptor, $node);
		if ($normalizedFuncCall === null) {
			return [];
		}

		$args = $normalizedFuncCall->getArgs();
		$errors = [];
		foreach ($parametersAcceptor->getParameters() as $i => $parameter) {
			if (!in_array($parameter->getName(), ['encoding', 'to_encoding'], true) || !array_key_exists($i, $args)) {
				continue;
			}

			foreach ($this->getDeprecatedEncodings($scope->getType($args[$i]->value)->getConstantStrings()) as $encoding) {
				$errors[] = RuleErrorBuilder::message(sprintf(
					'Handling %s via mbstring in %s() is deprecated in PHP 8.2.',
					$encoding,
					$functionName,
				))
					->tip(sprintf('Use %s instead.', self::REPLACEMENTS[$encoding]))
					->identifier('argument.deprecatedEncoding')
					->build();
			}
		}

		return $errors;
	}

	/**
	 * @param list<ConstantStringType> $constantStrings
	 * @return list<value-of<self::ENCODINGS>>
	 */
	private function getDeprecatedEncodings(array $constantStrings): array
	{
		$encodings = [];
		foreach ($constantStrings as $constantString) {
			$encoding = self::ENCODINGS[strtoupper($constantString->getValue())] ?? null;
			if ($encoding === null) {
				return [];
			}

			$encodings[$encoding] = $encoding;
		}

		return array_values($encodings);
	}

}
