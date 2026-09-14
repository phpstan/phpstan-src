/*
 * PHPStanTurbo\ConstantTypeHelper — native implementation of
 * PHPStan\Type\ConstantTypeHelper.
 *
 * Declared as PHPStan\Type\ConstantTypeHelper itself at activation: final,
 * one static factory. getTypeFromValue() maps a PHP value to its constant
 * type the way the twin's is_*() chain does: the scalars and null to the
 * shadowed constant types, an array through the shadowed
 * ConstantArrayTypeBuilder (direct C++ calls, no engine frames), an enum
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

	/* the builder over every key/value pair, degraded beforehand when the
	 * array is larger than ARRAY_COUNT_LIMIT */
	static zv::Val arrayType(zval *value)
	{
		zv::Val builder = pt_constant_array_type_builder_create_empty();
		if (UNEXPECTED(builder.isUndef())) return zv::Val();
		if (zend_hash_num_elements(Z_ARRVAL_P(value)) > PT_CONSTANT_ARRAY_TYPE_BUILDER_ARRAY_COUNT_LIMIT) {
			if (UNEXPECTED(!pt_constant_array_type_builder_degrade_to_general_array(builder.raw(), true))) return zv::Val();
		}
		for (zv::ArrayEntry entry : zv::ArrRef(value)) {
			zval key;
			zend_string *stringKey = entry.stringKeyOrNull();
			if (stringKey != NULL) {
				ZVAL_STR(&key, stringKey);
			} else {
				ZVAL_LONG(&key, (zend_long) entry.indexKey());
			}
			zv::Val keyType = getTypeFromValue(&key);
			if (UNEXPECTED(keyType.isUndef())) return zv::Val();
			zv::Val valueType = getTypeFromValue(entry.value().raw());
			if (UNEXPECTED(valueType.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_constant_array_type_builder_set_offset_value_type(builder.raw(), keyType.raw(), valueType.raw()))) return zv::Val();
		}
		return pt_constant_array_type_builder_get_array(builder.raw());
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
