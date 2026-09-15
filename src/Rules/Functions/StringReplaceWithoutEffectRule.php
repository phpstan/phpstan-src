<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_key_exists;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function min;
use function sprintf;
use function str_contains;
use function stripos;
use function strlen;
use function strpbrk;
use function substr;

/**
 * Reports calls to string replacement functions that cannot change the subject they're given.
 *
 * A typical cause are swapped arguments, like `strtr('\\', '/', $path)`
 * instead of `strtr($path, '\\', '/')`.
 *
 * @implements Rule<Node\Expr\FuncCall>
 */
final class StringReplaceWithoutEffectRule implements Rule
{

	private const IDENTIFIERS = [
		'strtr' => 'strtr.noEffect',
		'str_replace' => 'strReplace.noEffect',
		'str_ireplace' => 'strIreplace.noEffect',
		'substr_replace' => 'substrReplace.noEffect',
		'preg_replace' => 'pregReplace.noEffect',
		'preg_replace_callback' => 'pregReplaceCallback.noEffect',
		'preg_replace_callback_array' => 'pregReplaceCallbackArray.noEffect',
	];

	/**
	 * Position of the by-reference $count parameter. When it's passed, the call
	 * writes to it even when nothing gets replaced, so it's never without effect.
	 */
	private const COUNT_PARAMETER_POSITION = [
		'str_replace' => 3,
		'str_ireplace' => 3,
		'preg_replace' => 4,
		'preg_replace_callback' => 4,
		'preg_replace_callback_array' => 3,
	];

