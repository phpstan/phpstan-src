/*
 * PHPStanTurbo\OperatorTypeHelper — native implementation of
 * PHPStan\Type\OperatorTypeHelper, the operators of Type::arithmetic*(),
 * Type::bitwise*(), Type::stringConcatenate() and Type::arrayUnion() that
 * OperatorTypeTrait delegates to (pt_type_trait_operator() in
 * TypeTraits.cpp calls pt_operator_type_helper_binary() /
 * pt_operator_type_helper_negate() directly).
 *
 * A final class of static methods: every method mirrors its twin, the
 * private ones are registered too so the method sets stay identical. The
 * arithmetic runs on the engine's own operator functions (add_function(),
 * mod_function(), bitwise_and_function(), zend_compare(), ...), so int
 * overflow into float, float-to-int truncation and the mixed-type
 * comparisons behave exactly like the twin's PHP operators. None of the
 * operators depends on the PhpVersion yet; the twin's parameter is accepted
 * and passed along.
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
	static zv::Val concat(zval *left, zval *right)
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
			if (leftNumeric == PT_TRI_YES) {
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
						|| !(integerValidation ? ZSTR_VAL(string)[0] != '-' : isDecimalDigits(string))
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

	/* }}} */

	/* {{{ the bitwise operators */

	/* Mirrors bitwiseAnd() / bitwiseOr() / bitwiseXor() (kind:
	 * IETR_BITWISE_AND / _OR / _XOR — the three bodies differ only in the
	 * operation and the range helper) */
	static zv::Val bitwise(IetrBinaryKind kind, zval *leftType, zval *rightType)
	{
		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return getNeverType(leftType, rightType);
		}

		zv::Val result;
		zend_long code = getFiniteOrConstantScalarTypes(leftType, rightType, kind, result);
		if (UNEXPECTED(code < 0)) return zv::Val();
		if (code == 0) return result;

		// computed before optimizeScalarType() below widens integer ranges to int
		IETR_VAL(leftNumberType, callOn(leftType, mToNumber));
		IETR_VAL(rightNumberType, callOn(rightType, mToNumber));

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (code == PT_IETR_IS_SCALAR_TYPE) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		bool leftMixed = isA(leftType, pt_ce_mixed_type);
		bool rightMixed = isA(rightType, pt_ce_mixed_type);
		if (leftMixed && rightMixed) {
			IETR_VAL(integerType, newIntegerType());
			IETR_VAL(stringType, newStringType());
			zv::Arr types = zv::Arr::create(2);
			types.push(std::move(integerType));
			types.push(std::move(stringType));
			return newBenevolentUnionType(types);
		}

		IETR_TRI(leftIsString, triOn(leftType, mIsString));
		IETR_TRI(rightIsString, triOn(rightType, mIsString));
		if (
			(leftIsString == PT_TRI_YES || leftMixed)
			&& (rightIsString == PT_TRI_YES || rightMixed)
		) {
			return newStringType();
		}
		if (leftIsString == PT_TRI_MAYBE && rightIsString == PT_TRI_MAYBE) {
			return newErrorType();
		}

		{
			IETR_VAL(leftToNumber, callOn(leftType, mToNumber));
			if (isA(leftToNumber.raw(), pt_ce_error_type)) return newErrorType();
			IETR_VAL(rightToNumber, callOn(rightType, mToNumber));
			if (isA(rightToNumber.raw(), pt_ce_error_type)) return newErrorType();
		}

		zv::Val range = kind == IETR_BITWISE_AND
			? computeBitwiseAndRange(leftNumberType.raw(), rightNumberType.raw())
			: computeBitwiseOrXorRange(leftNumberType.raw(), rightNumberType.raw());
		if (UNEXPECTED(range.isUndef())) return zv::Val();
		if (Z_TYPE_P(range.raw()) != IS_NULL) return range;

		return newIntegerType();
	}

	/* Mirrors getFiniteOrConstantScalarTypes() with the operation of `kind`:
	 * 0 = the Type in `result`, PT_IETR_IS_SCALAR_TYPE / PT_IETR_IS_UNKNOWN,
	 * -1 = pending exception */
	static zend_long getFiniteOrConstantScalarTypes(zval *leftType, zval *rightType, IetrBinaryKind kind, zv::Val &result)
	{
		pt_ietr_binary_op operation = kind == IETR_BITWISE_AND ? bitwise_and_function : (kind == IETR_BITWISE_OR ? bitwise_or_function : bitwise_xor_function);
		return getFiniteOrConstantScalarTypesWith(leftType, rightType, operation, NULL, result);
	}

	/* the same with the operation as the engine's operator function or, for
	 * a PHP caller of the private method, its $operationCallable */
	static zend_long getFiniteOrConstantScalarTypesWith(zval *leftType, zval *rightType, pt_ietr_binary_op operation, zval *operationCallable, zv::Val &result)
	{
		zv::Val leftTypes = isA(leftType, pt_ce_integer_range_type) ? callOn(leftType, mGetFiniteTypes) : callOn(leftType, mGetConstantScalarTypes);
		if (UNEXPECTED(leftTypes.isUndef())) return -1;
		zv::Val rightTypes = isA(rightType, pt_ce_integer_range_type) ? callOn(rightType, mGetFiniteTypes) : callOn(rightType, mGetConstantScalarTypes);
		if (UNEXPECTED(rightTypes.isUndef())) return -1;
		if (UNEXPECTED(!requireArray(leftTypes.raw()) || !requireArray(rightTypes.raw()))) return -1;

		zend_long leftTypesCount = zend_hash_num_elements(Z_ARRVAL_P(leftTypes.raw()));
		zend_long rightTypesCount = zend_hash_num_elements(Z_ARRVAL_P(rightTypes.raw()));

		if (leftTypesCount == 0 || rightTypesCount == 0) {
			return PT_IETR_IS_UNKNOWN;
		}

		bool generalize = leftTypesCount * rightTypesCount > PT_IETR_CALCULATE_SCALARS_LIMIT;
		if (generalize) {
			return PT_IETR_IS_SCALAR_TYPE;
		}

		zend_class_entry *constantScalarCe = pt_class(PT_CLASS_CONSTANT_SCALAR_TYPE);
		if (UNEXPECTED(constantScalarCe == NULL)) return -1;
		zv::Arr resultTypes = zv::Arr::create((uint32_t) (leftTypesCount * rightTypesCount));
		for (zv::ArrayEntry leftEntry : zv::ArrRef(leftTypes.raw())) {
			zval *leftTypeInner = leftEntry.value().raw();
			for (zv::ArrayEntry rightEntry : zv::ArrRef(rightTypes.raw())) {
				zval *rightTypeInner = rightEntry.value().raw();
				zv::Val resultType;
				if (isA(leftTypeInner, pt_ce_constant_string_type) && isA(rightTypeInner, pt_ce_constant_string_type)) {
					zv::Val leftValue = callOn(leftTypeInner, mGetValue);
					if (UNEXPECTED(leftValue.isUndef())) return -1;
					zv::Val rightValue = callOn(rightTypeInner, mGetValue);
					if (UNEXPECTED(rightValue.isUndef())) return -1;
					zv::Val resultValue = applyOperation(operation, operationCallable, leftValue.raw(), rightValue.raw());
					if (UNEXPECTED(resultValue.isUndef())) return -1;
					resultType = typeFromValue(resultValue.raw());
				} else {
					zv::Val leftNumberType = callOn(leftTypeInner, mToNumber);
					if (UNEXPECTED(leftNumberType.isUndef())) return -1;
					zv::Val rightNumberType = callOn(rightTypeInner, mToNumber);
					if (UNEXPECTED(rightNumberType.isUndef())) return -1;

					if (isA(leftNumberType.raw(), pt_ce_error_type) || isA(rightNumberType.raw(), pt_ce_error_type)) {
						result = newErrorType();
						return result.isUndef() ? -1 : 0;
					}

					if (!isA(leftNumberType.raw(), constantScalarCe) || !isA(rightNumberType.raw(), constantScalarCe)) {
						pt_throw_should_not_happen();
						return -1;
					}

					zval leftInteger, rightInteger;
					if (UNEXPECTED(!toIntegerOperand(leftTypeInner, leftInteger) || !toIntegerOperand(rightTypeInner, rightInteger))) return -1;
					zv::Val resultValue = applyOperation(operation, operationCallable, &leftInteger, &rightInteger);
					if (UNEXPECTED(resultValue.isUndef())) return -1;
					resultType = typeFromValue(resultValue.raw());
				}
				if (UNEXPECTED(resultType.isUndef())) return -1;
				resultTypes.push(std::move(resultType));
			}
		}
		result = unionOf(resultTypes);
		return result.isUndef() ? -1 : 0;
	}

	/* Mirrors toIntegerOperand(): the only value of
	 * $type->toInteger()->getConstantScalarValues(), as an IS_LONG zval */
	static bool toIntegerOperand(zval *type, zval &integer)
	{
		zv::Val integerType = callOn(type, mToInteger);
		if (UNEXPECTED(integerType.isUndef())) return false;
		zv::Val values = callOn(integerType.raw(), mGetConstantScalarValues);
		if (UNEXPECTED(values.isUndef()) || UNEXPECTED(!requireArray(values.raw()))) return false;
		zval *value = zend_hash_index_find(Z_ARRVAL_P(values.raw()), 0);
		if (zend_hash_num_elements(Z_ARRVAL_P(values.raw())) != 1 || value == NULL || Z_TYPE_P(value) != IS_LONG) {
			pt_throw_should_not_happen();
			return false;
		}
		ZVAL_LONG(&integer, Z_LVAL_P(value));
		return true;
	}

	/* $operationCallable($a, $b) or the operator */
	static zv::Val applyOperation(pt_ietr_binary_op operation, zval *operationCallable, zval *a, zval *b)
	{
		if (operationCallable != NULL) {
			zv::Args argv{a, b};
			return pt_type_call_callable(operationCallable, 2, argv);
		}
		return phpOp(operation, a, b);
	}

	/* }}} */

	/* {{{ the arithmetic operators */

	/* the constant-scalar product of add() / subtract() / multiply() /
	 * getDivTypeFromTypes() / modulo() / shiftLeft() / shiftRight(): 0 = the Type in `result`
	 * (the union, or an ErrorType the twin returns from inside the loop),
	 * 1 = generalize (the twin's optimizeScalarType() of both sides follows),
	 * 2 = not both sides constant, -1 = pending exception */
	static int constantScalarArithmetic(zval *leftType, zval *rightType, IetrBinaryKind kind, zv::Val &result)
	{
		zv::Val leftTypes = callOn(leftType, mGetConstantScalarTypes);
		if (UNEXPECTED(leftTypes.isUndef())) return -1;
		zv::Val rightTypes = callOn(rightType, mGetConstantScalarTypes);
		if (UNEXPECTED(rightTypes.isUndef())) return -1;
		if (UNEXPECTED(!requireArray(leftTypes.raw()) || !requireArray(rightTypes.raw()))) return -1;
		zend_long leftTypesCount = zend_hash_num_elements(Z_ARRVAL_P(leftTypes.raw()));
		zend_long rightTypesCount = zend_hash_num_elements(Z_ARRVAL_P(rightTypes.raw()));
		if (leftTypesCount <= 0 || rightTypesCount <= 0) return 2;

		bool generalize = leftTypesCount * rightTypesCount > PT_IETR_CALCULATE_SCALARS_LIMIT;
		if (generalize) return 1;

		zend_class_entry *constantScalarCe = pt_class(PT_CLASS_CONSTANT_SCALAR_TYPE);
		if (UNEXPECTED(constantScalarCe == NULL)) return -1;
		zv::Arr resultTypes = zv::Arr::create((uint32_t) (leftTypesCount * rightTypesCount));
		for (zv::ArrayEntry leftEntry : zv::ArrRef(leftTypes.raw())) {
			for (zv::ArrayEntry rightEntry : zv::ArrRef(rightTypes.raw())) {
				zv::Val leftNumberType = callOn(leftEntry.value().raw(), mToNumber);
				if (UNEXPECTED(leftNumberType.isUndef())) return -1;
				zv::Val rightNumberType = callOn(rightEntry.value().raw(), mToNumber);
				if (UNEXPECTED(rightNumberType.isUndef())) return -1;

				if (isA(leftNumberType.raw(), pt_ce_error_type) || isA(rightNumberType.raw(), pt_ce_error_type)) {
					result = newErrorType();
					return result.isUndef() ? -1 : 0;
				}

				if (!isA(leftNumberType.raw(), constantScalarCe) || !isA(rightNumberType.raw(), constantScalarCe)) {
					pt_throw_should_not_happen();
					return -1;
				}

				zv::Val leftValue = callOn(leftNumberType.raw(), mGetValue);
				if (UNEXPECTED(leftValue.isUndef())) return -1;
				zv::Val rightValue = callOn(rightNumberType.raw(), mGetValue);
				if (UNEXPECTED(rightValue.isUndef())) return -1;

				zv::Val resultValue;
				switch (kind) {
					case IETR_PLUS:
						resultValue = phpOp(add_function, leftValue.raw(), rightValue.raw());
						break;
					case IETR_MINUS:
						resultValue = phpOp(sub_function, leftValue.raw(), rightValue.raw());
						break;
					case IETR_MUL:
						resultValue = phpOp(mul_function, leftValue.raw(), rightValue.raw());
						break;
					case IETR_DIV:
						if (isZeroNumber(rightValue.raw())) {
							result = newErrorType();
							return result.isUndef() ? -1 : 0;
						}
						resultValue = phpOp(div_function, leftValue.raw(), rightValue.raw());
						break;
					case IETR_MOD: {
						zval rightInteger;
						if (UNEXPECTED(!toIntegerOperand(rightEntry.value().raw(), rightInteger))) return -1;
						if (Z_LVAL(rightInteger) == 0) {
							result = newErrorType();
							return result.isUndef() ? -1 : 0;
						}
						zval leftInteger;
						if (UNEXPECTED(!toIntegerOperand(leftEntry.value().raw(), leftInteger))) return -1;
						resultValue = phpOp(mod_function, &leftInteger, &rightInteger);
						break;
					}
					case IETR_SHIFT_LEFT:
					case IETR_SHIFT_RIGHT: {
						zval rightInteger;
						if (UNEXPECTED(!toIntegerOperand(rightEntry.value().raw(), rightInteger))) return -1;
						if (Z_LVAL(rightInteger) < 0) {
							result = newErrorType();
							return result.isUndef() ? -1 : 0;
						}
						zval leftInteger;
						if (UNEXPECTED(!toIntegerOperand(leftEntry.value().raw(), leftInteger))) return -1;
						resultValue = phpOp(kind == IETR_SHIFT_LEFT ? shift_left_function : shift_right_function, &leftInteger, &rightInteger);
						break;
					}
					default:
						pt_throw_should_not_happen();
						return -1;
				}
				if (UNEXPECTED(resultValue.isUndef())) return -1;
				zv::Val resultType = typeFromValue(resultValue.raw());
				if (UNEXPECTED(resultType.isUndef())) return -1;
				resultTypes.push(std::move(resultType));
			}
		}

		result = unionOf(resultTypes);
		return result.isUndef() ? -1 : 0;
	}

	/* Mirrors divide(). */
	static zv::Val divide(zval *leftType, zval *rightType)
	{
		IETR_VAL(result, getDivTypeFromTypes(leftType, rightType));

		IETR_TRI(leftIsInteger, triOn(leftType, mIsInteger));
		if (leftIsInteger == PT_TRI_YES) {
			IETR_TRI(rightIsInteger, triOn(rightType, mIsInteger));
			if (rightIsInteger == PT_TRI_YES) {
				IETR_VAL(modType, modulo(leftType, rightType));
				IETR_TRI(modIsInteger, triOn(modType.raw(), mIsInteger));
				if (modIsInteger == PT_TRI_YES) {
					IETR_VAL(zero, newConstantIntegerType(0));
					IETR_TRI(zeroIsSuperTypeOfMod, triOn(zero.raw(), mIsSuperTypeOf, 1, modType.raw()));
					if (zeroIsSuperTypeOfMod == PT_TRI_YES) {
						IETR_VAL(floatType, newFloatType());
						IETR_VAL(withoutFloat, pt_type_combinator_remove(result.raw(), floatType.raw()));

						// PHP_INT_MIN / -1 divides without a remainder but still overflows to a float
						if (!isA(withoutFloat.raw(), pt_ce_never_type)) {
							return withoutFloat;
						}
					}
				}
			}
		}

		return result;
	}

	/* Mirrors getDivTypeFromTypes(). */
	static zv::Val getDivTypeFromTypes(zval *leftType, zval *rightType)
	{
		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, IETR_DIV, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		{
			IETR_VAL(rightNumberType, callOn(rightType, mToNumber));
			IETR_VAL(rightScalarValues, callOn(rightNumberType.raw(), mGetConstantScalarValues));
			if (UNEXPECTED(!requireArray(rightScalarValues.raw()))) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(rightScalarValues.raw())) {
				if (isZeroNumber(entry.value().raw())) {
					return newErrorType();
				}
			}
		}

		return resolveCommonMath(IETR_DIV, leftType, rightType);
	}

	/* Mirrors modulo(). */
	static zv::Val modulo(zval *leftType, zval *rightType)
	{
		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return getNeverType(leftType, rightType);
		}

		{
			IETR_VAL(leftToNumber, callOn(leftType, mToNumber));
			if (isA(leftToNumber.raw(), pt_ce_error_type)) return newErrorType();
			IETR_VAL(rightToNumber, callOn(rightType, mToNumber));
			if (isA(rightToNumber.raw(), pt_ce_error_type)) return newErrorType();
		}

		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, IETR_MOD, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		{
			IETR_VAL(integerType, callOn(rightType, mToInteger));
			if (isA(integerType.raw(), pt_ce_constant_integer_type)) {
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(integerType.raw()), value))) return zv::Val();
				if (value == 1) return newConstantIntegerType(0);
			}
		}

		{
			IETR_VAL(rightNumberType, callOn(rightType, mToNumber));
			IETR_VAL(rightScalarValues, callOn(rightNumberType.raw(), mGetConstantScalarValues));
			if (UNEXPECTED(!requireArray(rightScalarValues.raw()))) return zv::Val();
			for (zv::ArrayEntry entry : zv::ArrRef(rightScalarValues.raw())) {
				if (isZeroNumber(entry.value().raw())) {
					return newErrorType();
				}
			}
		}

		NullableLong maxMagnitude = NullableLong::null();
		{
			IETR_VAL(rightInteger, callOn(rightType, mToInteger));
			bool hasDivisorBounds;
			NullableLong divisorMin, divisorMax;
			if (UNEXPECTED(!getIntegerBounds(rightInteger.raw(), hasDivisorBounds, divisorMin, divisorMax))) return zv::Val();
			if (hasDivisorBounds) {
				maxMagnitude = getMaxModuloMagnitude(divisorMin, divisorMax);
			}
		}

		bool hasLeftBounds;
		NullableLong leftMin, leftMax;
		{
			IETR_VAL(leftInteger, callOn(leftType, mToInteger));
			if (UNEXPECTED(!getIntegerBounds(leftInteger.raw(), hasLeftBounds, leftMin, leftMax))) return zv::Val();
		}
		if (!hasLeftBounds) {
			leftMin = NullableLong::null();
			leftMax = NullableLong::null();
		}
		if (leftMin.isNull) {
			IETR_VAL(nonNegative, fromInterval(NullableLong::of(0), NullableLong::null()));
			IETR_TRI(leftIsNonNegative, triOn(nonNegative.raw(), mIsSuperTypeOf, 1, leftType));
			if (leftIsNonNegative == PT_TRI_YES) leftMin = NullableLong::of(0);
		}
		if (leftMax.isNull) {
			IETR_VAL(nonPositive, fromInterval(NullableLong::null(), NullableLong::of(0)));
			IETR_TRI(leftIsNonPositive, triOn(nonPositive.raw(), mIsSuperTypeOf, 1, leftType));
			if (leftIsNonPositive == PT_TRI_YES) leftMax = NullableLong::of(0);
		}

		// The result has the sign of the dividend and is never bigger in magnitude than either
		// the dividend or the largest magnitude the divisor can have.
		NullableLong rangeMax;
		if (!leftMax.isNull && leftMax.value <= 0) {
			rangeMax = NullableLong::of(0);
		} else if (maxMagnitude.isNull) {
			rangeMax = leftMax;
		} else if (leftMax.isNull) {
			rangeMax = maxMagnitude;
		} else {
			rangeMax = NullableLong::of(std::min(leftMax.value, maxMagnitude.value));
		}

		NullableLong rangeMin;
		if (!leftMin.isNull && leftMin.value >= 0) {
			rangeMin = NullableLong::of(0);
		} else if (maxMagnitude.isNull) {
			rangeMin = leftMin;
		} else if (leftMin.isNull) {
			rangeMin = NullableLong::of(-maxMagnitude.value);
		} else {
			rangeMin = NullableLong::of(std::max(leftMin.value, -maxMagnitude.value));
		}

		return fromInterval(rangeMin, rangeMax);
	}

	/* Mirrors toIntBound(): the bound an overflowing float stands for, since
	 * the values past the int range are floats. ZEND_LONG_MAX is not
	 * representable as a double, so the first double past the int range is
	 * (double) ZEND_LONG_MAX itself. */
	static void toIntBound(double value, zval *out)
	{
		if (!std::isfinite(value)) {
			ZVAL_NULL(out);
		} else if (value >= (double) ZEND_LONG_MAX) {
			ZVAL_LONG(out, ZEND_LONG_MAX);
		} else if (value <= (double) ZEND_LONG_MIN) {
			ZVAL_LONG(out, ZEND_LONG_MIN);
		} else {
			ZVAL_LONG(out, (zend_long) value);
		}
	}

	/* private static: the highest possible absolute value of `$x % $divisor`,
	 * one less than the largest possible absolute value of the divisor; null
	 * when there is no such bound. A divisor reaching PHP_INT_MIN is reported
	 * as unbounded too: abs(PHP_INT_MIN) does not fit into an integer, and the
	 * bound it stands for is PHP_INT_MAX, which is all an unbounded result can
	 * hold anyway */
	static NullableLong getMaxModuloMagnitude(NullableLong divisorMin, NullableLong divisorMax)
	{
		if (divisorMin.isNull || divisorMax.isNull || divisorMin.value == ZEND_LONG_MIN) return NullableLong::null();
		zend_long absMin = divisorMin.value < 0 ? -divisorMin.value : divisorMin.value;
		zend_long absMax = divisorMax.value < 0 ? -divisorMax.value : divisorMax.value;
		zend_long magnitude = std::max(absMin, absMax);
		if (magnitude == 0) return NullableLong::null();
		return NullableLong::of(magnitude - 1);
	}

	/* private: the lowest and highest value the type can hold, null for an
	 * unbounded side; `has` false when the type is not built from integer
	 * ranges and constants. false = pending exception */
	[[nodiscard]] static bool getIntegerBounds(zval *type, bool &has, NullableLong &outMin, NullableLong &outMax)
	{
		has = false;
		zv::Val innerTypesHold;
		zval single;
		HashTable *innerTypes = NULL;
		if (isA(type, pt_ce_union_type)) {
			zv::Val types = callOn(type, mGetTypes);
			if (UNEXPECTED(types.isUndef() || !requireArray(types.raw()))) return false;
			innerTypesHold = std::move(types);
			innerTypes = Z_ARRVAL_P(innerTypesHold.raw());
			if (zend_hash_num_elements(innerTypes) == 0) return true;
		}

		NullableLong min = NullableLong::null();
		NullableLong max = NullableLong::null();
		bool unboundedMin = false;
		bool unboundedMax = false;
		auto visit = [&](zval *innerType, bool &known) -> bool {
			NullableLong innerMin, innerMax;
			known = true;
			if (isA(innerType, pt_ce_integer_range_type)) {
				if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(innerType), innerMin, innerMax))) return false;
			} else if (isA(innerType, pt_ce_constant_integer_type)) {
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(innerType), value))) return false;
				innerMin = NullableLong::of(value);
				innerMax = innerMin;
			} else {
				known = false;
				return true;
			}

			if (innerMin.isNull) {
				unboundedMin = true;
			} else if (min.isNull || innerMin.value < min.value) {
				min = innerMin;
			}

			if (innerMax.isNull) {
				unboundedMax = true;
			} else if (max.isNull || innerMax.value > max.value) {
				max = innerMax;
			}
			return true;
		};

		if (innerTypes == NULL) {
			ZVAL_COPY_VALUE(&single, type);
			bool known;
			if (UNEXPECTED(!visit(&single, known))) return false;
			if (!known) return true;
		} else {
			for (zv::ArrayEntry entry : zv::TableRef(innerTypes)) {
				bool known;
				if (UNEXPECTED(!visit(entry.value().deref().raw(), known))) return false;
				if (!known) return true;
			}
		}

		has = true;
		outMin = unboundedMin ? NullableLong::null() : min;
		outMax = unboundedMax ? NullableLong::null() : max;
		return true;
	}

	/* getIntegerBounds() as the private method returns it: the [min, max]
	 * pair or null; UNDEF = pending exception */
	static zv::Val getIntegerBoundsValue(zval *type)
	{
		bool has = false;
		NullableLong min = NullableLong::null();
		NullableLong max = NullableLong::null();
		if (UNEXPECTED(!getIntegerBounds(type, has, min, max))) return zv::Val();
		if (!has) return zv::Val::null();
		zv::Arr pair = zv::Arr::create(2);
		pair.push(min.toVal());
		pair.push(max.toVal());
		return zv::Val(std::move(pair));
	}

	/* shiftLeftOverflows() for the private method's glue; false = pending
	 * exception */
	[[nodiscard]] static bool shiftLeftOverflowsValue(zend_long value, zend_long shift, bool &out)
	{
		zval shiftZv;
		ZVAL_LONG(&shiftZv, shift);
		return shiftLeftOverflows(value, &shiftZv, out);
	}

	/* Mirrors add(). */
	static zv::Val add(zval *leftType, zval *rightType)
	{
		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return getNeverType(leftType, rightType);
		}

		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, IETR_PLUS, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		{
			IETR_VAL(arrayUnion, tryArrayUnion(leftType, rightType));
			if (Z_TYPE_P(arrayUnion.raw()) != IS_NULL) return arrayUnion;
		}

		IETR_TRI(leftIsArray, triOn(leftType, mIsArray));
		IETR_TRI(rightIsArray, triOn(rightType, mIsArray));

		bool leftMixed = isA(leftType, pt_ce_mixed_type);
		bool rightMixed = isA(rightType, pt_ce_mixed_type);
		if (leftMixed && rightMixed) {
			IETR_VAL(floatType, newFloatType());
			IETR_VAL(integerType, newIntegerType());
			zv::Arr types = zv::Arr::create(3);
			types.push(std::move(floatType));
			types.push(std::move(integerType));
			if (leftIsArray == PT_TRI_NO && rightIsArray == PT_TRI_NO) {
				return newBenevolentUnionType(types);
			}
			IETR_VAL(keyMixed, newMixedType());
			IETR_VAL(valueMixed, newMixedType());
			IETR_VAL(arrayType, newArrayType(keyMixed.raw(), valueMixed.raw()));
			types.push(std::move(arrayType));
			return newBenevolentUnionType(types);
		}

		if (
			(leftIsArray == PT_TRI_YES && rightIsArray == PT_TRI_NO)
			|| (leftIsArray == PT_TRI_NO && rightIsArray == PT_TRI_YES)
		) {
			return newErrorType();
		}

		if (
			(leftIsArray == PT_TRI_YES && rightIsArray == PT_TRI_MAYBE)
			|| (leftIsArray == PT_TRI_MAYBE && rightIsArray == PT_TRI_YES)
		) {
			IETR_VAL(keyMixed, newMixedType());
			IETR_VAL(valueMixed, newMixedType());
			IETR_VAL(resultType, newArrayType(keyMixed.raw(), valueMixed.raw()));
			IETR_TRI(leftAtLeastOnce, triOn(leftType, mIsIterableAtLeastOnce));
			bool nonEmpty = leftAtLeastOnce == PT_TRI_YES;
			if (!nonEmpty) {
				IETR_TRI(rightAtLeastOnce, triOn(rightType, mIsIterableAtLeastOnce));
				nonEmpty = rightAtLeastOnce == PT_TRI_YES;
			}
			if (nonEmpty) {
				zval accessory;
				if (UNEXPECTED(!pt_non_empty_array_type_new(&accessory))) return zv::Val();
				zv::Val accessoryHold = zv::Val::adopt(accessory);
				zv::Args argv{resultType.raw(), accessoryHold.raw()};
				return pt_type_combinator_intersect(2, argv);
			}

			return resultType;
		}

		if (leftIsArray == PT_TRI_MAYBE && rightIsArray == PT_TRI_MAYBE) {
			zv::Arr plusableTypes = zv::Arr::create(5);
			{
				IETR_VAL(stringType, newStringType());
				plusableTypes.push(std::move(stringType));
				IETR_VAL(floatType, newFloatType());
				plusableTypes.push(std::move(floatType));
				IETR_VAL(integerType, newIntegerType());
				plusableTypes.push(std::move(integerType));
				IETR_VAL(keyMixed, newMixedType());
				IETR_VAL(valueMixed, newMixedType());
				IETR_VAL(arrayType, newArrayType(keyMixed.raw(), valueMixed.raw()));
				plusableTypes.push(std::move(arrayType));
				IETR_VAL(booleanType, newBooleanType());
				plusableTypes.push(std::move(booleanType));
			}
			IETR_VAL(plusable, newUnionType(plusableTypes));

			IETR_TRI(plusableSuperTypeOfLeftTri, triOn(plusable.raw(), mIsSuperTypeOf, 1, leftType));
			IETR_TRI(plusableSuperTypeOfRightTri, triOn(plusable.raw(), mIsSuperTypeOf, 1, rightType));
			bool plusableSuperTypeOfLeft = plusableSuperTypeOfLeftTri == PT_TRI_YES;
			bool plusableSuperTypeOfRight = plusableSuperTypeOfRightTri == PT_TRI_YES;
			if (plusableSuperTypeOfLeft && plusableSuperTypeOfRight) {
				return union2(leftType, rightType);
			}
			if (plusableSuperTypeOfLeft && rightMixed) {
				return zv::Val::copyOf(zv::Ref(leftType));
			}
			if (plusableSuperTypeOfRight && leftMixed) {
				return zv::Val::copyOf(zv::Ref(rightType));
			}
		}

		return resolveCommonMath(IETR_PLUS, leftType, rightType);
	}

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

	/* Mirrors subtract() / multiply() (kind IETR_MINUS / IETR_MUL). */
	static zv::Val subtractOrMultiply(IetrBinaryKind kind, zval *leftType, zval *rightType)
	{
		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, kind, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		if (kind == IETR_MUL) {
			/* zero times an integer is always 0; times a float it is 0.0, -0.0 or NAN */
			IETR_VAL(leftNumberType, callOn(leftType, mToNumber));
			IETR_VAL(rightNumberType, callOn(rightType, mToNumber));
			int leftZero = isConstantIntegerZero(leftNumberType.raw());
			if (UNEXPECTED(leftZero < 0)) return zv::Val();
			if (leftZero) {
				IETR_TRI(rightIsInteger, triOn(rightNumberType.raw(), mIsInteger));
				if (rightIsInteger == PT_TRI_YES) return newConstantIntegerType(0);
			}
			int rightZero = isConstantIntegerZero(rightNumberType.raw());
			if (UNEXPECTED(rightZero < 0)) return zv::Val();
			if (rightZero) {
				IETR_TRI(leftIsInteger, triOn(leftNumberType.raw(), mIsInteger));
				if (leftIsInteger == PT_TRI_YES) return newConstantIntegerType(0);
			}
		}

		return resolveCommonMath(kind, leftType, rightType);
	}

	/* Mirrors shiftLeft() / shiftRight(). */
	static zv::Val shift(IetrBinaryKind kind, zval *leftType, zval *rightType)
	{
		if (isA(leftType, pt_ce_never_type) || isA(rightType, pt_ce_never_type)) {
			return getNeverType(leftType, rightType);
		}

		zv::Val result;
		int constant = constantScalarArithmetic(leftType, rightType, kind, result);
		if (UNEXPECTED(constant < 0)) return zv::Val();
		if (constant == 0) return result;

		zv::Val optimizedLeft;
		zv::Val optimizedRight;
		if (constant == 1) {
			optimizedLeft = optimizeScalarType(leftType);
			if (UNEXPECTED(optimizedLeft.isUndef())) return zv::Val();
			leftType = optimizedLeft.raw();
			optimizedRight = optimizeScalarType(rightType);
			if (UNEXPECTED(optimizedRight.isUndef())) return zv::Val();
			rightType = optimizedRight.raw();
		}

		{
			IETR_VAL(leftNumberType, callOn(leftType, mToNumber));
			IETR_VAL(rightNumberType, callOn(rightType, mToNumber));

			if (isA(leftNumberType.raw(), pt_ce_error_type) || isA(rightNumberType.raw(), pt_ce_error_type)) {
				return newErrorType();
			}
		}

		return resolveCommonMath(kind, leftType, rightType);
	}

	/* Mirrors optimizeScalarType(). */
	static zv::Val optimizeScalarType(zval *type)
	{
		zv::Arr types = zv::Arr::create(4);
		{
			IETR_TRI(isInteger, triOn(type, mIsInteger));
			if (isInteger == PT_TRI_YES) {
				IETR_VAL(integerType, newIntegerType());
				types.push(std::move(integerType));
			}
		}
		{
			IETR_TRI(isString, triOn(type, mIsString));
			if (isString == PT_TRI_YES) {
				IETR_VAL(stringType, newStringType());
				types.push(std::move(stringType));
			}
		}
		{
			IETR_TRI(isFloat, triOn(type, mIsFloat));
			if (isFloat == PT_TRI_YES) {
				IETR_VAL(floatType, newFloatType());
				types.push(std::move(floatType));
			}
		}
		{
			IETR_TRI(isNull, triOn(type, mIsNull));
			if (isNull == PT_TRI_YES) {
				IETR_VAL(nullType, newNullType());
				types.push(std::move(nullType));
			}
		}

		uint32_t count = zend_hash_num_elements(types.table());
		if (count == 0) {
			return newErrorType();
		}

		if (count == 1) {
			return zv::Val::copyOf(zv::Ref(zend_hash_index_find(types.table(), 0)));
		}

		return newUnionType(types);
	}

	/* Mirrors getNonNegativeIntegerBounds(): true fills min/max (max null
	 * for no finite upper bound), false = null; -1 = pending exception */
	static int getNonNegativeIntegerBounds(zval *type, zend_long &min, NullableLong &max)
	{
		if (isA(type, pt_ce_integer_range_type)) {
			NullableLong rangeMin, rangeMax;
			if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(type), rangeMin, rangeMax))) return -1;
			if (!rangeMin.isNull && rangeMin.value >= 0) {
				min = rangeMin.value;
				max = rangeMax;
				return 1;
			}
			return 0;
		}
		if (isA(type, pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(type), value))) return -1;
			if (value >= 0) {
				min = value;
				max = NullableLong::of(value);
				return 1;
			}
		}
		return 0;
	}

	/* Mirrors getNonNegativeIntegerBounds() as the PHP value it returns:
	 * [min, max] or null */
	static zv::Val getNonNegativeIntegerBoundsValue(zval *type)
	{
		zend_long min = 0;
		NullableLong max = NullableLong::null();
		int bounds = getNonNegativeIntegerBounds(type, min, max);
		if (UNEXPECTED(bounds < 0)) return zv::Val();
		if (bounds == 0) return zv::Val::null();
		zv::Arr pair = zv::Arr::create(2);
		pair.push(zv::Val::integer(min));
		pair.push(max.toVal());
		return zv::Val(std::move(pair));
	}

	/* Mirrors computeBitwiseAndRange(): a Type, or PHP null */
	static zv::Val computeBitwiseAndRange(zval *leftNumberType, zval *rightNumberType)
	{
		zend_long leftMin = 0, rightMin = 0;
		NullableLong leftMax = NullableLong::null(), rightMax = NullableLong::null();
		int leftBounds = getNonNegativeIntegerBounds(leftNumberType, leftMin, leftMax);
		if (UNEXPECTED(leftBounds < 0)) return zv::Val();
		int rightBounds = getNonNegativeIntegerBounds(rightNumberType, rightMin, rightMax);
		if (UNEXPECTED(rightBounds < 0)) return zv::Val();
		if (leftBounds == 0 && rightBounds == 0) {
			return zv::Val::null();
		}

		bool anyMax = false;
		zend_long minOfMax = 0;
		if (leftBounds == 1 && !leftMax.isNull) {
			minOfMax = leftMax.value;
			anyMax = true;
		}
		if (rightBounds == 1 && !rightMax.isNull) {
			minOfMax = anyMax && minOfMax <= rightMax.value ? minOfMax : rightMax.value;
			anyMax = true;
		}

		return fromInterval(NullableLong::of(0), anyMax ? NullableLong::of(minOfMax) : NullableLong::null());
	}

	/* Mirrors computeBitwiseOrXorRange(): a Type, or PHP null */
	static zv::Val computeBitwiseOrXorRange(zval *leftNumberType, zval *rightNumberType)
	{
		zend_long leftMin = 0, rightMin = 0;
		NullableLong leftMax = NullableLong::null(), rightMax = NullableLong::null();
		int leftBounds = getNonNegativeIntegerBounds(leftNumberType, leftMin, leftMax);
		if (UNEXPECTED(leftBounds < 0)) return zv::Val();
		int rightBounds = getNonNegativeIntegerBounds(rightNumberType, rightMin, rightMax);
		if (UNEXPECTED(rightBounds < 0)) return zv::Val();
		if (leftBounds == 0 || rightBounds == 0) {
			return zv::Val::null();
		}
		if (leftMax.isNull || rightMax.isNull) {
			return fromInterval(NullableLong::of(0), NullableLong::null());
		}

		return fromInterval(NullableLong::of(0), NullableLong::of(allBitsMask(leftMax.value >= rightMax.value ? leftMax.value : rightMax.value)));
	}

	/* Mirrors allBitsMask(). */
	static zend_long allBitsMask(zend_long value)
	{
		value |= value >> 1;
		value |= value >> 2;
		value |= value >> 4;
		value |= value >> 8;
		value |= value >> 16;
		value |= value >> 32;

		return value;
	}

	/* }}} */

	/* {{{ resolveCommonMath() / integerRangeMath() */

	/* Mirrors resolveCommonMath() ($operator as `kind`). */
	static zv::Val resolveCommonMath(IetrBinaryKind kind, zval *leftType, zval *rightType)
	{
		IETR_VAL(types, union2(leftType, rightType));
		IETR_VAL(leftNumberType, callOn(leftType, mToNumber));
		IETR_VAL(rightNumberType, callOn(rightType, mToNumber));

		bool typesMixed = isA(types.raw(), pt_ce_mixed_type);
		if (
			!typesMixed
			&& (
				isA(rightNumberType.raw(), pt_ce_integer_range_type)
				|| isA(rightNumberType.raw(), pt_ce_constant_integer_type)
				|| isA(rightNumberType.raw(), pt_ce_union_type)
			)
		) {
			if (isA(leftNumberType.raw(), pt_ce_integer_range_type) || isA(leftNumberType.raw(), pt_ce_constant_integer_type)) {
				return integerRangeMath(leftNumberType.raw(), kind, rightNumberType.raw());
			} else if (isA(leftNumberType.raw(), pt_ce_union_type)) {
				IETR_VAL(leftTypes, callOn(leftNumberType.raw(), mGetTypes));
				if (UNEXPECTED(!requireArray(leftTypes.raw()))) return zv::Val();
				zv::Arr unionParts = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(leftTypes.raw())));

				for (zv::ArrayEntry entry : zv::ArrRef(leftTypes.raw())) {
					IETR_VAL(numberType, callOn(entry.value().raw(), mToNumber));
					if (isA(numberType.raw(), pt_ce_integer_range_type) || isA(numberType.raw(), pt_ce_constant_integer_type)) {
						IETR_VAL(part, integerRangeMath(numberType.raw(), kind, rightNumberType.raw()));
						unionParts.push(std::move(part));
					} else {
						unionParts.push(std::move(numberType));
					}
				}

				IETR_VAL(union_, unionOf(unionParts));
				if (isA(leftNumberType.raw(), pt_ce_benevolent_union_type)) {
					IETR_VAL(benevolent, toBenevolentUnion(union_.raw()));
					return callOn(benevolent.raw(), mToNumber);
				}

				return callOn(union_.raw(), mToNumber);
			}
		}

		{
			IETR_TRI(leftIsArray, triOn(leftType, mIsArray));
			if (leftIsArray == PT_TRI_YES) return newErrorType();
			IETR_TRI(rightIsArray, triOn(rightType, mIsArray));
			if (rightIsArray == PT_TRI_YES) return newErrorType();
			IETR_TRI(typesIsArray, triOn(types.raw(), mIsArray));
			if (typesIsArray == PT_TRI_YES) return newErrorType();
		}

		if (isA(leftNumberType.raw(), pt_ce_error_type) || isA(rightNumberType.raw(), pt_ce_error_type)) {
			return newErrorType();
		}
		if (isA(leftNumberType.raw(), pt_ce_never_type) || isA(rightNumberType.raw(), pt_ce_never_type)) {
			return getNeverType(leftNumberType.raw(), rightNumberType.raw());
		}

		{
			IETR_TRI(leftIsFloat, triOn(leftNumberType.raw(), mIsFloat));
			bool isFloat = leftIsFloat == PT_TRI_YES;
			if (!isFloat) {
				IETR_TRI(rightIsFloat, triOn(rightNumberType.raw(), mIsFloat));
				isFloat = rightIsFloat == PT_TRI_YES;
			}
			if (isFloat) {
				if (kind == IETR_SHIFT_LEFT || kind == IETR_SHIFT_RIGHT) {
					return newIntegerType();
				}
				return newFloatType();
			}
		}

		IETR_VAL(resultType, union2(leftNumberType.raw(), rightNumberType.raw()));
		if (kind == IETR_DIV) {
			bool benevolent = typesMixed;
			if (!benevolent) {
				IETR_TRI(resultIsInteger, triOn(resultType.raw(), mIsInteger));
				benevolent = resultIsInteger == PT_TRI_YES;
			}
			return newIntFloatUnion(benevolent);
		}

		if (typesMixed
			|| isA(leftType, pt_ce_benevolent_union_type)
			|| isA(rightType, pt_ce_benevolent_union_type)
		) {
			return toBenevolentUnion(resultType.raw());
		}

		return resultType;
	}

	/* Mirrors integerRangeMath() ($range a ConstantIntegerType or
	 * IntegerRangeType, $operator as `kind`). */
	static zv::Val integerRangeMath(zval *range, IetrBinaryKind kind, zval *operandIn)
	{
		zval rangeMin, rangeMax;
		if (isA(range, pt_ce_integer_range_type)) {
			NullableLong min, max;
			if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(range), min, max))) return zv::Val();
			setNullableLong(&rangeMin, min);
			setNullableLong(&rangeMax, max);
		} else {
			IETR_VAL(value, callOn(range, mGetValue));
			if (UNEXPECTED(Z_TYPE_P(value.raw()) != IS_LONG)) {
				// the twin's `$range->getValue()` of a ConstantIntegerType is always an int
				pt_throw_should_not_happen();
				return zv::Val();
			}
			ZVAL_LONG(&rangeMin, Z_LVAL_P(value.raw()));
			ZVAL_COPY_VALUE(&rangeMax, &rangeMin);
		}

		if (isA(operandIn, pt_ce_union_type)) {
			IETR_VAL(operandTypes, callOn(operandIn, mGetTypes));
			if (UNEXPECTED(!requireArray(operandTypes.raw()))) return zv::Val();
			zv::Arr unionParts = zv::Arr::create(zend_hash_num_elements(Z_ARRVAL_P(operandTypes.raw())));

			for (zv::ArrayEntry entry : zv::ArrRef(operandTypes.raw())) {
				zval *type = entry.value().raw();
				IETR_VAL(numberType, callOn(type, mToNumber));
				if (isA(numberType.raw(), pt_ce_integer_range_type) || isA(numberType.raw(), pt_ce_constant_integer_type)) {
					IETR_VAL(part, integerRangeMath(range, kind, numberType.raw()));
					unionParts.push(std::move(part));
				} else {
					IETR_VAL(part, callOn(type, mToNumber));
					unionParts.push(std::move(part));
				}
			}

			IETR_VAL(union_, unionOf(unionParts));
			if (isA(operandIn, pt_ce_benevolent_union_type)) {
				IETR_VAL(benevolent, toBenevolentUnion(union_.raw()));
				return callOn(benevolent.raw(), mToNumber);
			}

			return callOn(union_.raw(), mToNumber);
		}

		IETR_VAL(operand, callOn(operandIn, mToNumber));
		zval operandMin, operandMax;
		bool operandIsConstant;
		if (isA(operand.raw(), pt_ce_integer_range_type)) {
			NullableLong min, max;
			if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(operand.raw()), min, max))) return zv::Val();
			setNullableLong(&operandMin, min);
			setNullableLong(&operandMax, max);
			operandIsConstant = false;
		} else if (isA(operand.raw(), pt_ce_constant_integer_type)) {
			zend_long value;
			if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(operand.raw()), value))) return zv::Val();
			ZVAL_LONG(&operandMin, value);
			ZVAL_LONG(&operandMax, value);
			operandIsConstant = true;
		} else {
			return operand;
		}

		// $operand->getValue() of a ConstantIntegerType operand
		zval *operandValue = &operandMin;

		// the int|float|null results; floats and the INF sentinels are plain
		// doubles, so every zval here is scalar and needs no release
		zval min, max;
		ZVAL_NULL(&min);
		ZVAL_NULL(&max);

		zval zero;
		ZVAL_LONG(&zero, 0);

		switch (kind) {
			case IETR_PLUS:
				if (operandIsConstant) {
					if (Z_TYPE(rangeMin) != IS_NULL && UNEXPECTED(!scalarOp(add_function, &rangeMin, operandValue, &min))) return zv::Val();
					if (Z_TYPE(rangeMax) != IS_NULL && UNEXPECTED(!scalarOp(add_function, &rangeMax, operandValue, &max))) return zv::Val();
				} else {
					if (Z_TYPE(rangeMin) != IS_NULL && Z_TYPE(operandMin) != IS_NULL && UNEXPECTED(!scalarOp(add_function, &rangeMin, &operandMin, &min))) return zv::Val();
					if (Z_TYPE(rangeMax) != IS_NULL && Z_TYPE(operandMax) != IS_NULL && UNEXPECTED(!scalarOp(add_function, &rangeMax, &operandMax, &max))) return zv::Val();
				}
				break;
			case IETR_MINUS:
				if (operandIsConstant) {
					if (Z_TYPE(rangeMin) != IS_NULL && UNEXPECTED(!scalarOp(sub_function, &rangeMin, operandValue, &min))) return zv::Val();
					if (Z_TYPE(rangeMax) != IS_NULL && UNEXPECTED(!scalarOp(sub_function, &rangeMax, operandValue, &max))) return zv::Val();
				} else {
					if (identical(&rangeMin, &rangeMax) && Z_TYPE(rangeMin) != IS_NULL
						&& (Z_TYPE(operandMin) == IS_NULL || Z_TYPE(operandMax) == IS_NULL)) {
						ZVAL_NULL(&min);
						ZVAL_COPY_VALUE(&max, &rangeMin);
					} else {
						if (Z_TYPE(operandMin) == IS_NULL) {
							ZVAL_NULL(&min);
						} else if (Z_TYPE(rangeMin) != IS_NULL) {
							if (Z_TYPE(operandMax) != IS_NULL) {
								if (UNEXPECTED(!scalarOp(sub_function, &rangeMin, &operandMax, &min))) return zv::Val();
							} else {
								if (UNEXPECTED(!scalarOp(sub_function, &rangeMin, &operandMin, &min))) return zv::Val();
							}
						} else {
							ZVAL_NULL(&min);
						}

						if (Z_TYPE(operandMax) == IS_NULL) {
							ZVAL_NULL(&min);
							ZVAL_NULL(&max);
						} else if (Z_TYPE(rangeMax) != IS_NULL) {
							if (Z_TYPE(rangeMin) != IS_NULL && Z_TYPE(operandMin) == IS_NULL) {
								if (UNEXPECTED(!scalarOp(sub_function, &rangeMin, &operandMax, &min))) return zv::Val();
								ZVAL_NULL(&max);
							} else if (Z_TYPE(operandMin) != IS_NULL) {
								if (UNEXPECTED(!scalarOp(sub_function, &rangeMax, &operandMin, &max))) return zv::Val();
							} else {
								ZVAL_NULL(&max);
							}
						} else {
							ZVAL_NULL(&max);
						}

						if (Z_TYPE(min) != IS_NULL && Z_TYPE(max) != IS_NULL && greaterThan(&min, &max)) {
							zval swap;
							ZVAL_COPY_VALUE(&swap, &min);
							ZVAL_COPY_VALUE(&min, &max);
							ZVAL_COPY_VALUE(&max, &swap);
						}
					}
				}
				break;
			case IETR_MUL: {
				zval minusInf, inf;
				ZVAL_DOUBLE(&minusInf, -std::numeric_limits<double>::infinity());
				ZVAL_DOUBLE(&inf, std::numeric_limits<double>::infinity());
				zval min1, min2, max1, max2;
				if (UNEXPECTED(!mulOrZero(&rangeMin, &operandMin, &minusInf, &minusInf, &min1))) return zv::Val();
				if (UNEXPECTED(!mulOrZero(&rangeMin, &operandMax, &minusInf, &inf, &min2))) return zv::Val();
				if (UNEXPECTED(!mulOrZero(&rangeMax, &operandMin, &inf, &minusInf, &max1))) return zv::Val();
				if (UNEXPECTED(!mulOrZero(&rangeMax, &operandMax, &inf, &inf, &max2))) return zv::Val();

				zv::Args extremes{&min1, &min2, &max1, &max2};
				IETR_VAL(minValue, phpMinMax(false, 4, extremes));
				IETR_VAL(maxValue, phpMinMax(true, 4, extremes));
				ZVAL_COPY_VALUE(&min, minValue.raw());
				ZVAL_COPY_VALUE(&max, maxValue.raw());

				if (!std::isfinite(zval_get_double(&min))) {
					ZVAL_NULL(&min);
				}
				if (!std::isfinite(zval_get_double(&max))) {
					ZVAL_NULL(&max);
				}
				break;
			}
			case IETR_DIV: {
				if (operandIsConstant) {
					if (Z_TYPE(rangeMin) != IS_NULL && Z_LVAL_P(operandValue) != 0) {
						if (UNEXPECTED(!scalarOp(div_function, &rangeMin, operandValue, &min))) return zv::Val();
					}
					if (Z_TYPE(rangeMax) != IS_NULL && Z_LVAL_P(operandValue) != 0) {
						if (UNEXPECTED(!scalarOp(div_function, &rangeMax, operandValue, &max))) return zv::Val();
					}
				} else {
					// Avoid division by zero when looking for the min and the max by using the closest int
					if (identical(&operandMin, &zero)) {
						ZVAL_LONG(&operandMin, 1);
					}
					if (identical(&operandMax, &zero)) {
						ZVAL_LONG(&operandMax, -1);
					}

					if (
						(lessThan(&operandMin, &zero) || Z_TYPE(operandMin) == IS_NULL)
						&& (greaterThan(&operandMax, &zero) || Z_TYPE(operandMax) == IS_NULL)
					) {
						IETR_VAL(negativeOperand, fromIntervalZv(&operandMin, &zero));
						IETR_VAL(positiveOperand, fromIntervalZv(&zero, &operandMax));
						return splitDivision(range, kind, negativeOperand.raw(), positiveOperand.raw(), true);
					}
					if (
						(lessThan(&rangeMin, &zero) || Z_TYPE(rangeMin) == IS_NULL)
						&& (greaterThan(&rangeMax, &zero) || Z_TYPE(rangeMax) == IS_NULL)
					) {
						IETR_VAL(negativeRange, fromIntervalZv(&rangeMin, &zero));
						IETR_VAL(positiveRange, fromIntervalZv(&zero, &rangeMax));
						return splitDivision(operand.raw(), kind, negativeRange.raw(), positiveRange.raw(), false);
					}

					zval minusInf, inf;
					ZVAL_DOUBLE(&minusInf, -std::numeric_limits<double>::infinity());
					ZVAL_DOUBLE(&inf, std::numeric_limits<double>::infinity());
					zval *rangeMinOrInf = Z_TYPE(rangeMin) != IS_NULL ? &rangeMin : &minusInf;
					zval *rangeMaxOrInf = Z_TYPE(rangeMax) != IS_NULL ? &rangeMax : &inf;
					zval rangeMinSign, rangeMaxSign;
					ZVAL_LONG(&rangeMinSign, zend_compare(rangeMinOrInf, &zero));
					ZVAL_LONG(&rangeMaxSign, zend_compare(rangeMaxOrInf, &zero));

					zval negativeTenth, positiveTenth;
					ZVAL_DOUBLE(&negativeTenth, -0.1);
					ZVAL_DOUBLE(&positiveTenth, 0.1);
					zval min1, min2, max1, max2;
					if (UNEXPECTED(!scalarOp(Z_TYPE(operandMin) != IS_NULL ? div_function : mul_function, Z_TYPE(operandMin) != IS_NULL ? rangeMinOrInf : &rangeMinSign, Z_TYPE(operandMin) != IS_NULL ? &operandMin : &negativeTenth, &min1))) return zv::Val();
					if (UNEXPECTED(!scalarOp(Z_TYPE(operandMax) != IS_NULL ? div_function : mul_function, Z_TYPE(operandMax) != IS_NULL ? rangeMinOrInf : &rangeMinSign, Z_TYPE(operandMax) != IS_NULL ? &operandMax : &positiveTenth, &min2))) return zv::Val();
					if (UNEXPECTED(!scalarOp(Z_TYPE(operandMin) != IS_NULL ? div_function : mul_function, Z_TYPE(operandMin) != IS_NULL ? rangeMaxOrInf : &rangeMaxSign, Z_TYPE(operandMin) != IS_NULL ? &operandMin : &negativeTenth, &max1))) return zv::Val();
					if (UNEXPECTED(!scalarOp(Z_TYPE(operandMax) != IS_NULL ? div_function : mul_function, Z_TYPE(operandMax) != IS_NULL ? rangeMaxOrInf : &rangeMaxSign, Z_TYPE(operandMax) != IS_NULL ? &operandMax : &positiveTenth, &max2))) return zv::Val();

					zv::Args extremes{&min1, &min2, &max1, &max2};
					IETR_VAL(minValue, phpMinMax(false, 4, extremes));
					IETR_VAL(maxValue, phpMinMax(true, 4, extremes));
					ZVAL_COPY_VALUE(&min, minValue.raw());
					ZVAL_COPY_VALUE(&max, maxValue.raw());

					if (Z_TYPE(min) == IS_DOUBLE && Z_DVAL(min) == -std::numeric_limits<double>::infinity()) {
						ZVAL_NULL(&min);
					}
					if (Z_TYPE(max) == IS_DOUBLE && Z_DVAL(max) == std::numeric_limits<double>::infinity()) {
						ZVAL_NULL(&max);
					}
				}

				if (Z_TYPE(min) != IS_NULL && Z_TYPE(max) != IS_NULL && greaterThan(&min, &max)) {
					zval swap;
					ZVAL_COPY_VALUE(&swap, &min);
					ZVAL_COPY_VALUE(&min, &max);
					ZVAL_COPY_VALUE(&max, &swap);
				}

				if (Z_TYPE(min) == IS_DOUBLE) {
					toIntBound(std::ceil(Z_DVAL(min)), &min);
				}
				if (Z_TYPE(max) == IS_DOUBLE) {
					toIntBound(std::floor(Z_DVAL(max)), &max);
				}

				// invert maximas on division with negative constants
				bool rangeNegativeConstant = false;
				if (isA(range, pt_ce_constant_integer_type)) {
					zend_long value;
					if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(range), value))) return zv::Val();
					rangeNegativeConstant = value < 0;
				}
				if ((rangeNegativeConstant || (operandIsConstant && Z_LVAL_P(operandValue) < 0))
					&& (Z_TYPE(min) == IS_NULL || Z_TYPE(max) == IS_NULL)) {
					zval swap;
					ZVAL_COPY_VALUE(&swap, &min);
					ZVAL_COPY_VALUE(&min, &max);
					ZVAL_COPY_VALUE(&max, &swap);
				}

				if (Z_TYPE(min) == IS_NULL && Z_TYPE(max) == IS_NULL) {
					return newIntFloatUnion(true);
				}

				IETR_VAL(rangeType, fromIntervalZv(&min, &max));
				IETR_VAL(floatType, newFloatType());
				return union2(rangeType.raw(), floatType.raw());
			}
			case IETR_SHIFT_LEFT:
			case IETR_SHIFT_RIGHT: {
				if (!operandIsConstant) {
					return newIntegerType();
				}
				if (Z_LVAL_P(operandValue) < 0) {
					return newErrorType();
				}
				if (kind == IETR_SHIFT_LEFT) {
					// an overflowing shift wraps around, which breaks the monotonicity the bounds rely on
					// open bounds still include the platform's minimum or maximum integer
					bool minOverflows = false;
					bool maxOverflows = false;
					if (UNEXPECTED(!shiftLeftOverflows(Z_TYPE(rangeMin) == IS_NULL ? ZEND_LONG_MIN : zval_get_long(&rangeMin), operandValue, minOverflows))) return zv::Val();
					if (!minOverflows && UNEXPECTED(!shiftLeftOverflows(Z_TYPE(rangeMax) == IS_NULL ? ZEND_LONG_MAX : zval_get_long(&rangeMax), operandValue, maxOverflows))) return zv::Val();
					if (minOverflows || maxOverflows) {
						return newIntegerType();
					}
				}
				pt_ietr_binary_op shift = kind == IETR_SHIFT_LEFT ? shift_left_function : shift_right_function;
				if (Z_TYPE(rangeMin) != IS_NULL) {
					zval rangeMinInteger;
					ZVAL_LONG(&rangeMinInteger, zval_get_long(&rangeMin));
					if (UNEXPECTED(!scalarOp(shift, &rangeMinInteger, operandValue, &min))) return zv::Val();
				}
				if (Z_TYPE(rangeMax) != IS_NULL) {
					zval rangeMaxInteger;
					ZVAL_LONG(&rangeMaxInteger, zval_get_long(&rangeMax));
					if (UNEXPECTED(!scalarOp(shift, &rangeMaxInteger, operandValue, &max))) return zv::Val();
				}
				break;
			}
			default:
				pt_throw_should_not_happen();
				return zv::Val();
		}

		if (Z_TYPE(min) == IS_DOUBLE) {
			ZVAL_NULL(&min);
		}
		if (Z_TYPE(max) == IS_DOUBLE) {
			ZVAL_NULL(&max);
		}

		return fromIntervalZv(&min, &max);
	}

	/* the Div arm's split around zero: TypeCombinator::union(
	 * integerRangeMath(fixed, negative), integerRangeMath(fixed, positive)
	 * )->toNumber() and the int|float -> benevolent rewrite; the split side
	 * is the operand (operandSplit) or the range */
	static zv::Val splitDivision(zval *fixed, IetrBinaryKind kind, zval *negative, zval *positive, bool operandSplit)
	{
		IETR_VAL(negativeResult, operandSplit ? integerRangeMath(fixed, kind, negative) : integerRangeMath(negative, kind, fixed));
		IETR_VAL(positiveResult, operandSplit ? integerRangeMath(fixed, kind, positive) : integerRangeMath(positive, kind, fixed));
		IETR_VAL(union_, union2(negativeResult.raw(), positiveResult.raw()));
		IETR_VAL(result, callOn(union_.raw(), mToNumber));

		IETR_VAL(intFloat, newIntFloatUnion(false));
		IETR_VAL(equals, callOn(result.raw(), mEquals, 1, intFloat.raw()));
		if (zend_is_true(equals.raw())) {
			return newIntFloatUnion(true);
		}

		return result;
	}

	/* }}} */

	/* {{{ negate() */

	/* Mirrors negateNumber(). */
	static zv::Val negateNumber(zval *typeIn)
	{
		IETR_VAL(type, callOn(typeIn, mToNumber));
		IETR_VAL(scalarValues, callOn(type.raw(), mGetConstantScalarValues));
		if (UNEXPECTED(!requireCountable(scalarValues.raw()))) return zv::Val();

		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(scalarValues.raw()));
		if (count > 0) {
			zv::Arr newTypes = zv::Arr::create(count);
			for (zv::ArrayEntry entry : zv::ArrRef(scalarValues.raw())) {
				zval *scalarValue = entry.value().raw();
				ZVAL_DEREF(scalarValue);
				if (Z_TYPE_P(scalarValue) == IS_LONG) {
					zval minusOne, newValue;
					ZVAL_LONG(&minusOne, -1);
					if (UNEXPECTED(!scalarOp(mul_function, scalarValue, &minusOne, &newValue))) return zv::Val();
					if (Z_TYPE(newValue) != IS_LONG) {
						// Negating the smallest integer overflows into a float.
						IETR_VAL(floatType, newConstantFloatType(zval_get_double(&newValue)));
						newTypes.push(std::move(floatType));
						continue;
					}
					IETR_VAL(integerType, newConstantIntegerType(Z_LVAL(newValue)));
					newTypes.push(std::move(integerType));
				} else if (Z_TYPE_P(scalarValue) == IS_DOUBLE) {
					zval minusOne, newValue;
					ZVAL_LONG(&minusOne, -1);
					if (UNEXPECTED(!scalarOp(mul_function, scalarValue, &minusOne, &newValue))) return zv::Val();
					IETR_VAL(floatType, newConstantFloatType(Z_DVAL(newValue)));
					newTypes.push(std::move(floatType));
				}
			}

			return unionOf(newTypes);
		}

		return type;
	}

	/* Mirrors negate(). */
	static zv::Val negate(zval *type)
	{
		IETR_VAL(negated, negateNumber(type));
		if (isA(negated.raw(), pt_ce_integer_range_type)) {
			IETR_VAL(minusOne, newConstantIntegerType(-1));
			return subtractOrMultiply(IETR_MUL, type, minusOne.raw());
		}

		return negated;
	}

	/* }}} */

	/* {{{ small private helpers of the twin */

	/* Mirrors getNeverType(). */
	static zv::Val getNeverType(zval *leftType, zval *rightType)
	{
		// make sure we don't lose the explicit flag in the process
		if (isA(leftType, pt_ce_never_type)) {
			bool isExplicit;
			if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(leftType), isExplicit))) return zv::Val();
			if (isExplicit) return zv::Val::copyOf(zv::Ref(leftType));
		}
		if (isA(rightType, pt_ce_never_type)) {
			bool isExplicit;
			if (UNEXPECTED(!pt_never_type_is_explicit(Z_OBJ_P(rightType), isExplicit))) return zv::Val();
			if (isExplicit) return zv::Val::copyOf(zv::Ref(rightType));
		}
		return newNeverType();
	}

	/* Mirrors getTypeFromValue(). */
	static zv::Val getTypeFromValue(zval *value)
	{
		return typeFromValue(value);
	}

	/* }}} */

	/* private static: a left shift that does not fit into an integer wraps
	 * around instead of turning into a float, so the shifted value no longer
	 * preserves the ordering of its operand — `($value << $shift) >> $shift
	 * !== $value` through the engine's operators; false = pending exception */
	[[nodiscard]] static bool shiftLeftOverflows(zend_long value, zval *shift, bool &out)
	{
		zval operand, shifted, restored;
		ZVAL_LONG(&operand, value);
		if (UNEXPECTED(!scalarOp(shift_left_function, &operand, shift, &shifted))) return false;
		if (UNEXPECTED(!scalarOp(shift_right_function, &shifted, shift, &restored))) return false;
		out = Z_TYPE(restored) != IS_LONG || Z_LVAL(restored) != value;
		return true;
	}

	/* {{{ intDivide() */

	/* an interval bound of intDivide(): an int, or the twin's -INF / INF
	 * (inf -1 / 1) for an unbounded end */
	struct Bound
	{
		int inf;
		zend_long value;

		static Bound of(zend_long value) { return { 0, value }; }
		static Bound infinity(int sign) { return { sign, 0 }; }

		bool lessThan(const Bound &other) const
		{
			if (inf != other.inf) return inf < other.inf;
			return inf == 0 && value < other.value;
		}

		/* the int|float the twin's private methods trade */
		void toZval(zval *out) const
		{
			if (inf != 0) {
				ZVAL_DOUBLE(out, inf * std::numeric_limits<double>::infinity());
			} else {
				ZVAL_LONG(out, value);
			}
		}

		/* an int|float parameter of the twin's private methods; false =
		 * TypeError thrown */
		[[nodiscard]] static bool fromZval(zval *value, uint32_t arg, Bound &out)
		{
			if (Z_TYPE_P(value) == IS_LONG) {
				out = of(Z_LVAL_P(value));
				return true;
			}
			if (Z_TYPE_P(value) == IS_DOUBLE && std::isinf(Z_DVAL_P(value))) {
				out = infinity(Z_DVAL_P(value) > 0 ? 1 : -1);
				return true;
			}
			if (Z_TYPE_P(value) == IS_DOUBLE && Z_DVAL_P(value) == std::trunc(Z_DVAL_P(value)) && Z_DVAL_P(value) >= (double) ZEND_LONG_MIN && Z_DVAL_P(value) < (double) ZEND_LONG_MAX) {
				out = of((zend_long) Z_DVAL_P(value));
				return true;
			}
			zend_argument_type_error(arg, "must be of type int|float, %s given", zend_zval_value_name(value));
			return false;
		}
	};

	struct Interval
	{
		Bound min;
		Bound max;
	};

	/* Mirrors intDivide(). */
	static zv::Val intDivide(zval *numeratorType, zval *divisorType)
	{
		std::vector<Interval> numeratorIntervals;
		std::vector<Interval> divisorIntervals;
		bool numeratorKnown, divisorKnown;
		{
			IETR_VAL(numeratorInteger, callOn(numeratorType, mToInteger));
			if (UNEXPECTED(!getIntervals(numeratorInteger.raw(), numeratorIntervals, numeratorKnown))) return zv::Val();
			IETR_VAL(divisorInteger, callOn(divisorType, mToInteger));
			if (UNEXPECTED(!getIntervals(divisorInteger.raw(), divisorIntervals, divisorKnown))) return zv::Val();
		}
		if (!numeratorKnown || !divisorKnown) {
			return newErrorType();
		}

		zv::Arr resultTypes = zv::Arr::create((uint32_t) (numeratorIntervals.size() * divisorIntervals.size() * 2));
		for (const Interval &numerator : numeratorIntervals) {
			for (const Interval &divisor : divisorIntervals) {
				if (divisor.min.lessThan(Bound::of(0))) {
					Bound minusOne = Bound::of(-1);
					IETR_VAL(negative, divideIntervals(numerator.min, numerator.max, divisor.min, minusOne.lessThan(divisor.max) ? minusOne : divisor.max));
					resultTypes.push(std::move(negative));
				}
				if (!Bound::of(0).lessThan(divisor.max)) {
					continue;
				}

				Bound one = Bound::of(1);
				IETR_VAL(positive, divideIntervals(numerator.min, numerator.max, divisor.min.lessThan(one) ? one : divisor.min, divisor.max));
				resultTypes.push(std::move(positive));
			}
		}

		if (zend_hash_num_elements(resultTypes.table()) == 0) {
			return newErrorType();
		}

		return unionOf(resultTypes);
	}

	/* Mirrors getIntervals(): `known` false for the twin's null; false =
	 * pending exception */
	[[nodiscard]] static bool getIntervals(zval *type, std::vector<Interval> &intervals, bool &known)
	{
		known = false;
		zv::Val typesHold;
		zval single;
		HashTable *innerTypes;
		if (isA(type, pt_ce_union_type)) {
			typesHold = callOn(type, mGetTypes);
			if (UNEXPECTED(typesHold.isUndef() || !requireArray(typesHold.raw()))) return false;
			innerTypes = Z_ARRVAL_P(typesHold.raw());
		} else {
			innerTypes = NULL;
			ZVAL_COPY_VALUE(&single, type);
		}

		auto visit = [&](zval *innerType, bool &ok) -> bool {
			ok = true;
			if (isA(innerType, pt_ce_constant_integer_type)) {
				zend_long value;
				if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(innerType), value))) return false;
				intervals.push_back({ Bound::of(value), Bound::of(value) });
				return true;
			}
			if (isA(innerType, pt_ce_integer_range_type)) {
				NullableLong min, max;
				if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(innerType), min, max))) return false;
				intervals.push_back({ min.isNull ? Bound::infinity(-1) : Bound::of(min.value), max.isNull ? Bound::infinity(1) : Bound::of(max.value) });
				return true;
			}
			zend_long isInteger = triOn(innerType, mIsInteger);
			if (UNEXPECTED(isInteger < 0)) return false;
			if (isInteger == PT_TRI_YES) {
				intervals.push_back({ Bound::infinity(-1), Bound::infinity(1) });
				return true;
			}
			ok = false;
			return true;
		};

		if (innerTypes == NULL) {
			bool ok;
			if (UNEXPECTED(!visit(&single, ok))) return false;
			if (!ok) return true;
		} else {
			for (zv::ArrayEntry entry : zv::TableRef(innerTypes)) {
				bool ok;
				if (UNEXPECTED(!visit(entry.value().deref().raw(), ok))) return false;
				if (!ok) return true;
			}
		}

		known = true;
		return true;
	}

	/* getIntervals() as the private method returns it: a list of [min, max]
	 * pairs or null; UNDEF = pending exception */
	static zv::Val getIntervalsValue(zval *type)
	{
		std::vector<Interval> intervals;
		bool known;
		if (UNEXPECTED(!getIntervals(type, intervals, known))) return zv::Val();
		if (!known) return zv::Val::null();
		zv::Arr list = zv::Arr::create((uint32_t) intervals.size());
		for (const Interval &interval : intervals) {
			zval min, max;
			interval.min.toZval(&min);
			interval.max.toZval(&max);
			zv::Arr pair = zv::Arr::create(2);
			pair.push(zv::Val::adopt(min));
			pair.push(zv::Val::adopt(max));
			list.push(zv::Val(std::move(pair)));
		}
		return zv::Val(std::move(list));
	}

	/* Mirrors divideIntervals(). */
	static zv::Val divideIntervals(Bound numeratorMin, Bound numeratorMax, Bound divisorMin, Bound divisorMax)
	{
		Bound results[4] = {
			divideBounds(numeratorMin, divisorMin),
			divideBounds(numeratorMin, divisorMax),
			divideBounds(numeratorMax, divisorMin),
			divideBounds(numeratorMax, divisorMax),
		};
		Bound min = results[0];
		Bound max = results[0];
		for (const Bound &result : results) {
			if (result.lessThan(min)) min = result;
			if (max.lessThan(result)) max = result;
		}

		return fromInterval(
			min.inf != 0 ? NullableLong::null() : NullableLong::of(min.value),
			max.inf != 0 ? NullableLong::null() : NullableLong::of(max.value)
		);
	}

	/* Mirrors divideBounds(). */
	static Bound divideBounds(Bound numerator, Bound divisor)
	{
		if (numerator.inf != 0) {
			if (divisor.inf != 0) {
				// the range already contains 0 through a neighbouring corner
				return Bound::of(0);
			}

			return (numerator.inf > 0) == (divisor.value > 0) ? Bound::infinity(1) : Bound::infinity(-1);
		}

		if (divisor.inf != 0) {
			return Bound::of(0);
		}

		if (numerator.value == ZEND_LONG_MIN && divisor.value == -1) {
			// throws ArithmeticError, the closest valid result is intdiv(PHP_INT_MIN + 1, -1)
			return Bound::of(ZEND_LONG_MAX);
		}

		return Bound::of(numerator.value / divisor.value);
	}

	/* }}} */

};

} // namespace phpstanturbo

