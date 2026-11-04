/*
 * PHPStanTurbo\OperatorTypeHelper — native implementation of
 * PHPStan\Type\OperatorTypeHelper: the . operator (Type::stringConcatenate())
 * and the array + operator (Type::arrayUnion()) OperatorTypeTrait delegates
 * to (pt_type_trait_operator() in TypeTraits.cpp calls
 * pt_operator_type_helper_binary() directly). The arithmetic and bitwise
 * operators are computed on ValueSet and NumberSet.
 *
 * A final class of static methods: every method mirrors its twin, the
 * private ones are registered too so the method sets stay identical. Only
 * concat() reads the PhpVersion (trailing whitespace in numeric strings).
 */

#include "support.h"
#include "generated/OperatorTypeHelper.h"

#include "zv.h"
#include "IetrSupport.h"
#include "reg.h"

#include <cmath>
#include <limits>
#include <vector>

zend_class_entry *pt_ce_operator_type_helper = nullptr;

namespace sigs = ptdecl::OperatorTypeHelper::sig;

using namespace phpstanturbo::ietr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\OperatorTypeHelper; UNDEF = pending exception. */
class OperatorTypeHelper
{
public:

	/* {{{ concat() */

	/* Mirrors concat(). */
	static zv::Val concat(zval *left, zval *right, zval *phpVersion)
	{
		IETR_VAL(leftStringType, callOn(left, mToString));
		IETR_VAL(rightStringType, callOn(right, mToString));
		{
			IETR_VAL(union_, union2(leftStringType.raw(), rightStringType.raw()));
			if (isA(union_.raw(), pt_ce_error_type)) return newErrorType();
		}

		int leftEmpty = isEmptyConstantString(leftStringType.raw());
		if (UNEXPECTED(leftEmpty < 0)) return zv::Val();
		if (leftEmpty) return rightStringType;

		int rightEmpty = isEmptyConstantString(rightStringType.raw());
		if (UNEXPECTED(rightEmpty < 0)) return zv::Val();
		if (rightEmpty) return leftStringType;

		if (isA(leftStringType.raw(), pt_ce_constant_string_type) && isA(rightStringType.raw(), pt_ce_constant_string_type)) {
			return callOn(leftStringType.raw(), mAppend, 1, rightStringType.raw());
		}

		IETR_VAL(leftConstantStrings, callOn(leftStringType.raw(), mGetConstantStrings));
		IETR_VAL(rightConstantStrings, callOn(rightStringType.raw(), mGetConstantStrings));
		if (UNEXPECTED(!requireArray(leftConstantStrings.raw()) || !requireArray(rightConstantStrings.raw()))) return zv::Val();
		zend_long combinedConstantStringsCount = (zend_long) zend_hash_num_elements(Z_ARRVAL_P(leftConstantStrings.raw())) * (zend_long) zend_hash_num_elements(Z_ARRVAL_P(rightConstantStrings.raw()));

		// we limit the number of union-types for performance reasons
		if (combinedConstantStringsCount > 0 && combinedConstantStringsCount <= PT_IETR_CALCULATE_SCALARS_LIMIT) {
			zv::Arr strings = zv::Arr::create((uint32_t) combinedConstantStringsCount);
			for (zv::ArrayEntry leftEntry : zv::ArrRef(leftConstantStrings.raw())) {
				zval *leftConstantString = leftEntry.value().raw();
				int leftStringEmpty = constantStringValueIsEmpty(leftConstantString);
				if (UNEXPECTED(leftStringEmpty < 0)) return zv::Val();
				if (leftStringEmpty) {
					// $strings = array_merge($strings, $rightConstantStrings)
					for (zv::ArrayEntry rightEntry : zv::ArrRef(rightConstantStrings.raw())) {
						if (rightEntry.hasStringKey()) {
							strings.set(rightEntry.stringKey(), zv::Val::copyOf(rightEntry.value()));
						} else {
							strings.push(rightEntry.value());
						}
					}
					continue;
				}

				for (zv::ArrayEntry rightEntry : zv::ArrRef(rightConstantStrings.raw())) {
					zval *rightConstantString = rightEntry.value().raw();
					int rightStringEmpty = constantStringValueIsEmpty(rightConstantString);
					if (UNEXPECTED(rightStringEmpty < 0)) return zv::Val();
					if (rightStringEmpty) {
						strings.push(zv::Ref(leftConstantString));
						continue;
					}

					IETR_VAL(appended, callOn(leftConstantString, mAppend, 1, rightConstantString));
					strings.push(std::move(appended));
				}
			}

			if (zend_hash_num_elements(strings.table()) > 0) {
				return unionOf(strings);
			}
		}

		zv::Arr accessoryTypes = zv::Arr::create(6);
		{
			IETR_TRI(leftNonEmpty, triOn(leftStringType.raw(), mIsNonEmptyString));
			IETR_TRI(rightNonEmpty, triOn(rightStringType.raw(), mIsNonEmptyString));
			if (pt_trinary_and(leftNonEmpty, rightNonEmpty) == PT_TRI_YES) {
				zval accessory;
				if (UNEXPECTED(!pt_accessory_non_falsy_string_type_new(&accessory))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(accessory));
			} else {
				IETR_TRI(leftNonFalsy, triOn(leftStringType.raw(), mIsNonFalsyString));
				IETR_TRI(rightNonFalsy, triOn(rightStringType.raw(), mIsNonFalsyString));
				if (pt_trinary_or(leftNonFalsy, rightNonFalsy) == PT_TRI_YES) {
					zval accessory;
					if (UNEXPECTED(!pt_accessory_non_falsy_string_type_new(&accessory))) return zv::Val();
					accessoryTypes.push(zv::Val::adopt(accessory));
				} else {
					IETR_TRI(leftNonEmpty2, triOn(leftStringType.raw(), mIsNonEmptyString));
					IETR_TRI(rightNonEmpty2, triOn(rightStringType.raw(), mIsNonEmptyString));
					if (pt_trinary_or(leftNonEmpty2, rightNonEmpty2) == PT_TRI_YES) {
						zval accessory;
						if (UNEXPECTED(!pt_accessory_non_empty_string_type_new(&accessory))) return zv::Val();
						accessoryTypes.push(zv::Val::adopt(accessory));
					}
				}
			}
		}

		{
			IETR_TRI(leftLiteral, triOn(leftStringType.raw(), mIsLiteralString));
			IETR_TRI(rightLiteral, triOn(rightStringType.raw(), mIsLiteralString));
			if (pt_trinary_and(leftLiteral, rightLiteral) == PT_TRI_YES) {
				zval accessory;
				if (UNEXPECTED(!pt_accessory_literal_string_type_new(&accessory))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(accessory));
			}
		}

		{
			IETR_TRI(leftLowercase, triOn(leftStringType.raw(), mIsLowercaseString));
			IETR_TRI(rightLowercase, triOn(rightStringType.raw(), mIsLowercaseString));
			if (pt_trinary_and(leftLowercase, rightLowercase) == PT_TRI_YES) {
				zval accessory;
				if (UNEXPECTED(!pt_accessory_lowercase_string_type_new(&accessory))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(accessory));
			}
		}

		{
			IETR_TRI(leftUppercase, triOn(leftStringType.raw(), mIsUppercaseString));
			IETR_TRI(rightUppercase, triOn(rightStringType.raw(), mIsUppercaseString));
			if (pt_trinary_and(leftUppercase, rightUppercase) == PT_TRI_YES) {
				zval accessory;
				if (UNEXPECTED(!pt_accessory_uppercase_string_type_new(&accessory))) return zv::Val();
				accessoryTypes.push(zv::Val::adopt(accessory));
			}
		}

		{
			zval emptyString;
			ZVAL_EMPTY_STRING(&emptyString);
			IETR_VAL(emptyStringType, newConstantStringType(Z_STR(emptyString)));
			IETR_VAL(leftNumericStringNonEmpty, pt_type_combinator_remove(leftStringType.raw(), emptyStringType.raw()));
			IETR_TRI(leftNumeric, triOn(leftNumericStringNonEmpty.raw(), mIsNumericString));
			bool numericResult = leftNumeric == PT_TRI_YES;
			if (numericResult) {
				/* '' . '' is not numeric */
				IETR_TRI(leftNonEmpty, triOn(leftStringType.raw(), mIsNonEmptyString));
				IETR_TRI(rightNonEmpty, triOn(rightStringType.raw(), mIsNonEmptyString));
				numericResult = pt_trinary_or(leftNonEmpty, rightNonEmpty) == PT_TRI_YES;
			}
			if (numericResult) {
				/* '1 ' is numeric on PHP 8, '1 5' is not */
				int endsInWhitespace = canEndInWhitespace(left, leftStringType.raw(), phpVersion);
				if (UNEXPECTED(endsInWhitespace < 0)) return zv::Val();
				numericResult = endsInWhitespace == 0;
			}
			if (numericResult) {
				IETR_TRI(leftIsInteger, triOn(left, mIsInteger));
				bool integerValidation = leftIsInteger == PT_TRI_YES;

				bool allRightConstantsZeroOrMore = false;
				for (zv::ArrayEntry rightEntry : zv::ArrRef(rightConstantStrings.raw())) {
					zval *rightConstantString = rightEntry.value().raw();
					IETR_VAL(value, constantStringValue(rightConstantString));
					if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_STRING)) {
						// ConstantStringType::getValue(): string
						pt_throw_should_not_happen();
						return zv::Val();
					}
					zend_string *string = Z_STR_P(value.raw());
					if (ZSTR_LEN(string) == 0) {
						continue;
					}

					if (
						is_numeric_string(ZSTR_VAL(string), ZSTR_LEN(string), NULL, NULL, false) == 0
						|| !(integerValidation ? isNumberAfterInteger(string) : isDecimalDigits(string))
					) {
						allRightConstantsZeroOrMore = false;
						break;
					}

					allRightConstantsZeroOrMore = true;
				}

				bool nonNegativeRight = allRightConstantsZeroOrMore;
				if (!nonNegativeRight) {
					IETR_VAL(zeroOrMoreInteger, fromInterval(NullableLong::of(0), NullableLong::null()));
					IETR_TRI(zeroOrMoreRight, triOn(zeroOrMoreInteger.raw(), mIsSuperTypeOf, 1, right));
					nonNegativeRight = zeroOrMoreRight == PT_TRI_YES;
				}
				if (nonNegativeRight) {
					zval accessory;
					if (UNEXPECTED(!pt_accessory_numeric_string_type_new(&accessory))) return zv::Val();
					accessoryTypes.push(zv::Val::adopt(accessory));
				}
			}
		}

		if (zend_hash_num_elements(accessoryTypes.table()) > 0) {
			IETR_VAL(stringType, newStringType());
			accessoryTypes.push(std::move(stringType));
			return newIntersectionType(accessoryTypes);
		}

		return newStringType();
	}

	/* Mirrors canEndInWhitespace(): 1 / 0, -1 = pending exception */
	static int canEndInWhitespace(zval *left, zval *leftStringType, zval *phpVersion)
	{
		zend_long leftIsInteger = triOn(left, mIsInteger);
		if (UNEXPECTED(leftIsInteger < 0)) return -1;
		if (leftIsInteger == PT_TRI_YES) return 0;
		zend_long leftIsFloat = triOn(left, mIsFloat);
		if (UNEXPECTED(leftIsFloat < 0)) return -1;
		if (leftIsFloat == PT_TRI_YES) return 0;
		zend_long leftIsDecimal = triOn(leftStringType, mIsDecimalIntegerString);
		if (UNEXPECTED(leftIsDecimal < 0)) return -1;
		if (leftIsDecimal == PT_TRI_YES) return 0;

		zv::Val constantStrings = callOn(leftStringType, mGetConstantStrings);
		if (UNEXPECTED(constantStrings.isUndef()) || UNEXPECTED(!requireArray(constantStrings.raw()))) return -1;
		if (zend_hash_num_elements(Z_ARRVAL_P(constantStrings.raw())) > 0) {
			for (zv::ArrayEntry entry : zv::ArrRef(constantStrings.raw())) {
				zv::Val value = constantStringValue(entry.value().raw());
				if (UNEXPECTED(value.isUndef())) return -1;
				if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_STRING)) {
					pt_throw_should_not_happen();
					return -1;
				}
				/* Strings::match($value, '#\s\z#'): PCRE's \s */
				size_t length = Z_STRLEN_P(value.raw());
				if (length > 0) {
					char last = Z_STRVAL_P(value.raw())[length - 1];
					if (last == ' ' || last == '\t' || last == '\n' || last == '\v' || last == '\f' || last == '\r') return 1;
				}
			}
			return 0;
		}

		bool allows;
		if (UNEXPECTED(!pt_php_version_answer(phpVersion, PT_PHP_VERSION_ALLOWS_TRAILING_WHITESPACE_IN_NUMERIC_STRINGS, allows))) return -1;
		return allows ? 1 : 0;
	}

	/* }}} */

	/* {{{ arrayUnion() */

	/* Mirrors arrayUnion(). */
	static zv::Val arrayUnion(zval *leftType, zval *rightType)
	{
		IETR_VAL(result, tryArrayUnion(leftType, rightType));
		if (Z_TYPE_P(result.raw()) != IS_NULL) return result;
		return newErrorType();
	}

	/* Mirrors tryArrayUnion(): a Type, or PHP null */
	static zv::Val tryArrayUnion(zval *leftType, zval *rightType)
	{
		IETR_VAL(leftConstantArrays, callOn(leftType, mGetConstantArrays));
		IETR_VAL(rightConstantArrays, callOn(rightType, mGetConstantArrays));
		if (UNEXPECTED(!requireArray(leftConstantArrays.raw()) || !requireArray(rightConstantArrays.raw()))) return zv::Val();

		zend_long leftCount = zend_hash_num_elements(Z_ARRVAL_P(leftConstantArrays.raw()));
		zend_long rightCount = zend_hash_num_elements(Z_ARRVAL_P(rightConstantArrays.raw()));
		if (leftCount > 0 && rightCount > 0
			&& (leftCount + rightCount < PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT)) {
			zv::Arr resultTypes = zv::Arr::create((uint32_t) (leftCount * rightCount));
			for (zv::ArrayEntry rightEntry : zv::ArrRef(rightConstantArrays.raw())) {
				zval *rightConstantArray = rightEntry.value().raw();
				for (zv::ArrayEntry leftEntry : zv::ArrRef(leftConstantArrays.raw())) {
					zval *leftConstantArray = leftEntry.value().raw();
					IETR_VAL(newArrayBuilder, pt_constant_array_type_builder_create_from_constant_array(rightConstantArray));
					IETR_VAL(leftKeyTypes, callOn(leftConstantArray, mGetKeyTypes));
					if (UNEXPECTED(!requireArray(leftKeyTypes.raw()))) return zv::Val();
					for (zv::ArrayEntry keyEntry : zv::ArrRef(leftKeyTypes.raw())) {
						zval *leftKeyType = keyEntry.value().raw();
						zval i;
						entryKey(keyEntry, i);
						IETR_VAL(optionalHold, callOn(leftConstantArray, mIsOptionalKey, 1, &i));
						bool optional = zend_is_true(optionalHold.raw());
						IETR_VAL(valueType, callOn(leftConstantArray, mGetOffsetValueType, 1, leftKeyType));
						zv::Val valueTypeHold = std::move(valueType);
						if (!optional) {
							IETR_TRI(rightHas, triOn(rightConstantArray, mHasOffsetValueType, 1, leftKeyType));
							if (rightHas == PT_TRI_MAYBE) {
								IETR_VAL(rightValueType, callOn(rightConstantArray, mGetOffsetValueType, 1, leftKeyType));
								IETR_VAL(merged, union2(valueTypeHold.raw(), rightValueType.raw()));
								valueTypeHold = std::move(merged);
							}
						}
						if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(newArrayBuilder.raw(), leftKeyType, valueTypeHold.raw(), optional))) return zv::Val();
					}
					IETR_VAL(array, pt_constant_array_type_builder_get_array(newArrayBuilder.raw()));
					resultTypes.push(std::move(array));
				}
			}
			return unionOf(resultTypes);
		}

		// `array{...} + array<TKey, TValue>` keeps the known keyed prefix and
		// folds the right-hand side into an open `...<TKey, TValue>` tail. This
		// only applies when the left arrays' sealedness is known (bleeding edge);
		// otherwise the legacy behavior below is kept for backward compatibility.
		if (leftCount > 0 && rightCount == 0
			&& leftCount < PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT) {
			IETR_TRI(rightIsArray, triOn(rightType, mIsArray));
			if (rightIsArray == PT_TRI_YES) {
				bool allSealednessKnown = true;
				for (zv::ArrayEntry leftEntry : zv::ArrRef(leftConstantArrays.raw())) {
					IETR_TRI(unsealed, triOn(leftEntry.value().raw(), mIsUnsealed));
					if (unsealed == PT_TRI_MAYBE) {
						allSealednessKnown = false;
						break;
					}
				}

				if (allSealednessKnown) {
					IETR_VAL(rightKeyType, callOn(rightType, mGetIterableKeyType));
					IETR_VAL(rightValueType, callOn(rightType, mGetIterableValueType));
					zv::Arr resultTypes = zv::Arr::create((uint32_t) leftCount);
					for (zv::ArrayEntry leftEntry : zv::ArrRef(leftConstantArrays.raw())) {
						zval *leftConstantArray = leftEntry.value().raw();
						IETR_VAL(newArrayBuilder, pt_constant_array_type_builder_create_from_constant_array(leftConstantArray));
						IETR_VAL(existingUnsealed, callOn(leftConstantArray, mGetUnsealedTypes));
						IETR_TRI(unsealed, triOn(leftConstantArray, mIsUnsealed));
						if (unsealed == PT_TRI_YES && Z_TYPE_P(existingUnsealed.raw()) != IS_NULL) {
							zval *existingKey = arrayIndex(existingUnsealed.raw(), 0);
							if (UNEXPECTED(existingKey == NULL)) return zv::Val();
							IETR_VAL(keyUnion, union2(existingKey, rightKeyType.raw()));
							zval *existingValue = arrayIndex(existingUnsealed.raw(), 1);
							if (UNEXPECTED(existingValue == NULL)) return zv::Val();
							IETR_VAL(valueUnion, union2(existingValue, rightValueType.raw()));
							if (UNEXPECTED(!pt_constant_array_type_builder_make_unsealed(newArrayBuilder.raw(), keyUnion.raw(), valueUnion.raw()))) return zv::Val();
						} else {
							if (UNEXPECTED(!pt_constant_array_type_builder_make_unsealed(newArrayBuilder.raw(), rightKeyType.raw(), rightValueType.raw()))) return zv::Val();
						}
						IETR_VAL(array, pt_constant_array_type_builder_get_array(newArrayBuilder.raw()));
						resultTypes.push(std::move(array));
					}
					return unionOf(resultTypes);
				}
			}
		}

		IETR_TRI(leftIsArray, triOn(leftType, mIsArray));
		IETR_TRI(rightIsArray, triOn(rightType, mIsArray));
		if (leftIsArray == PT_TRI_YES && rightIsArray == PT_TRI_YES) {
			IETR_VAL(leftKeyTypeForEquals, callOn(leftType, mGetIterableKeyType));
			IETR_VAL(rightKeyTypeForEquals, callOn(rightType, mGetIterableKeyType));
			zv::Val keyType;
			{
				zv::Val equalsHold = callOn(leftKeyTypeForEquals.raw(), mEquals, 1, rightKeyTypeForEquals.raw());
				if (UNEXPECTED(equalsHold.isUndef())) return zv::Val();
				if (zend_is_true(equalsHold.raw())) {
					// to preserve BenevolentUnionType
					keyType = callOn(leftType, mGetIterableKeyType);
				} else {
					IETR_VAL(leftKey, callOn(leftType, mGetIterableKeyType));
					IETR_VAL(rightKey, callOn(rightType, mGetIterableKeyType));
					keyType = union2(leftKey.raw(), rightKey.raw());
				}
				if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			}

			IETR_VAL(leftIterableValueType, callOn(leftType, mGetIterableValueType));
			IETR_VAL(rightIterableValueType, callOn(rightType, mGetIterableValueType));
			IETR_VAL(valueUnion, union2(leftIterableValueType.raw(), rightIterableValueType.raw()));
			IETR_VAL(arrayType, newArrayType(keyType.raw(), valueUnion.raw()));

			zv::Arr accessories = zv::Arr::create(4);
			if (leftCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				zval *constantArray = arrayIndex(leftConstantArrays.raw(), 0);
				if (UNEXPECTED(constantArray == NULL)) return zv::Val();
				IETR_VAL(keyTypes, callOn(constantArray, mGetKeyTypes));
				if (UNEXPECTED(!requireArray(keyTypes.raw()))) return zv::Val();
				for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
					zval *offsetType = keyEntry.value().raw();
					IETR_TRI(hasOffset, triOn(leftType, mHasOffsetValueType, 1, offsetType));
					if (hasOffset != PT_TRI_YES) {
						continue;
					}

					IETR_VAL(valueType, callOn(leftType, mGetOffsetValueType, 1, offsetType));
					zval accessory;
					if (UNEXPECTED(!pt_has_offset_value_type_new(&accessory, offsetType, valueType.raw()))) return zv::Val();
					accessories.push(zv::Val::adopt(accessory));
				}
			}

			if (rightCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				zval *constantArray = arrayIndex(rightConstantArrays.raw(), 0);
				if (UNEXPECTED(constantArray == NULL)) return zv::Val();
				IETR_VAL(keyTypes, callOn(constantArray, mGetKeyTypes));
				if (UNEXPECTED(!requireArray(keyTypes.raw()))) return zv::Val();
				for (zv::ArrayEntry keyEntry : zv::ArrRef(keyTypes.raw())) {
					zval *offsetType = keyEntry.value().raw();
					IETR_TRI(hasOffset, triOn(rightType, mHasOffsetValueType, 1, offsetType));
					if (hasOffset != PT_TRI_YES) {
						continue;
					}

					IETR_VAL(rightOffsetValueType, callOn(rightType, mGetOffsetValueType, 1, offsetType));
					IETR_VAL(valueType, union2(leftIterableValueType.raw(), rightOffsetValueType.raw()));
					zval accessory;
					if (UNEXPECTED(!pt_has_offset_value_type_new(&accessory, offsetType, valueType.raw()))) return zv::Val();
					accessories.push(zv::Val::adopt(accessory));
				}
			}

			IETR_TRI(leftAtLeastOnce, triOn(leftType, mIsIterableAtLeastOnce));
			bool nonEmpty = leftAtLeastOnce == PT_TRI_YES;
			if (!nonEmpty) {
				IETR_TRI(rightAtLeastOnce, triOn(rightType, mIsIterableAtLeastOnce));
				nonEmpty = rightAtLeastOnce == PT_TRI_YES;
			}
			if (nonEmpty) {
				zval accessory;
				if (UNEXPECTED(!pt_non_empty_array_type_new(&accessory))) return zv::Val();
				accessories.push(zv::Val::adopt(accessory));
			}
			IETR_TRI(leftIsList, triOn(leftType, mIsList));
			if (leftIsList == PT_TRI_YES) {
				IETR_TRI(rightIsList, triOn(rightType, mIsList));
				if (rightIsList == PT_TRI_YES) {
					zval accessory;
					if (UNEXPECTED(!pt_accessory_array_list_type_new(&accessory))) return zv::Val();
					accessories.push(zv::Val::adopt(accessory));
				}
			}

			uint32_t accessoryCount = zend_hash_num_elements(accessories.table());
			if (accessoryCount > 0) {
				zv::Arr intersectArgs = zv::Arr::create(accessoryCount + 1);
				intersectArgs.push(std::move(arrayType));
				for (zv::ArrayEntry entry : zv::ArrRef(accessories.raw())) {
					intersectArgs.push(entry.value());
				}
				return pt_type_combinator_intersect(accessoryCount + 1, intersectArgs.table()->arPacked);
			}

			return arrayType;
		}

		return zv::Val::null();
	}

	/* }}} */

};

} // namespace phpstanturbo

