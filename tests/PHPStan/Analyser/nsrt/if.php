<?php

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;
function () {
	if (foo()) {
		$ifVar = 1;
		$issetFoo = new Foo();
		$maybeDefinedButLaterCertainlyDefined = 1;
		if ($test) {
			$ifNestedVar = 1;
			$ifNotNestedVar = 1;
		} elseif (fooBar()) {
			$ifNotNestedVar = 2;
			$variableOnlyInEarlyTerminatingElse = 1;
			throw $e;
		} else {
			$ifNestedVar = 2;
		}
		$ifNotVar = 1;
	} elseif (bar()) {
		$ifVar = 2;
		$issetFoo = null;
		$ifNestedVar = 2;
		$ifNotNestedVar = 2;
		$ifNotVar = 2;
	} elseif ($ifNestedVar = 3) {
		$ifVar = 3;
		$ifNotNestedVar = 3;
	} else {
		$variableOnlyInEarlyTerminatingElse = 1;
		return;
	}

	if (foo()) {
		$maybeDefinedButLaterCertainlyDefined = 2;
	} else {
		$maybeDefinedButLaterCertainlyDefined = 3;
	}

	$exceptionFromTry = null;
	try {
		$inTry = 1;
		$fooObjectFromTryCatch = new InTryCatchFoo();
		$inTryNotInCatch = 1;
		$mixedVarFromTryCatch = 1;
		$nullableIntegerFromTryCatch = 1;
		$anotherNullableIntegerFromTryCatch = null;
		$someVariableThatWillGetOverrideInFinally = 1;
	} catch (\SomeConcreteException $e) {
		$inTry = 1;
		$fooObjectFromTryCatch = new InTryCatchFoo();
		$mixedVarFromTryCatch = 1.0;
		$nullableIntegerFromTryCatch = null;
		$anotherNullableIntegerFromTryCatch = 1;
	} catch (\Exception $e) {
		throw $e;
	} finally {
		$someVariableThatWillGetOverrideInFinally = 'foo';
		restore_error_handler();
	}

	$exceptionFromTryCatch = null;
	try {
		maybeThrows();
	} catch (\SomeConcreteException $exceptionFromTryCatch) {
		return;
	} catch (\AnotherException $exceptionFromTryCatch) {

	} catch (\YetAnotherException $exceptionFromTryCatch) {
		doFoo();
	}

	$lorem = 1;
	$arrOne[] = 'one';
	$arrTwo['test'] = 'two';
	$anotherArray['test'][] = 'another';
	doSomething($one, $callParameter = 3);
	$arrTwo[] = new Foo([
		$inArray = 1,
	]);
	$arrThree = null;
	$arrThree[] = 'three';
	preg_match('#.*#', 'foo', $matches);
	if ((bool) preg_match('#.*#', 'foo', $matches3)) {
		foo();
	} elseif (preg_match('#.*#', 'foo', $matches4)) {
		foo();
	}

	$trueOrFalseFromSwitch = true;
	switch (foo()) {
		case 1:
			$switchVar = 1;
			$noSwitchVar = 1;
			$trueOrFalseFromSwitch = false;
			break;
		case 'foo':
			$trueOrFalseFromSwitch = 1;
			return;
		case 2:
			$switchVar = 2;
			break;
		case 3:
			$anotherNoSwitchVar = 1;
		case 4:
		default:
			$switchVar = 3;
			if (doFoo()) {
				$switchVar = 4;
				break;
			}
	}

	$trueOrFalseInSwitchWithDefault = false;
	$nullableTrueOrFalse = null;
	switch ('foo') {
		case 'foo':
			$trueOrFalseInSwitchWithDefault = true;
			$nullableTrueOrFalse = true;
			continue;
		case 'bar';
			$nullableTrueOrFalse = false;
			break;
		default:
			break;
	}

	$trueOrFalseInSwitchInAllCases = false;
	switch ('foo') {
		case 'foo':
			$trueOrFalseInSwitchInAllCases = true;
			break;
		case 'bar';
			$trueOrFalseInSwitchInAllCases = true;
			break;
	}
	$trueOrFalseInSwitchInAllCasesWithDefault = false;
	switch ('foo') {
		case 'foo':
			$trueOrFalseInSwitchInAllCasesWithDefault = true;
			break;
		case 'bar';
			$trueOrFalseInSwitchInAllCasesWithDefault = true;
			break;
		default:
			break;
	}
	$trueOrFalseInSwitchInAllCasesWithDefaultCase = false;
	switch ('foo') {
		case 'foo':
			$trueOrFalseInSwitchInAllCasesWithDefaultCase = true;
			break;
		case 'bar';
			$trueOrFalseInSwitchInAllCasesWithDefaultCase = true;
			break;
		default:
			$trueOrFalseInSwitchInAllCasesWithDefaultCase = true;
			break;
	}

	switch ('foo') {
		case 'foo':
			$variableDefinedInSwitchWithOtherCasesWithEarlyTermination = true;
			break;
		case 'bar':
			throw new \Exception();
		default:
			throw new \Exception();
	}

	switch ('foo') {
		case 'foo':
			throw new \Exception();
		case 'bar':
			$anotherVariableDefinedInSwitchWithOtherCasesWithEarlyTermination = true;
			break;
		default:
			throw new \Exception();
	}

	switch ('foo') {
		case 'foo':
			$variableDefinedOnlyInEarlyTerminatingSwitchCases = true;
			throw new \Exception();
		case 'bar':
			$variableDefinedOnlyInEarlyTerminatingSwitchCases = true;
			return;
		case 'baz':
			break;
		default:
			$variableDefinedOnlyInEarlyTerminatingSwitchCases = true;
			return;
	}

	switch ('foo') {
		case 'a':
			$variableDefinedInSwitchWithoutEarlyTermination = true;
		case 'b':
			$variableDefinedInSwitchWithoutEarlyTermination = false;
	}

	switch ('foo') {
		case 'a':
			$anotherVariableDefinedInSwitchWithoutEarlyTermination = true;
			break;
		case 'b':
			$anotherVariableDefinedInSwitchWithoutEarlyTermination = false;
	}

	switch (doFoo()) {
		case 1:
		case 2:
		case 3:
			$alwaysDefinedFromSwitch = 1;
			break;

		default:
			$alwaysDefinedFromSwitch = null;
	}

	$nullOverwrittenInSwitchToOne = null;
	switch (doFoo()) {
		case 1:
			if (doFoo()) {
				throw new \Exception();
			}
			$nullOverwrittenInSwitchToOne = 1;
			break;
		default:
			throw new \Exception();
	}

	switch (doFoo()) {
		case 1:
			if (rand(0, 1)) {
				$variableFromSwitchShouldBeBool = true;
				break;
			}

		default:
			$variableFromSwitchShouldBeBool = false;
	}

	do {
		$doWhileVar = 1;
	} while (something());

	$integerOrNullFromFor = null;
	for ($previousI = 0, $previousJ = 0; $previousI < 1; $previousI++) {
		$integerOrNullFromFor = 1;
		$nonexistentVariableOutsideFor = 1;
	}

	$integerOrNullFromWhile = null;
	while (($frame = $that->getReader()->consumeFrame($that->getReadBuffer())) === null) {
		$integerOrNullFromWhile = 1;
		$nonexistentVariableOutsideWhile = 1;
	}

	/** @var array $someArray */
	$someArray = doFoo();
	$integerOrNullFromForeach = null;
	foreach ($someArray as $someValue) {
		$integerOrNullFromForeach = 1;
		$nonexistentVariableOutsideForeach = null;
	}

	$nullableIntegers = [1, 2, 3];
	$nullableIntegers[] = null;

	$union = [1, 2, 3];
	$union[] = 'foo';

	$$lorem = 'ipsum';

	$trueOrFalse = true;
	$falseOrTrue = false;
	$true = true;
	$false = false;
	if (doFoo()) {
		$trueOrFalse = false;
		$falseOrTrue = true;
		$true = true;
		$false = false;
	}

	/** @var string|null $notNullableString */
	$notNullableString = 'foo';
	if ($notNullableString === null) {
		return;
	}

	/** @var string|null $anotherNotNullableString */
	$anotherNotNullableString = 'foo';
	if ($anotherNotNullableString !== null) {
		$alsoNotNullableString = $anotherNotNullableString;
	} else {
		return;
	}

	/** @var Foo|null $notNullableObject */
	$notNullableObject = doFoo();
	if ($notNullableObject === null) {
		$notNullableObject = new Foo();
	}

	/** @var string|null $nullableString */
	$nullableString = 'foo';
	if ($nullableString !== null) {
		$whatever = $nullableString;
	}

	/** @var int|null $integerOrString */
	$integerOrString = 1;
	if ($integerOrString === null) {
		$integerOrString = 'str';
	}

	/** @var int|null $stillNullableInteger */
	$stillNullableInteger = 1;
	if (is_int($stillNullableInteger)) {
		$stillNullableInteger = 2;
	}

	/** @var int|null $nullableIntegerAfterNeverCondition */
	$nullableIntegerAfterNeverCondition = 1;
	if ($nullableIntegerAfterNeverCondition === false) {
		$nullableIntegerAfterNeverCondition = 1;
	}

	$arrayOfIntegers = [1, 2, 3];

	$arrayAccessObject = new \ObjectWithArrayAccess\Foo();
	$arrayAccessObject[] = 1;
	$arrayAccessObject[] = 2;

	$width = 1;
	$scale = 2.0;
	$width *= $scale;

	/** @var mixed $mixed */
	$mixed = doFoo();
	if (is_bool($mixed)) {
		$mixed = 1;
	}

	if (rand(0, 1)) {
		/** @var mixed $issetBar */
		$issetBar = doFoo();
		/** @var mixed $issetBaz */
		$issetBaz = doFoo();
	}

	try {
		$inTryTwo = 1;
		maybeThrows();
	} catch (\Exception $e) {
		$exception = $e;
		if (something()) {
			bar();
		} elseif (foo() || $foo = exists() || preg_match('#.*#', $subject, $matches2)) {
			if (isset($issetFoo, $issetBar) && isset($issetBaz)) {
				$anotherF = 1;
				for ($i = 0; $i < 5; $i++, $f = $i, $anotherF = $i) {
					$arr = [
						[1, 2],
					];
					foreach ($arr as list($listOne, $listTwo)) {
						if (is_array($arrayOfIntegers)) {
							if ((bool) preg_match('~.*~', $attributes, $ternaryMatches)) {
							assertVariableCertainty(TrinaryLogic::createNo(), $nonexistentVariable);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $foo);
							assertType('bool', $foo);
							assertVariableCertainty(TrinaryLogic::createYes(), $lorem);
							assertType('1', $lorem);
							assertVariableCertainty(TrinaryLogic::createYes(), $callParameter);
							assertType('3', $callParameter);
							assertVariableCertainty(TrinaryLogic::createYes(), $arrOne);
							assertType('array{\'one\'}', $arrOne);
							assertVariableCertainty(TrinaryLogic::createYes(), $arrTwo);
							assertType('array{test: \'two\', 0: Foo}', $arrTwo);
							assertVariableCertainty(TrinaryLogic::createYes(), $arrThree);
							assertType('array{\'three\'}', $arrThree);
							assertVariableCertainty(TrinaryLogic::createYes(), $inArray);
							assertType('1', $inArray);
							assertVariableCertainty(TrinaryLogic::createYes(), $i);
							assertType('int<0, 4>', $i);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $f);
							assertType('int<1, max>', $f);
							assertVariableCertainty(TrinaryLogic::createYes(), $anotherF);
							assertType('int<1, max>', $anotherF);
							assertVariableCertainty(TrinaryLogic::createYes(), $matches);
							assertType('array{0?: string}', $matches);
							assertVariableCertainty(TrinaryLogic::createYes(), $anotherArray);
							assertType('array{test: array{\'another\'}}', $anotherArray);
							assertVariableCertainty(TrinaryLogic::createYes(), $ifVar);
							assertType('1|2|3', $ifVar);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $ifNotVar);
							assertType('1|2', $ifNotVar);
							assertVariableCertainty(TrinaryLogic::createYes(), $ifNestedVar);
							assertType('1|2|3', $ifNestedVar);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $ifNotNestedVar);
							assertType('1|2|3', $ifNotNestedVar);
							assertVariableCertainty(TrinaryLogic::createNo(), $variableOnlyInEarlyTerminatingElse);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $matches2);
							assertType('array{0?: string}', $matches2);
							assertVariableCertainty(TrinaryLogic::createYes(), $inTry);
							assertType('1', $inTry);
							assertVariableCertainty(TrinaryLogic::createYes(), $matches3);
							assertType('array{}|array{string}', $matches3);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $matches4);
							assertType('array{}|array{string}', $matches4);
							assertVariableCertainty(TrinaryLogic::createYes(), $issetFoo);
							assertType('Foo', $issetFoo);
							assertVariableCertainty(TrinaryLogic::createYes(), $issetBar);
							assertType('mixed~null', $issetBar);
							assertVariableCertainty(TrinaryLogic::createYes(), $issetBaz);
							assertType('mixed~null', $issetBaz);
							assertVariableCertainty(TrinaryLogic::createYes(), $doWhileVar);
							assertType('1', $doWhileVar);
							assertVariableCertainty(TrinaryLogic::createYes(), $switchVar);
							assertType('1|2|3|4', $switchVar);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $noSwitchVar);
							assertType('1', $noSwitchVar);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $anotherNoSwitchVar);
							assertType('1', $anotherNoSwitchVar);
							assertVariableCertainty(TrinaryLogic::createYes(), $inTryTwo);
							assertType('1', $inTryTwo);
							assertVariableCertainty(TrinaryLogic::createYes(), $ternaryMatches);
							assertType('array{string}', $ternaryMatches);
							assertVariableCertainty(TrinaryLogic::createYes(), $previousI);
							assertType('int<1, max>', $previousI);
							assertVariableCertainty(TrinaryLogic::createYes(), $previousJ);
							assertType('0', $previousJ);
							assertVariableCertainty(TrinaryLogic::createYes(), $frame);
							assertType('mixed~null', $frame);
							assertVariableCertainty(TrinaryLogic::createYes(), $listOne);
							assertType('1', $listOne);
							assertVariableCertainty(TrinaryLogic::createYes(), $listTwo);
							assertType('2', $listTwo);
							assertVariableCertainty(TrinaryLogic::createYes(), $e);
							assertType('Exception', $e);
							assertVariableCertainty(TrinaryLogic::createYes(), $exception);
							assertType('Exception', $exception);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $inTryNotInCatch);
							assertType('1', $inTryNotInCatch);
							assertVariableCertainty(TrinaryLogic::createYes(), $fooObjectFromTryCatch);
							assertType('InTryCatchFoo', $fooObjectFromTryCatch);
							assertVariableCertainty(TrinaryLogic::createYes(), $mixedVarFromTryCatch);
							assertType('1|1.0', $mixedVarFromTryCatch);
							assertVariableCertainty(TrinaryLogic::createYes(), $nullableIntegerFromTryCatch);
							assertType('1|null', $nullableIntegerFromTryCatch);
							assertVariableCertainty(TrinaryLogic::createYes(), $anotherNullableIntegerFromTryCatch);
							assertType('1|null', $anotherNullableIntegerFromTryCatch);
							assertVariableCertainty(TrinaryLogic::createYes(), $nullableIntegers);
							assertType('array{1, 2, 3, null}', $nullableIntegers);
							assertVariableCertainty(TrinaryLogic::createYes(), $union);
							assertType('array{1, 2, 3, \'foo\'}', $union);
							assertVariableCertainty(TrinaryLogic::createYes(), $trueOrFalse);
							assertType('bool', $trueOrFalse);
							assertVariableCertainty(TrinaryLogic::createYes(), $falseOrTrue);
							assertType('bool', $falseOrTrue);
							assertVariableCertainty(TrinaryLogic::createYes(), $true);
							assertType('true', $true);
							assertVariableCertainty(TrinaryLogic::createYes(), $false);
							assertType('false', $false);
							assertVariableCertainty(TrinaryLogic::createYes(), $trueOrFalseFromSwitch);
							assertType('bool', $trueOrFalseFromSwitch);
							assertVariableCertainty(TrinaryLogic::createYes(), $trueOrFalseInSwitchWithDefault);
							assertType('bool', $trueOrFalseInSwitchWithDefault);
							assertVariableCertainty(TrinaryLogic::createYes(), $trueOrFalseInSwitchInAllCases);
							assertType('bool', $trueOrFalseInSwitchInAllCases);
							assertVariableCertainty(TrinaryLogic::createYes(), $trueOrFalseInSwitchInAllCasesWithDefault);
							assertType('bool', $trueOrFalseInSwitchInAllCasesWithDefault);
							assertVariableCertainty(TrinaryLogic::createYes(), $trueOrFalseInSwitchInAllCasesWithDefaultCase);
							assertType('true', $trueOrFalseInSwitchInAllCasesWithDefaultCase);
							assertVariableCertainty(TrinaryLogic::createYes(), $variableDefinedInSwitchWithOtherCasesWithEarlyTermination);
							assertType('true', $variableDefinedInSwitchWithOtherCasesWithEarlyTermination);
							assertVariableCertainty(TrinaryLogic::createYes(), $anotherVariableDefinedInSwitchWithOtherCasesWithEarlyTermination);
							assertType('true', $anotherVariableDefinedInSwitchWithOtherCasesWithEarlyTermination);
							assertVariableCertainty(TrinaryLogic::createNo(), $variableDefinedOnlyInEarlyTerminatingSwitchCases);
							assertVariableCertainty(TrinaryLogic::createYes(), $nullableTrueOrFalse);
							assertType('bool|null', $nullableTrueOrFalse);
							assertVariableCertainty(TrinaryLogic::createYes(), $nonexistentVariableOutsideFor);
							assertType('1', $nonexistentVariableOutsideFor);
							assertVariableCertainty(TrinaryLogic::createYes(), $integerOrNullFromFor);
							assertType('1', $integerOrNullFromFor);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $nonexistentVariableOutsideWhile);
							assertType('1', $nonexistentVariableOutsideWhile);
							assertVariableCertainty(TrinaryLogic::createYes(), $integerOrNullFromWhile);
							assertType('1|null', $integerOrNullFromWhile);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $nonexistentVariableOutsideForeach);
							assertType('null', $nonexistentVariableOutsideForeach);
							assertVariableCertainty(TrinaryLogic::createYes(), $integerOrNullFromForeach);
							assertType('1|null', $integerOrNullFromForeach);
							assertVariableCertainty(TrinaryLogic::createYes(), $notNullableString);
							assertType('string', $notNullableString);
							assertVariableCertainty(TrinaryLogic::createYes(), $anotherNotNullableString);
							assertType('string', $anotherNotNullableString);
							assertVariableCertainty(TrinaryLogic::createYes(), $notNullableObject);
							assertType('Foo', $notNullableObject);
							assertVariableCertainty(TrinaryLogic::createYes(), $nullableString);
							assertType('string|null', $nullableString);
							assertVariableCertainty(TrinaryLogic::createYes(), $alsoNotNullableString);
							assertType('string', $alsoNotNullableString);
							assertVariableCertainty(TrinaryLogic::createYes(), $integerOrString);
							assertType('\'str\'|int', $integerOrString);
							assertVariableCertainty(TrinaryLogic::createYes(), $nullableIntegerAfterNeverCondition);
							assertType('int|null', $nullableIntegerAfterNeverCondition);
							assertVariableCertainty(TrinaryLogic::createYes(), $stillNullableInteger);
							assertType('2|null', $stillNullableInteger);
							assertVariableCertainty(TrinaryLogic::createYes(), $arrayOfIntegers);
							assertType('array{1, 2, 3}', $arrayOfIntegers);
							assertVariableCertainty(TrinaryLogic::createYes(), $arrayAccessObject);
							assertType('ObjectWithArrayAccess\Foo', $arrayAccessObject);
							assertVariableCertainty(TrinaryLogic::createYes(), $width);
							assertType('2.0', $width);
							assertVariableCertainty(TrinaryLogic::createYes(), $someVariableThatWillGetOverrideInFinally);
							assertType('\'foo\'', $someVariableThatWillGetOverrideInFinally);
							assertVariableCertainty(TrinaryLogic::createYes(), $maybeDefinedButLaterCertainlyDefined);
							assertType('2|3', $maybeDefinedButLaterCertainlyDefined);
							assertVariableCertainty(TrinaryLogic::createYes(), $mixed);
							assertType('mixed~bool', $mixed);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $variableDefinedInSwitchWithoutEarlyTermination);
							assertType('false', $variableDefinedInSwitchWithoutEarlyTermination);
							assertVariableCertainty(TrinaryLogic::createMaybe(), $anotherVariableDefinedInSwitchWithoutEarlyTermination);
							assertType('bool', $anotherVariableDefinedInSwitchWithoutEarlyTermination);
							assertVariableCertainty(TrinaryLogic::createYes(), $alwaysDefinedFromSwitch);
							assertType('1|null', $alwaysDefinedFromSwitch);
							assertVariableCertainty(TrinaryLogic::createYes(), $exceptionFromTryCatch);
							assertType('(AnotherException&Throwable)|(Throwable&YetAnotherException)|null', $exceptionFromTryCatch);
							assertVariableCertainty(TrinaryLogic::createYes(), $nullOverwrittenInSwitchToOne);
							assertType('1', $nullOverwrittenInSwitchToOne);
							assertVariableCertainty(TrinaryLogic::createYes(), $variableFromSwitchShouldBeBool);
							assertType('bool', $variableFromSwitchShouldBeBool);
							}
						}
					}
				}
			}
		}
	}
};
