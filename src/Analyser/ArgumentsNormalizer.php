<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node as PhpParserNode;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Scalar\String_;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use function array_is_list;
use function array_key_exists;
use function array_key_first;
use function array_keys;
use function array_values;
use function count;
use function is_string;
use function ksort;
use function max;
use function sprintf;

/**
 * @api
 */
final class ArgumentsNormalizer
{

	public const ORIGINAL_ARG_ATTRIBUTE = 'originalArg';

	/**
	 * @return array{ParametersAcceptor, FuncCall, TrinaryLogic}|null
	 */
	public static function reorderCallUserFuncArguments(
		FuncCall $callUserFuncCall,
		Scope $scope,
	): ?array
	{
		$args = $callUserFuncCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$passThruArgs = [];
		$callbackArg = null;
		foreach ($args as $i => $arg) {
			if ($callbackArg === null) {
				if ($arg->name === null && $i === 0) {
					$callbackArg = $arg;
					continue;
				}
				if ($arg->name !== null && $arg->name->toString() === 'callback') {
					$callbackArg = $arg;
					continue;
				}
			}

			$passThruArgs[] = $arg;
		}

		if ($callbackArg === null) {
			return null;
		}

		$calledOnType = $scope->getType($callbackArg->value);
		if (!$calledOnType->isCallable()->yes()) {
			return null;
		}

		$callableParametersAcceptors = $calledOnType->getCallableParametersAcceptors($scope);
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$passThruArgs,
			$callableParametersAcceptors,
			null,
		);

		$acceptsNamedArguments = TrinaryLogic::createYes();
		foreach ($callableParametersAcceptors as $callableParametersAcceptor) {
			$acceptsNamedArguments = $acceptsNamedArguments->and($callableParametersAcceptor->acceptsNamedArguments());
		}

