/*
 * PHPStanTurbo\ConstantTypeHelper — native implementation of
 * PHPStan\Type\ConstantTypeHelper.
 *
 * Declared as PHPStan\Type\ConstantTypeHelper itself at activation: final,
 * one static factory. getTypeFromValue() maps a PHP value to its constant
 * type the way the twin's is_*() chain does: the scalars and null to the
 * shadowed constant types, an array through the shadowed
 * ConstantArrayTypeBuilder (direct C++ calls, no engine frames) or, when
 * larger than ARRAY_COUNT_LIMIT, to a generalized oversized array, an enum
 * case to EnumCaseObjectType, any other object to ObjectType, anything else
 * (a resource) to MixedType.
 *
 * The logic lives in the ConstantTypeHelper handle class below, mirroring
 * src/Type/ConstantTypeHelper.php; the registration at the bottom is only
 * the engine ABI glue.
 */

#include "TypeTraits.h"
#include "generated/ConstantTypeHelper.h"

namespace sigs = ptdecl::ConstantTypeHelper::sig;

zend_class_entry *pt_ce_constant_type_helper = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\ConstantTypeHelper. */
class ConstantTypeHelper
{
public:
	/* the constant type of $value; UNDEF = pending exception */
	static zv::Val getTypeFromValue(zval *value)
	{
		ZVAL_DEREF(value);
		zval raw;
		switch (Z_TYPE_P(value)) {
			case IS_LONG:
				return adopted(pt_constant_integer_type_new(&raw, Z_LVAL_P(value)), raw);
			case IS_DOUBLE:
				return adopted(pt_constant_float_type_new(&raw, Z_DVAL_P(value)), raw);
			case IS_TRUE:
			case IS_FALSE:
				return adopted(pt_constant_boolean_type_new(&raw, Z_TYPE_P(value) == IS_TRUE), raw);
			case IS_NULL:
				return adopted(pt_null_type_new(&raw), raw);
			case IS_STRING:
				return adopted(pt_constant_string_type_new(&raw, Z_STR_P(value)), raw);
			case IS_ARRAY:
				return arrayType(value);
			case IS_OBJECT:
				return objectType(Z_OBJ_P(value));
			default:
				return adopted(pt_mixed_type_new(&raw), raw);
		}
	}

private:
	static zv::Val adopted(bool created, zval &raw)
	{
		if (UNEXPECTED(!created)) return zv::Val();
		return zv::Val::adopt(raw);
	}

	/* the key type of an array entry */
	static zv::Val keyTypeOf(zv::ArrayEntry &entry)
	{
		zval key;
		zend_string *stringKey = entry.stringKeyOrNull();
		if (stringKey != NULL) {
			ZVAL_STR(&key, stringKey);
		} else {
			ZVAL_LONG(&key, (zend_long) entry.indexKey());
		}
		return getTypeFromValue(&key);
	}