using phpstanturbo::OperatorTypeHelper;

/* {{{ direct entries */

zv::Val pt_operator_type_helper_binary(pt_operator_type_op op, zval *left, zval *right, zval *phpVersion)
{
	switch (op) {
		case PT_OPERATOR_CONCATENATE:
			return OperatorTypeHelper::concat(left, right, phpVersion);
		case PT_OPERATOR_ARRAY_UNION:
			return OperatorTypeHelper::arrayUnion(left, right);
	}
	pt_throw_should_not_happen();
	return zv::Val();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

namespace {

/* the engine's TypeError of a Type parameter the twin declares (the
 * internal arginfo does not enforce it); false = thrown */
bool otArgType(zval *value, uint32_t arg)
{
	zend_class_entry *ce = pt_class(PT_CLASS_TYPE);
	if (UNEXPECTED(ce == NULL)) return false;
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce))) return true;
	zend_argument_type_error(arg, "must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(value));
	return false;
}

bool otArgPhpVersion(zval *value, uint32_t arg)
{
	zend_class_entry *ce = pt_class(PT_CLASS_PHP_VERSION);
	if (UNEXPECTED(ce == NULL)) return false;
	if (EXPECTED(Z_TYPE_P(value) == IS_OBJECT && instanceof_function(Z_OBJCE_P(value), ce))) return true;
	zend_argument_type_error(arg, "must be of type PHPStan\\Php\\PhpVersion, %s given", zend_zval_value_name(value));
	return false;
}