	/**
	 * Upper bound on how many subject/needle pairs are cross-checked. Both sides
	 * come from unions of constant strings which can grow large.
	 */
	private const STRING_COMBINATIONS_LIMIT = 64;

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private bool $treatPhpDocTypesAsCertain,
		private bool $treatPhpDocTypesAsCertainTip,
	)
	{
	}

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		if (!($node->name instanceof Node\Name)) {
			return [];
		}

		if (!$this->reflectionProvider->hasFunction($node->name, $scope)) {
			return [];
		}

		$functionReflection = $this->reflectionProvider->getFunction($node->name, $scope);
		$functionName = $functionReflection->getName();
		if (!array_key_exists($functionName, self::IDENTIFIERS)) {
			return [];
		}

		foreach ($node->getArgs() as $arg) {
			if ($arg->unpack) {
				return [];
			}
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

		if (
			array_key_exists($functionName, self::COUNT_PARAMETER_POSITION)
			&& array_key_exists(self::COUNT_PARAMETER_POSITION[$functionName], $args)
		) {
			return [];
		}

		$message = $this->findNoEffectMessage($functionName, $parametersAcceptor, $args, $scope, !$this->treatPhpDocTypesAsCertain);
		if ($message === null) {
			return [];
		}

		$errorBuilder = RuleErrorBuilder::message($message)->identifier(self::IDENTIFIERS[$functionName]);

		if (
			$this->treatPhpDocTypesAsCertain
			&& $this->treatPhpDocTypesAsCertainTip
			&& $this->findNoEffectMessage($functionName, $parametersAcceptor, $args, $scope, true) === null
		) {
			$errorBuilder->treatPhpDocTypesAsCertainTip();
		}

		return [$errorBuilder->build()];
	}

	/**
	 * @param Node\Arg[] $args
	 */
	private function findNoEffectMessage(
		string $functionName,
		ParametersAcceptor $parametersAcceptor,
		array $args,
		Scope $scope,
		bool $nativeTypes,
	): ?string
	{
		$types = [];
		foreach ($args as $i => $arg) {
			$types[$i] = $nativeTypes ? $scope->getNativeType($arg->value) : $scope->getType($arg->value);
		}

		if ($functionName === 'strtr') {
			return $this->findStrtrNoEffectMessage($parametersAcceptor, $types);
		}

		if ($functionName === 'substr_replace') {
			return $this->findSubstrReplaceNoEffectMessage($parametersAcceptor, $types);
		}

		if (in_array($functionName, ['preg_replace', 'preg_replace_callback', 'preg_replace_callback_array'], true)) {
			return $this->findPregReplaceNoEffectMessage($functionName, $parametersAcceptor, $types);
		}

		return $this->findStrReplaceNoEffectMessage($functionName, $parametersAcceptor, $types);
	}

	/**
	 * @param array<int, Type> $types
	 */
	private function findStrtrNoEffectMessage(ParametersAcceptor $parametersAcceptor, array $types): ?string
	{
		if (!array_key_exists(0, $types) || !array_key_exists(1, $types)) {
			return null;
		}

		if (!array_key_exists(2, $types)) {
			return $this->findStrtrPairsNoEffectMessage($parametersAcceptor, $types[0], $types[1]);
		}

		[$stringType, $fromType, $toType] = [$types[0], $types[1], $types[2]];
		if (!$fromType->isString()->yes() || !$toType->isString()->yes()) {
			return null;
		}

		if ($fromType->isNonEmptyString()->no()) {
			return sprintf(
				'Parameter #2 $%s (%s) of function strtr is an empty string, call has no effect.',
				$this->getParameterName($parametersAcceptor, 1),
				$fromType->describe(VerbosityLevel::value()),
			);
		}

		if ($toType->isNonEmptyString()->no()) {
			return sprintf(
				'Parameter #3 $%s (%s) of function strtr is an empty string, call has no effect.',
				$this->getParameterName($parametersAcceptor, 2),
				$toType->describe(VerbosityLevel::value()),
			);
		}

		$fromValues = $this->getConstantStringValues($fromType);
		if ($fromValues === null) {
			return null;
		}

		$toValues = $this->getConstantStringValues($toType);
		if ($toValues !== null && $this->isIdentityMapping($fromValues, $toValues)) {
			return sprintf(
				'Parameter #2 $%s (%s) and parameter #3 $%s (%s) of function strtr map every character to itself, call has no effect.',
				$this->getParameterName($parametersAcceptor, 1),
				$fromType->describe(VerbosityLevel::value()),
				$this->getParameterName($parametersAcceptor, 2),
				$toType->describe(VerbosityLevel::value()),
			);
		}

		$subjectValues = $this->getConstantStringValues($stringType);
		if ($subjectValues === null) {
			return null;
		}

		if (count($subjectValues) * count($fromValues) > self::STRING_COMBINATIONS_LIMIT) {
			return null;
		}

		foreach ($subjectValues as $subject) {
			foreach ($fromValues as $from) {
				// $to may be shorter than $from, in which case only a prefix of $from is
				// taken into account. Checking the whole $from is therefore conservative.
				if ($from !== '' && strpbrk($subject, $from) !== false) {
					return null;
				}
			}
		}

		return sprintf(
			'Parameter #1 $%s (%s) of function strtr does not contain any character from parameter #2 $%s (%s), call has no effect.',
			$this->getParameterName($parametersAcceptor, 0),
			$stringType->describe(VerbosityLevel::value()),
			$this->getParameterName($parametersAcceptor, 1),
			$fromType->describe(VerbosityLevel::value()),
		);
	}

	private function findStrtrPairsNoEffectMessage(
		ParametersAcceptor $parametersAcceptor,
		Type $stringType,
		Type $pairsType,
	): ?string
	{
		if (!$pairsType->isArray()->yes()) {
			return null;
		}

		if ($pairsType->isIterableAtLeastOnce()->no()) {
			return sprintf(
				'Parameter #2 $%s (%s) of function strtr is empty, call has no effect.',
				$this->getParameterName($parametersAcceptor, 1),
				$pairsType->describe(VerbosityLevel::value()),
			);
		}

		if ($this->mapsEveryPairToItself($pairsType)) {
			return sprintf(
				'Parameter #2 $%s (%s) of function strtr maps every string to itself, call has no effect.',
				$this->getParameterName($parametersAcceptor, 1),
				$pairsType->describe(VerbosityLevel::value()),
			);
		}

		$subjectValues = $this->getConstantStringValues($stringType);
		$keyValues = $this->getConstantStringValues($pairsType->getIterableKeyType()->toString());
		if ($subjectValues === null || $keyValues === null) {
			return null;
		}

		if (count($subjectValues) * count($keyValues) > self::STRING_COMBINATIONS_LIMIT) {
			return null;
		}

		foreach ($subjectValues as $subject) {
			foreach ($keyValues as $key) {
				if ($key === '') {
					return null;
				}

				if (str_contains($subject, $key)) {
					return null;
				}
			}
		}

		return sprintf(
			'Parameter #1 $%s (%s) of function strtr does not contain any of the replaced strings from parameter #2 $%s (%s), call has no effect.',
			$this->getParameterName($parametersAcceptor, 0),
			$stringType->describe(VerbosityLevel::value()),
			$this->getParameterName($parametersAcceptor, 1),
			$pairsType->describe(VerbosityLevel::value()),
		);
	}

	/**
	 * @param array<int, Type> $types
	 */
	private function findStrReplaceNoEffectMessage(
		string $functionName,
		ParametersAcceptor $parametersAcceptor,
		array $types,
	): ?string
	{
		if (!array_key_exists(0, $types) || !array_key_exists(1, $types) || !array_key_exists(2, $types)) {
			return null;
		}

		[$searchType, $replaceType, $subjectType] = [$types[0], $types[1], $types[2]];

		if ($searchType->isArray()->yes() && $searchType->isIterableAtLeastOnce()->no()) {
			return sprintf(
				'Parameter #1 $%s (%s) of function %s is empty, call has no effect.',
				$this->getParameterName($parametersAcceptor, 0),
				$searchType->describe(VerbosityLevel::value()),
				$functionName,
			);
		}

		if ($searchType->isString()->yes() && $searchType->isNonEmptyString()->no()) {
			return sprintf(
				'Parameter #1 $%s (%s) of function %s is an empty string, call has no effect.',
				$this->getParameterName($parametersAcceptor, 0),
				$searchType->describe(VerbosityLevel::value()),
				$functionName,
			);
		}

		// str_ireplace('A', 'A', $s) still rewrites every lowercase 'a' to 'A'
		$searchConstantStrings = $searchType->getConstantStrings();
		$replaceConstantStrings = $replaceType->getConstantStrings();
		if (
			$functionName === 'str_replace'
			&& count($searchConstantStrings) === 1
			&& count($replaceConstantStrings) === 1
			&& $searchConstantStrings[0]->getValue() === $replaceConstantStrings[0]->getValue()
		) {
			return sprintf(
				'Parameter #1 $%s (%s) and parameter #2 $%s (%s) of function str_replace are the same, call has no effect.',
				$this->getParameterName($parametersAcceptor, 0),
				$searchType->describe(VerbosityLevel::value()),
				$this->getParameterName($parametersAcceptor, 1),
				$replaceType->describe(VerbosityLevel::value()),
			);
		}

		$searchValues = $this->getConstantStringValues($searchType);
		$subjectValues = $this->getConstantStringValues($subjectType);
		if ($searchValues === null || $subjectValues === null) {
			return null;
		}

		if (count($subjectValues) * count($searchValues) > self::STRING_COMBINATIONS_LIMIT) {
			return null;
		}

		foreach ($subjectValues as $subject) {
			foreach ($searchValues as $search) {
				if ($search === '') {
					continue;
				}

				if ($functionName === 'str_ireplace') {
					if (stripos($subject, $search) !== false) {
						return null;
					}
					continue;
				}

				if (str_contains($subject, $search)) {
					return null;
				}
			}
		}

		$message = $searchType->isString()->yes() && count($searchValues) === 1
			? 'Parameter #3 $%s (%s) of function %s does not contain parameter #1 $%s (%s), call has no effect.'
			: 'Parameter #3 $%s (%s) of function %s does not contain any of the strings from parameter #1 $%s (%s), call has no effect.';

		return sprintf(
			$message,
			$this->getParameterName($parametersAcceptor, 2),
			$subjectType->describe(VerbosityLevel::value()),
			$functionName,
			$this->getParameterName($parametersAcceptor, 0),
			$searchType->describe(VerbosityLevel::value()),
		);
	}

	/**
	 * @param array<int, Type> $types
	 */
	private function findSubstrReplaceNoEffectMessage(ParametersAcceptor $parametersAcceptor, array $types): ?string
	{
		if (!array_key_exists(1, $types) || !array_key_exists(3, $types)) {
			return null;
		}

		$replaceType = $types[1];
		$lengthType = $types[3];

		if (!$replaceType->isString()->yes() || !$replaceType->isNonEmptyString()->no()) {
			return null;
		}

		if (!(new ConstantIntegerType(0))->isSuperTypeOf($lengthType)->yes()) {
			return null;
		}

		return sprintf(
			'Parameter #2 $%s (%s) of function substr_replace is an empty string and parameter #4 $%s (%s) is zero, call has no effect.',
			$this->getParameterName($parametersAcceptor, 1),
			$replaceType->describe(VerbosityLevel::value()),
			$this->getParameterName($parametersAcceptor, 3),
			$lengthType->describe(VerbosityLevel::value()),
		);
	}

	/**
	 * @param array<int, Type> $types
	 */
	private function findPregReplaceNoEffectMessage(
		string $functionName,
		ParametersAcceptor $parametersAcceptor,
		array $types,
	): ?string
	{
		if (!array_key_exists(0, $types)) {
			return null;
		}

		$patternType = $types[0];
		if (!$patternType->isArray()->yes() || !$patternType->isIterableAtLeastOnce()->no()) {
			return null;
		}

		return sprintf(
			'Parameter #1 $%s (%s) of function %s is empty, call has no effect.',
			$this->getParameterName($parametersAcceptor, 0),
			$patternType->describe(VerbosityLevel::value()),
			$functionName,
		);
	}

	/**
	 * Whether every character mapped by strtr()'s $from/$to pair maps to itself.
	 * Only the common prefix of both strings is taken into account, like PHP does.
	 *
	 * @param non-empty-list<string> $fromValues
	 * @param non-empty-list<string> $toValues
	 */
	private function isIdentityMapping(array $fromValues, array $toValues): bool
	{
		if (count($fromValues) * count($toValues) > self::STRING_COMBINATIONS_LIMIT) {
			return false;
		}

		foreach ($fromValues as $from) {
			foreach ($toValues as $to) {
				$length = min(strlen($from), strlen($to));
				if (substr($from, 0, $length) !== substr($to, 0, $length)) {
					return false;
				}
			}
		}

		return true;
	}

	private function mapsEveryPairToItself(Type $pairsType): bool
	{
		$constantArrays = $pairsType->getConstantArrays();
		if ($constantArrays === []) {
			return false;
		}

		foreach ($constantArrays as $constantArray) {
			$valueTypes = $constantArray->getValueTypes();
			foreach ($constantArray->getKeyTypes() as $i => $keyType) {
				if (!array_key_exists($i, $valueTypes)) {
					return false;
				}

				$keyValues = $this->getConstantStringValues($keyType->toString());
				$valueValues = $this->getConstantStringValues($valueTypes[$i]);
				if ($keyValues === null || $valueValues === null) {
					return false;
				}

				if (count($keyValues) !== 1 || $keyValues !== $valueValues) {
					return false;
				}
			}
		}

		return true;
	}

	private function getParameterName(ParametersAcceptor $parametersAcceptor, int $position): string
	{
		$parameters = $parametersAcceptor->getParameters();
		if (!array_key_exists($position, $parameters)) {
			return (string) ($position + 1);
		}

		return $parameters[$position]->getName();
	}

	/**
	 * Returns every possible string value of $type, or null if they're not all known.
	 * Arrays are unwrapped to their value types, mirroring how the replacement
	 * functions accept both a string and an array of strings.
	 *
	 * @return non-empty-list<string>|null
	 */
	private function getConstantStringValues(Type $type): ?array
	{
		if ($type->isString()->yes()) {
			$constantStrings = $type->getConstantStrings();
		} elseif ($type->isArray()->yes()) {
			$constantStrings = $type->getIterableValueType()->getConstantStrings();
		} else {
			return null;
		}

		if ($constantStrings === []) {
			return null;
		}

		$values = [];
		foreach ($constantStrings as $constantString) {
			$values[] = $constantString->getValue();
		}

		return array_values(array_unique($values));
	}

}