		return [$parametersAcceptor, new FuncCall(
			$callbackArg->value,
			$passThruArgs,
			self::attributesWithoutPrintedForm($callUserFuncCall),
		), $acceptsNamedArguments];
	}

	/**
	 * @return array{ParametersAcceptor, FuncCall, TrinaryLogic}|null
	 */
	public static function reorderCallUserFuncArrayArguments(
		FuncCall $callUserFuncArrayCall,
		Scope $scope,
	): ?array
	{
		$args = $callUserFuncArrayCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$callbackArg = null;
		$argsArrayArg = null;
		foreach ($args as $i => $arg) {
			if ($callbackArg === null) {
				if ($arg->name === null && $i === 0) {
					$callbackArg = $arg;
					continue;
				}
				if ($arg->name !== null && $arg->name->toString() === 'callback') {
					$callbackArg = $arg;
					continue;
				}
			}

			if ($argsArrayArg !== null) {
				continue;
			}
			if ($arg->name === null && $i === 1) {
				$argsArrayArg = $arg;
				continue;
			}
			if ($arg->name === null || $arg->name->toString() !== 'args') {
				continue;
			}
			$argsArrayArg = $arg;
		}

		if ($callbackArg === null || $argsArrayArg === null) {
			return null;
		}

		if (!$argsArrayArg->value instanceof Array_) {
			return null;
		}

		$passThruArgs = [];
		foreach ($argsArrayArg->value->items as $item) {
			$key = null;
			if ($item->key instanceof String_) {
				$key = array_key_first([$item->key->value => null]);
				if ($key === '') {
					return null;
				}
			} elseif ($item->key !== null && !$item->key instanceof Int_) {
				// Dynamic key, we cannot be sure.
				return null;
			}

			$passThruArgs[] = new Arg(
				$item->value,
				$item->byRef,
				$item->unpack,
				self::attributesWithoutPrintedForm($item),
				is_string($key) ? new Identifier($key) : null,
			);
		}

		$calledOnType = $scope->getType($callbackArg->value);
		if (!$calledOnType->isCallable()->yes()) {
			return null;
		}

		$callableParametersAcceptors = $calledOnType->getCallableParametersAcceptors($scope);
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$passThruArgs,
			$callableParametersAcceptors,
			null,
		);

		$acceptsNamedArguments = TrinaryLogic::createYes();
		foreach ($callableParametersAcceptors as $callableParametersAcceptor) {
			$acceptsNamedArguments = $acceptsNamedArguments->and($callableParametersAcceptor->acceptsNamedArguments());
		}

		return [$parametersAcceptor, new FuncCall(
			$callbackArg->value,
			$passThruArgs,
			self::attributesWithoutPrintedForm($callUserFuncArrayCall),
		), $acceptsNamedArguments];
	}

	public static function reorderFuncArguments(
		ParametersAcceptor $parametersAcceptor,
		FuncCall $functionCall,
	): ?FuncCall
	{
		$args = $functionCall->getArgs();
		$reorderedArgs = self::reorderArgs($parametersAcceptor, $args);

		if ($reorderedArgs === null) {
			return null;
		}

		// return identical object if not reordered, as TypeSpecifier relies on object identity
		if ($reorderedArgs === $args) {
			return $functionCall;
		}

		return new FuncCall(
			$functionCall->name,
			$reorderedArgs,
			self::attributesWithoutPrintedForm($functionCall),
		);
	}

	public static function reorderMethodArguments(
		ParametersAcceptor $parametersAcceptor,
		MethodCall $methodCall,
	): ?MethodCall
	{
		$args = $methodCall->getArgs();
		$reorderedArgs = self::reorderArgs($parametersAcceptor, $args);

		if ($reorderedArgs === null) {
			return null;
		}

		// return identical object if not reordered, as TypeSpecifier relies on object identity
		if ($reorderedArgs === $args) {
			return $methodCall;
		}

		return new MethodCall(
			$methodCall->var,
			$methodCall->name,
			$reorderedArgs,
			self::attributesWithoutPrintedForm($methodCall),
		);
	}

	public static function reorderStaticCallArguments(
		ParametersAcceptor $parametersAcceptor,
		StaticCall $staticCall,
	): ?StaticCall
	{
		$args = $staticCall->getArgs();
		$reorderedArgs = self::reorderArgs($parametersAcceptor, $args);

		if ($reorderedArgs === null) {
			return null;
		}

		// return identical object if not reordered, as TypeSpecifier relies on object identity
		if ($reorderedArgs === $args) {
			return $staticCall;
		}

		return new StaticCall(
			$staticCall->class,
			$staticCall->name,
			$reorderedArgs,
			self::attributesWithoutPrintedForm($staticCall),
		);
	}

	public static function reorderNewArguments(
		ParametersAcceptor $parametersAcceptor,
		New_ $new,
	): ?New_
	{
		$args = $new->getArgs();
		$reorderedArgs = self::reorderArgs($parametersAcceptor, $args);

		if ($reorderedArgs === null) {
			return null;
		}

		// return identical object if not reordered, as TypeSpecifier relies on object identity
		if ($reorderedArgs === $args) {
			return $new;
		}

		return new New_(
			$new->class,
			$reorderedArgs,
			self::attributesWithoutPrintedForm($new),
		);
	}

	/**
	 * @param Arg[] $callArgs
	 * @return ?list<Arg>
	 */
	public static function reorderArgs(ParametersAcceptor $parametersAcceptor, array $callArgs): ?array
	{
		if (count($callArgs) === 0) {
			return [];
		}

		$hasNamedArgs = false;
		foreach ($callArgs as $arg) {
			if ($arg->name !== null) {
				$hasNamedArgs = true;
				break;
			}
		}
		if (!$hasNamedArgs) {
			return array_values($callArgs);
		}

		$hasVariadic = false;
		$argumentPositions = [];
		$signatureParameters = $parametersAcceptor->getParameters();
		foreach ($signatureParameters as $i => $parameter) {
			if ($hasVariadic) {
				// variadic parameter must be last
				return null;
			}

			$hasVariadic = $parameter->isVariadic();
			$argumentPositions[$parameter->getName()] = $i;
		}

		$reorderedArgs = [];
		$additionalNamedArgs = [];
		$appendArgs = [];
		foreach ($callArgs as $i => $arg) {
			if ($arg->name === null) {
				// add regular args as is

				$attributes = $arg->getAttributes();
				$attributes[self::ORIGINAL_ARG_ATTRIBUTE] = $arg;
				$reorderedArgs[$i] = new Arg(
					$arg->value,
					$arg->byRef,
					$arg->unpack,
					$attributes,
					null,
				);
			} elseif (array_key_exists($arg->name->toString(), $argumentPositions)) {
				$argName = $arg->name->toString();
				// order named args into the position the signature expects them
				$attributes = $arg->getAttributes();
				$attributes[self::ORIGINAL_ARG_ATTRIBUTE] = $arg;
				if (array_key_exists($argumentPositions[$argName], $reorderedArgs)) {
					continue;
				}
				$reorderedArgs[$argumentPositions[$argName]] = new Arg(
					$arg->value,
					$arg->byRef,
					$arg->unpack,
					$attributes,
					null,
				);
			} else {
				if (!$hasVariadic) {
					$attributes = $arg->getAttributes();
					$attributes[self::ORIGINAL_ARG_ATTRIBUTE] = $arg;
					$appendArgs[] = new Arg(
						$arg->value,
						$arg->byRef,
						$arg->unpack,
						$attributes,
						null,
					);
					continue;
				}

				$attributes = $arg->getAttributes();
				$attributes[self::ORIGINAL_ARG_ATTRIBUTE] = $arg;
				$additionalNamedArgs[] = new Arg(
					$arg->value,
					$arg->byRef,
					$arg->unpack,
					$attributes,
					null,
				);
			}
		}

		// replace variadic parameter with additional named args, except if it is already set
		$additionalNamedArgsOffset = count($argumentPositions) - 1;
		if (array_key_exists($additionalNamedArgsOffset, $reorderedArgs)) {
			$additionalNamedArgsOffset++;
		}

		foreach ($additionalNamedArgs as $i => $additionalNamedArg) {
			$reorderedArgs[$additionalNamedArgsOffset + $i] = $additionalNamedArg;
		}

		if (count($reorderedArgs) === 0) {
			foreach ($appendArgs as $arg) {
				$reorderedArgs[] = $arg;
			}
			return $reorderedArgs;
		}

		// fill up all holes with default values until the last given argument
		for ($j = 0; $j < max(array_keys($reorderedArgs)); $j++) {
			if (array_key_exists($j, $reorderedArgs)) {
				continue;
			}
			if (!array_key_exists($j, $signatureParameters)) {
				return null;
			}

			$parameter = $signatureParameters[$j];

			// we can only fill up optional parameters with default values
			if (!$parameter->isOptional()) {
				return null;
			}

			$defaultValue = $parameter->getDefaultValue();
			if ($defaultValue === null) {
				if (!$parameter->isVariadic()) {
					throw new ShouldNotHappenException(sprintf('An optional parameter $%s must have a default value', $parameter->getName()));
				}
				$defaultValue = new ConstantArrayType([], []);
			}

			$reorderedArgs[$j] = new Arg(
				new TypeExpr($defaultValue),
			);
		}

		ksort($reorderedArgs);

		foreach ($appendArgs as $arg) {
			$reorderedArgs[] = $arg;
		}

		if (!array_is_list($reorderedArgs)) {
			$reorderedArgs = array_values($reorderedArgs);
		}

		return $reorderedArgs;
	}

	/**
	 * The printed form of an expression is derived from its own arguments, so
	 * it must not travel to a node whose arguments were just reordered — the
	 * normalized call would report the original's expression key, named
	 * arguments and all.
	 *
	 * @return array<string, mixed>
	 */
	private static function attributesWithoutPrintedForm(PhpParserNode $node): array
	{
		$attributes = $node->getAttributes();
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);

		return $attributes;
	}

}