/* the operator string of resolveCommonMath(); false =
 * the ValueError of an operator the twin does not handle */
/* the (Type $left, Type $right, PhpVersion $phpVersion) glue */
#define OT_BINARY_GLUE(body) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		zval *leftType, *rightType, *phpVersion; \
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, leftType, rightType, phpVersion)) RETURN_THROWS(); \
		if (UNEXPECTED(!otArgType(leftType, 1) || !otArgType(rightType, 2) || !otArgPhpVersion(phpVersion, 3))) RETURN_THROWS(); \
		PT_RETURN_VAL(body); \
	}

/* the (Type $leftType, Type $rightType) glue */
#define OT_TYPES_GLUE(body) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		zval *leftType, *rightType; \
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, leftType, rightType)) RETURN_THROWS(); \
		if (UNEXPECTED(!otArgType(leftType, 1) || !otArgType(rightType, 2))) RETURN_THROWS(); \
		PT_RETURN_VAL(body); \
	}

/* the (Type $type) glue */
#define OT_TYPE_GLUE(body) \
	[](INTERNAL_FUNCTION_PARAMETERS) { \
		zval *type; \
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS(); \
		if (UNEXPECTED(!otArgType(type, 1))) RETURN_THROWS(); \
		PT_RETURN_VAL(body); \
	}

} // namespace