using phpstanturbo::OperatorTypeHelper;
using phpstanturbo::NullableLong;

/* {{{ direct entries */

zv::Val pt_operator_type_helper_binary(pt_operator_type_op op, zval *left, zval *right, zval *phpVersion)
{
	(void) phpVersion;
	switch (op) {
		case PT_OPERATOR_ADD:
			return OperatorTypeHelper::add(left, right);
		case PT_OPERATOR_SUBTRACT:
			return OperatorTypeHelper::subtractOrMultiply(IETR_MINUS, left, right);
		case PT_OPERATOR_MULTIPLY:
			return OperatorTypeHelper::subtractOrMultiply(IETR_MUL, left, right);
		case PT_OPERATOR_DIVIDE:
			return OperatorTypeHelper::divide(left, right);
		case PT_OPERATOR_INT_DIVIDE:
			return OperatorTypeHelper::intDivide(left, right);
		case PT_OPERATOR_MODULO:
			return OperatorTypeHelper::modulo(left, right);
		case PT_OPERATOR_BITWISE_AND:
			return OperatorTypeHelper::bitwise(IETR_BITWISE_AND, left, right);
		case PT_OPERATOR_BITWISE_OR:
			return OperatorTypeHelper::bitwise(IETR_BITWISE_OR, left, right);
		case PT_OPERATOR_BITWISE_XOR:
			return OperatorTypeHelper::bitwise(IETR_BITWISE_XOR, left, right);
		case PT_OPERATOR_SHIFT_LEFT:
			return OperatorTypeHelper::shift(IETR_SHIFT_LEFT, left, right);
		case PT_OPERATOR_SHIFT_RIGHT:
			return OperatorTypeHelper::shift(IETR_SHIFT_RIGHT, left, right);
		case PT_OPERATOR_CONCATENATE:
			return OperatorTypeHelper::concat(left, right);
		case PT_OPERATOR_ARRAY_UNION:
			return OperatorTypeHelper::arrayUnion(left, right);
	}
	pt_throw_should_not_happen();
	return zv::Val();
}