	/* the builder over every key/value pair; getOversizedArrayType() when
	 * the array is larger than ARRAY_COUNT_LIMIT */
	static zv::Val arrayType(zval *value)
	{
		if (zend_hash_num_elements(Z_ARRVAL_P(value)) > PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT) {
			return getOversizedArrayType(value);
		}
		zv::Val builder = pt_constant_array_type_builder_create_empty();
		if (UNEXPECTED(builder.isUndef())) return zv::Val();
		for (zv::ArrayEntry entry : zv::ArrRef(value)) {
			zv::Val keyType = keyTypeOf(entry);
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			zv::Val valueType = getTypeFromValue(entry.value().raw());
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(builder.raw(), keyType.raw(), valueType.raw()))) return zv::Val();
		}
		return pt_constant_array_type_builder_get_array(builder.raw());
	}

	/* $acc = TypeCombinator::union($acc, $type->generalize($precision)),
	 * skipped when the generalized type already equals $acc */
	static bool unionGeneralized(zv::Val &acc, zv::Val type, zval *precision)
	{
		if (UNEXPECTED(type.isUndef())) return false;
		zv::Val generalized = pt_type_call(Z_OBJ_P(type.raw()), PT_LC("generalize"), 1, precision);
		if (UNEXPECTED(generalized.isUndef())) return false;
		int equal = pt_type_call_is_true(Z_OBJ_P(generalized.raw()), PT_LC("equals"), 1, acc.raw());
		if (UNEXPECTED(equal < 0)) return false;
		if (equal == 1) return true;
		zv::Args<2> unionArgs{acc.raw(), generalized.raw()};
		acc = pt_type_combinator_union(2, unionArgs);
		return !acc.isUndef();
	}

	/* non-empty-array<generalized keys, generalized values>&oversized-array,
	 * plus list when the value is a list */
	static zv::Val getOversizedArrayType(zval *value)
	{
		zv::Val precision = pt_type_call_static(PT_CLASS_GENERALIZE_PRECISION, PT_LC("morespecific"), 0, NULL);
		if (UNEXPECTED(precision.isUndef())) return zv::Val();
		zval raw;
		if (UNEXPECTED(!pt_never_type_new(&raw))) return zv::Val();
		zv::Val keyType = zv::Val::adopt(raw);
		if (UNEXPECTED(!pt_never_type_new(&raw))) return zv::Val();
		zv::Val valueType = zv::Val::adopt(raw);
		for (zv::ArrayEntry entry : zv::ArrRef(value)) {
			if (UNEXPECTED(!unionGeneralized(keyType, keyTypeOf(entry), precision.raw()))) return zv::Val();
			if (UNEXPECTED(!unionGeneralized(valueType, getTypeFromValue(entry.value().raw()), precision.raw()))) return zv::Val();
		}

		if (UNEXPECTED(!pt_array_type_new(&raw, keyType.raw(), valueType.raw()))) return zv::Val();
		zv::Val array = zv::Val::adopt(raw);
		if (UNEXPECTED(!pt_non_empty_array_type_new(&raw))) return zv::Val();
		zv::Val nonEmpty = zv::Val::adopt(raw);
		if (UNEXPECTED(!pt_oversized_array_type_new(&raw))) return zv::Val();
		zv::Val oversized = zv::Val::adopt(raw);
		if (!zend_array_is_list(Z_ARRVAL_P(value))) {
			zv::Args<3> intersectArgs{array.raw(), nonEmpty.raw(), oversized.raw()};
			return pt_type_combinator_intersect(3, intersectArgs);
		}
		if (UNEXPECTED(!pt_accessory_array_list_type_new(&raw))) return zv::Val();
		zv::Val list = zv::Val::adopt(raw);
		zv::Args<4> intersectArgs{array.raw(), nonEmpty.raw(), oversized.raw(), list.raw()};
		return pt_type_combinator_intersect(4, intersectArgs);
	}

	/* new EnumCaseObjectType($class, $value->name) for an enum case, new
	 * ObjectType(get_class($value)) otherwise */
	static zv::Val objectType(zend_object *object)
	{
		zval raw;
		if ((object->ce->ce_flags & ZEND_ACC_ENUM) != 0) {
			zval nameCopy;
			zval *name = zend_read_property_ex(object->ce, object, ZSTR_KNOWN(ZEND_STR_NAME), 0, &nameCopy);
			if (UNEXPECTED(name == NULL || EG(exception))) return zv::Val();
			if (UNEXPECTED(Z_TYPE_P(name) != IS_STRING)) {
				zend_type_error("phpstan_turbo: %s::$name must be a string", ZSTR_VAL(object->ce->name));
				return zv::Val();
			}
			bool created = pt_enum_case_object_type_new(&raw, object->ce->name, Z_STR_P(name));
			if (name == &nameCopy) {
				zval_ptr_dtor(&nameCopy);
			}
			return adopted(created, raw);
		}
		return adopted(pt_object_type_new(&raw, object->ce->name), raw);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ConstantTypeHelper;

zv::Val pt_constant_type_helper_get_type_from_value(zval *value)
{
	return ConstantTypeHelper::getTypeFromValue(value);
}

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_constant_type_helper()
{
	reg::Class cls("PHPStan\\Type\\ConstantTypeHelper");
	ptdecl::ConstantTypeHelper::declareClass(cls);
	ptdecl::ConstantTypeHelper::declareProperties(cls);

	cls.method(sigs::getTypeFromValue, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *value;
		if (!zp::parse<zp::Zval>(execute_data, value)) RETURN_THROWS();
		PT_RETURN_VAL(ConstantTypeHelper::getTypeFromValue(value));
	});

	cls.shadow(&pt_ce_constant_type_helper);
}

/* }}} */