PT_MINIT_REGISTRATION(pt_register_operator_type_helper)
{
	reg::Class cls("PHPStan\\Type\\OperatorTypeHelper");
	ptdecl::OperatorTypeHelper::declareClass(cls);
	ptdecl::OperatorTypeHelper::declareProperties(cls);

	cls.method(sigs::concat, OT_BINARY_GLUE(OperatorTypeHelper::concat(leftType, rightType, phpVersion)));
	cls.method(sigs::arrayUnion, OT_BINARY_GLUE(OperatorTypeHelper::arrayUnion(leftType, rightType)));
	cls.method(sigs::tryArrayUnion, OT_TYPES_GLUE(OperatorTypeHelper::tryArrayUnion(leftType, rightType)));
	cls.method(sigs::canEndInWhitespace, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *left, *leftStringType, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, left, leftStringType, phpVersion)) RETURN_THROWS();
		if (UNEXPECTED(!otArgType(left, 1) || !otArgType(leftStringType, 2) || !otArgPhpVersion(phpVersion, 3))) RETURN_THROWS();
		int result = OperatorTypeHelper::canEndInWhitespace(left, leftStringType, phpVersion);
		if (UNEXPECTED(result < 0)) RETURN_THROWS();
		RETURN_BOOL(result == 1);
	});

	cls.shadow(&pt_ce_operator_type_helper);
}

#undef OT_BINARY_GLUE
#undef OT_TYPES_GLUE
#undef OT_TYPE_GLUE

/* }}} */
