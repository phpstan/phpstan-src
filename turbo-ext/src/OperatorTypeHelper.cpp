/*
 * PHPStanTurbo\OperatorTypeHelper — native implementation of
 * PHPStan\Type\OperatorTypeHelper: the array + operator (Type::arrayUnion())
 * OperatorTypeTrait delegates to (pt_type_trait_operator() in TypeTraits.cpp
 * calls pt_operator_type_helper_binary() directly). The other operators are
 * computed on ValueSet, NumberSet and StringSet.
 *
 * A final class of static methods: every method mirrors its twin, the
 * private ones are registered too so the method sets stay identical.
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

	cls.method(sigs::arrayUnion, OT_BINARY_GLUE(OperatorTypeHelper::arrayUnion(leftType, rightType)));
	cls.method(sigs::tryArrayUnion, OT_TYPES_GLUE(OperatorTypeHelper::tryArrayUnion(leftType, rightType)));

	cls.shadow(&pt_ce_operator_type_helper);
}

#undef OT_BINARY_GLUE
#undef OT_TYPES_GLUE
#undef OT_TYPE_GLUE

/* }}} */
