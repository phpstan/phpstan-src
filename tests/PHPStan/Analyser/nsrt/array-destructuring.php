<?php

use function PHPStan\Testing\assertType;
function (\stdClass $obj) {
    /** @var mixed $array */
    $array = getMixed();
    [$a, $b, [$c]] = $array;
    list($aList, $bList, list($cList)) = $array;

    $constantArray = [1, 'foo', [true]];
    [$int, $string, [$bool, $nestedNever], $never] = $constantArray;
    list($intList, $stringList, list($boolList, $nestedNeverList), $neverList) = $constantArray;

    $unionArray = $foo ? [1, 2, 3] : [4, 'bar'];
    [$u1, $u2, $u3] = $unionArray;

    foreach ([[1, [false]]] as [$foreachInt, [$foreachBool, $foreachNestedNever], $foreachNever]) {

    }

    foreach ([[1, [false]]] as list($foreachIntList, list($foreachBoolList, $foreachNestedNeverList), $foreachNeverList)) {

    }

    foreach ([$unionArray] as [$foreachU1, $foreachU2, $foreachU3]) {

    }

    /** @var string[] $stringArray */
    $stringArray = getStringArray();
    [$firstStringArray, $secondStringArray, [$thirdStringArray], $fourthStringArray] = $stringArray;
    list($firstStringArrayList, $secondStringArrayList, list($thirdStringArrayList), $fourthStringArrayList) = $stringArray;

    foreach ($stringArray as [$firstStringArrayForeach, $secondStringArrayForeach, [$thirdStringArrayForeach], $fourthStringArrayForeach]) {

    }

    foreach ($stringArray as list($firstStringArrayForeachList, $secondStringArrayForeachList, list($thirdStringArrayForeachList), $fourthStringArrayForeachList)) {

    }

    /** @var int $dayInt */
    $dayInt = getInt($dayInt);
    $dateArray = ['d' => $dayInt];
    [$dateArray['Y'], $dateArray['m']] = explode('-', '2018-12-19');

    /** @var int $firstIntElement */
    $firstIntElement = getInt();
    /** @var int $secondIntElement */
    $secondIntElement = getInt();
    $intArrayForRewritingFirstElement = [$firstIntElement, $secondIntElement];
    [$intArrayForRewritingFirstElement[0]] = explode('*', '');

    [$newArray['newKey']] = [new stdClass(), new stdClass()];

    [$obj[0]] = ['error', 'error-error'];

    $constantAssocArray = [1, 'foo', 'key' => true, 'value' => '123'];
    ['key' => $assocKey, 0 => $assocOne, 1 => $assocFoo, 'non-existent' => $assocNonExistent] = $constantAssocArray;

    $fooKey = 'key';
    /** @var string $stringKey */
    $stringKey = getString();
    /** @var mixed $mixedKey */
    $mixedKey = getMixed();
    [$fooKey => $dynamicAssocKey, $stringKey => $dynamicAssocStrings, $mixedKey => $dynamicAssocMixed] = $constantAssocArray;

    foreach ([$constantAssocArray] as [$fooKey => $dynamicAssocKeyForeach, $stringKey => $dynamicAssocStringsForeach, $mixedKey => $dynamicAssocMixedForeach]) {

    }

    /** @var iterable<array<string>> $iterableOverStringArrays */
    $iterableOverStringArrays = doFoo();
    foreach ($iterableOverStringArrays as [$stringFromIterable]) {

    }

    /** @var string $stringWithVarAnnotation  */
    [$stringWithVarAnnotation] = doFoo();

    /** @var string $stringWithVarAnnotationInForeach */
    foreach (doFoo() as [$stringWithVarAnnotationInForeach]) {

    }

    assertType('mixed', $a);
    assertType('mixed', $b);
    assertType('mixed', $c);
    assertType('mixed', $aList);
    assertType('mixed', $bList);
    assertType('mixed', $cList);
    assertType('1', $int);
    assertType('\'foo\'', $string);
    assertType('true', $bool);
    assertType('*ERROR*', $never);
    assertType('*ERROR*', $nestedNever);
    assertType('1', $intList);
    assertType('\'foo\'', $stringList);
    assertType('true', $boolList);
    assertType('*ERROR*', $neverList);
    assertType('*ERROR*', $nestedNeverList);
    assertType('1', $foreachInt);
    assertType('false', $foreachBool);
    assertType('*ERROR*', $foreachNever);
    assertType('*ERROR*', $foreachNestedNever);
    assertType('1', $foreachIntList);
    assertType('false', $foreachBoolList);
    assertType('*ERROR*', $foreachNeverList);
    assertType('*ERROR*', $foreachNestedNeverList);
    assertType('1|4', $u1);
    assertType('2|\'bar\'', $u2);
    assertType('3|null', $u3);
    assertType('1|4', $foreachU1);
    assertType('2|\'bar\'', $foreachU2);
    assertType('3|null', $foreachU3);
    assertType('string|null', $firstStringArray);
    assertType('string|null', $secondStringArray);
    assertType('non-empty-string', $thirdStringArray);
    assertType('string|null', $fourthStringArray);
    assertType('string|null', $firstStringArrayList);
    assertType('string|null', $secondStringArrayList);
    assertType('non-empty-string', $thirdStringArrayList);
    assertType('string|null', $fourthStringArrayList);
    assertType('non-empty-string', $firstStringArrayForeach);
    assertType('non-empty-string', $secondStringArrayForeach);
    assertType('non-empty-string', $thirdStringArrayForeach);
    assertType('non-empty-string', $fourthStringArrayForeach);
    assertType('non-empty-string', $firstStringArrayForeachList);
    assertType('non-empty-string', $secondStringArrayForeachList);
    assertType('non-empty-string', $thirdStringArrayForeachList);
    assertType('non-empty-string', $fourthStringArrayForeachList);
    assertType('lowercase-string&uppercase-string', $dateArray['Y']);
    assertType('(lowercase-string&uppercase-string)|null', $dateArray['m']);
    assertType('int', $dateArray['d']);
    assertType('lowercase-string&uppercase-string', $intArrayForRewritingFirstElement[0]);
    assertType('int', $intArrayForRewritingFirstElement[1]);
    assertType('ArrayAccess&stdClass', $obj);
    assertType('stdClass', $newArray['newKey']);
    assertType('true', $assocKey);
    assertType('\'foo\'', $assocFoo);
    assertType('1', $assocOne);
    assertType('*ERROR*', $assocNonExistent);
    assertType('true', $dynamicAssocKey);
    assertType('\'123\'|true|null', $dynamicAssocStrings);
    assertType('1|\'123\'|\'foo\'|true|null', $dynamicAssocMixed);
    assertType('true', $dynamicAssocKeyForeach);
    assertType('\'123\'|true|null', $dynamicAssocStringsForeach);
    assertType('1|\'123\'|\'foo\'|true|null', $dynamicAssocMixedForeach);
    assertType('string|null', $stringFromIterable);
    assertType('string', $stringWithVarAnnotation);
    assertType('string', $stringWithVarAnnotationInForeach);
};