zv::Val pt_operator_type_helper_negate(zval *type, zval *phpVersion)
{
	(void) phpVersion;
	return OperatorTypeHelper::negate(type);
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

/* the operator string of resolveCommonMath() / integerRangeMath(); false =
 * the ValueError of an operator the twin does not handle */
bool otOperatorKind(zend_string *operator_, IetrBinaryKind &kind)
{
	static constexpr struct
	{
		const char *operator_;
		IetrBinaryKind kind;
	} operators[] = {
		{ "+", IETR_PLUS },
		{ "-", IETR_MINUS },
		{ "*", IETR_MUL },
		{ "/", IETR_DIV },
		{ "<<", IETR_SHIFT_LEFT },
		{ ">>", IETR_SHIFT_RIGHT },
	};
	for (const auto &candidate : operators) {
		if (zend_string_equals_cstr(operator_, candidate.operator_, strlen(candidate.operator_))) {
			kind = candidate.kind;
			return true;
		}
	}
	kind = IETR_OTHER;
	return true;
}

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
	cls.privateClassConstantLong("IS_SCALAR_TYPE", PT_IETR_IS_SCALAR_TYPE);
	cls.privateClassConstantLong("IS_UNKNOWN", PT_IETR_IS_UNKNOWN);
	ptdecl::OperatorTypeHelper::declareProperties(cls);

	cls.method(sigs::concat, OT_BINARY_GLUE(OperatorTypeHelper::concat(leftType, rightType)));
	cls.method(sigs::bitwiseAnd, OT_BINARY_GLUE(OperatorTypeHelper::bitwise(IETR_BITWISE_AND, leftType, rightType)));
	cls.method(sigs::bitwiseOr, OT_BINARY_GLUE(OperatorTypeHelper::bitwise(IETR_BITWISE_OR, leftType, rightType)));
	cls.method(sigs::bitwiseXor, OT_BINARY_GLUE(OperatorTypeHelper::bitwise(IETR_BITWISE_XOR, leftType, rightType)));

	cls.method(sigs::getFiniteOrConstantScalarTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *leftType, *rightType, *operationCallable;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Zval>(execute_data, leftType, rightType, operationCallable)) RETURN_THROWS();
		if (UNEXPECTED(!otArgType(leftType, 1) || !otArgType(rightType, 2))) RETURN_THROWS();
		if (UNEXPECTED(!zend_is_callable(operationCallable, 0, NULL))) {
			zend_argument_type_error(3, "must be of type callable, %s given", zend_zval_value_name(operationCallable));
			RETURN_THROWS();
		}
		zv::Val result;
		zend_long code = OperatorTypeHelper::getFiniteOrConstantScalarTypesWith(leftType, rightType, NULL, operationCallable, result);
		if (UNEXPECTED(code < 0)) RETURN_THROWS();
		if (code == 0) PT_RETURN_VAL(std::move(result));
		RETURN_LONG(code);
	});

	cls.method(sigs::divide, OT_BINARY_GLUE(OperatorTypeHelper::divide(leftType, rightType)));
	cls.method(sigs::getDivTypeFromTypes, OT_TYPES_GLUE(OperatorTypeHelper::getDivTypeFromTypes(leftType, rightType)));
	cls.method(sigs::modulo, OT_BINARY_GLUE(OperatorTypeHelper::modulo(leftType, rightType)));

	cls.method(sigs::getMaxModuloMagnitude, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long divisorMin, divisorMax;
		bool divisorMinIsNull, divisorMaxIsNull;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_LONG_OR_NULL(divisorMin, divisorMinIsNull)
			Z_PARAM_LONG_OR_NULL(divisorMax, divisorMaxIsNull)
		ZEND_PARSE_PARAMETERS_END();
		NullableLong magnitude = OperatorTypeHelper::getMaxModuloMagnitude(
			divisorMinIsNull ? NullableLong::null() : NullableLong::of(divisorMin),
			divisorMaxIsNull ? NullableLong::null() : NullableLong::of(divisorMax));
		PT_RETURN_VAL(magnitude.toVal());
	});

	cls.method(sigs::getIntegerBounds, OT_TYPE_GLUE(OperatorTypeHelper::getIntegerBoundsValue(type)));
	cls.method(sigs::add, OT_BINARY_GLUE(OperatorTypeHelper::add(leftType, rightType)));
	cls.method(sigs::arrayUnion, OT_BINARY_GLUE(OperatorTypeHelper::arrayUnion(leftType, rightType)));
	cls.method(sigs::tryArrayUnion, OT_TYPES_GLUE(OperatorTypeHelper::tryArrayUnion(leftType, rightType)));
	cls.method(sigs::subtract, OT_BINARY_GLUE(OperatorTypeHelper::subtractOrMultiply(IETR_MINUS, leftType, rightType)));
	cls.method(sigs::multiply, OT_BINARY_GLUE(OperatorTypeHelper::subtractOrMultiply(IETR_MUL, leftType, rightType)));
	cls.method(sigs::shiftLeft, OT_BINARY_GLUE(OperatorTypeHelper::shift(IETR_SHIFT_LEFT, leftType, rightType)));
	cls.method(sigs::shiftRight, OT_BINARY_GLUE(OperatorTypeHelper::shift(IETR_SHIFT_RIGHT, leftType, rightType)));
	cls.method(sigs::optimizeScalarType, OT_TYPE_GLUE(OperatorTypeHelper::optimizeScalarType(type)));
	cls.method(sigs::getNonNegativeIntegerBounds, OT_TYPE_GLUE(OperatorTypeHelper::getNonNegativeIntegerBoundsValue(type)));
	cls.method(sigs::computeBitwiseAndRange, OT_TYPES_GLUE(OperatorTypeHelper::computeBitwiseAndRange(leftType, rightType)));
	cls.method(sigs::computeBitwiseOrXorRange, OT_TYPES_GLUE(OperatorTypeHelper::computeBitwiseOrXorRange(leftType, rightType)));

	cls.method(sigs::allBitsMask, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value;
		if (!zp::parse<zp::Long>(execute_data, value)) RETURN_THROWS();
		RETURN_LONG(OperatorTypeHelper::allBitsMask(value));
	});

	cls.method(sigs::resolveCommonMath, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_string *operator_;
		zval *leftType, *rightType;
		if (!zp::parse<zp::Str, zp::Obj, zp::Obj>(execute_data, operator_, leftType, rightType)) RETURN_THROWS();
		if (UNEXPECTED(!otArgType(leftType, 2) || !otArgType(rightType, 3))) RETURN_THROWS();
		IetrBinaryKind kind;
		if (UNEXPECTED(!otOperatorKind(operator_, kind))) RETURN_THROWS();
		PT_RETURN_VAL(OperatorTypeHelper::resolveCommonMath(kind, leftType, rightType));
	});

	cls.method(sigs::integerRangeMath, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *range, *operand;
		zend_string *operator_;
		if (!zp::parse<zp::Obj, zp::Str, zp::Obj>(execute_data, range, operator_, operand)) RETURN_THROWS();
		if (UNEXPECTED(!otArgType(range, 1) || !otArgType(operand, 3))) RETURN_THROWS();
		IetrBinaryKind kind;
		if (UNEXPECTED(!otOperatorKind(operator_, kind))) RETURN_THROWS();
		PT_RETURN_VAL(OperatorTypeHelper::integerRangeMath(range, kind, operand));
	});

	cls.method(sigs::shiftLeftOverflows, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_long value, shift;
		if (!zp::parse<zp::Long, zp::Long>(execute_data, value, shift)) RETURN_THROWS();
		bool out = false;
		if (UNEXPECTED(!OperatorTypeHelper::shiftLeftOverflowsValue(value, shift, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::negateNumber, OT_TYPE_GLUE(OperatorTypeHelper::negateNumber(type)));

	cls.method(sigs::toIntBound, [](INTERNAL_FUNCTION_PARAMETERS) {
		double value;
		if (!zp::parse<zp::Double>(execute_data, value)) RETURN_THROWS();
		OperatorTypeHelper::toIntBound(value, return_value);
	});

	cls.method(sigs::getTypeFromValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *value;
		if (!zp::parse<zp::Zval>(execute_data, value)) RETURN_THROWS();
		PT_RETURN_VAL(OperatorTypeHelper::getTypeFromValue(value));
	});

	cls.method(sigs::getNeverType, OT_TYPES_GLUE(OperatorTypeHelper::getNeverType(leftType, rightType)));

	cls.method(sigs::toIntegerOperand, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		zend_class_entry *ce = pt_class(PT_CLASS_CONSTANT_SCALAR_TYPE);
		if (UNEXPECTED(ce == NULL)) RETURN_THROWS();
		if (UNEXPECTED(!instanceof_function(Z_OBJCE_P(type), ce))) {
			zend_argument_type_error(1, "must be of type PHPStan\\Type\\ConstantScalarType, %s given", zend_zval_value_name(type));
			RETURN_THROWS();
		}
		zval integer;
		if (UNEXPECTED(!OperatorTypeHelper::toIntegerOperand(type, integer))) RETURN_THROWS();
		RETURN_LONG(Z_LVAL(integer));
	});

	cls.method(sigs::negate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *phpVersion;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, type, phpVersion)) RETURN_THROWS();
		if (UNEXPECTED(!otArgType(type, 1) || !otArgPhpVersion(phpVersion, 2))) RETURN_THROWS();
		PT_RETURN_VAL(OperatorTypeHelper::negate(type));
	});

	cls.method(sigs::intDivide, OT_BINARY_GLUE(OperatorTypeHelper::intDivide(leftType, rightType)));
	cls.method(sigs::getIntervals, OT_TYPE_GLUE(OperatorTypeHelper::getIntervalsValue(type)));

	cls.method(sigs::divideIntervals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *numeratorMin, *numeratorMax, *divisorMin, *divisorMax;
		if (!zp::parse<zp::Zval, zp::Zval, zp::Zval, zp::Zval>(execute_data, numeratorMin, numeratorMax, divisorMin, divisorMax)) RETURN_THROWS();
		OperatorTypeHelper::Bound bounds[4];
		zval *values[4] = { numeratorMin, numeratorMax, divisorMin, divisorMax };
		for (uint32_t i = 0; i < 4; i++) {
			if (UNEXPECTED(!OperatorTypeHelper::Bound::fromZval(values[i], i + 1, bounds[i]))) RETURN_THROWS();
		}
		PT_RETURN_VAL(OperatorTypeHelper::divideIntervals(bounds[0], bounds[1], bounds[2], bounds[3]));
	});

	cls.method(sigs::divideBounds, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *numerator, *divisor;
		if (!zp::parse<zp::Zval, zp::Zval>(execute_data, numerator, divisor)) RETURN_THROWS();
		OperatorTypeHelper::Bound numeratorBound, divisorBound;
		if (UNEXPECTED(!OperatorTypeHelper::Bound::fromZval(numerator, 1, numeratorBound) || !OperatorTypeHelper::Bound::fromZval(divisor, 2, divisorBound))) RETURN_THROWS();
		OperatorTypeHelper::divideBounds(numeratorBound, divisorBound).toZval(return_value);
	});

	cls.shadow(&pt_ce_operator_type_helper);
}

#undef OT_BINARY_GLUE
#undef OT_TYPES_GLUE
#undef OT_TYPE_GLUE

/* }}} */
