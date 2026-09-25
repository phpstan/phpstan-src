/*
 * PHPStanTurbo\VariableWriteOffset — native implementation of
 * PHPStan\Analyser\VariableWriteOffset.
 *
 * A final class with one static method, fromType(), asked for every offset
 * read and write of a named variable (ArrayDimFetchHandler, AssignHandler,
 * VariableFlowBuilder, ArrayHandler, UnsetHandler); native callers use
 * pt_variable_write_offset_from_type(). The Type calls go through the op
 * table (the direct entry of a native type, the method otherwise).
 */

#include "support.h"
#include "generated/VariableWriteOffset.h"

namespace sigs = ptdecl::VariableWriteOffset::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

zend_class_entry *pt_ce_variable_write_offset = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\VariableWriteOffset; UNDEF = pending exception. */
class VariableWriteOffset
{
public:
	/* Mirrors fromType(): the int|string offset, PHP null otherwise */
	static zv::Val fromType(zval *dimType)
	{
		zv::Val keyType = pt_type_op(Z_OBJ_P(dimType), PT_OP_TO_ARRAY_KEY, 0, NULL);
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		if (UNEXPECTED(!keyType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isConstantScalarValue() on %s", zend_zval_value_name(keyType.raw()));
			return zv::Val();
		}
		zend_long isConstantScalarValue = pt_type_op_trinary(Z_OBJ_P(keyType.raw()), PT_OP_IS_CONSTANT_SCALAR_VALUE, 0, NULL);
		if (UNEXPECTED(isConstantScalarValue < 0)) return zv::Val();
		if (isConstantScalarValue != PT_TRI_YES) return zv::Val::null();

		zv::Val values = pt_type_op(Z_OBJ_P(keyType.raw()), PT_OP_GET_CONSTANT_SCALAR_VALUES, 0, NULL);
		if (UNEXPECTED(values.isUndef())) return zv::Val();
		if (UNEXPECTED(!values.ref().isArray())) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(values.raw()));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(values.raw())) != 1) return zv::Val::null();
		zval *value = zend_hash_index_find(Z_ARRVAL_P(values.raw()), 0);
		if (UNEXPECTED(value == NULL)) {
			zend_error(E_WARNING, "Undefined array key 0");
			if (UNEXPECTED(EG(exception))) return zv::Val();
			return zv::Val::null();
		}
		ZVAL_DEREF(value);
		if (Z_TYPE_P(value) == IS_LONG || Z_TYPE_P(value) == IS_STRING) return zv::Val::copyOf(zv::Ref(value));

		return zv::Val::null();
	}
};

} // namespace phpstanturbo

zv::Val pt_variable_write_offset_from_type(zval *dimType)
{
	if (UNEXPECTED(Z_TYPE_P(dimType) != IS_OBJECT)) {
		zend_type_error("PHPStan\\Analyser\\VariableWriteOffset::fromType(): Argument #1 ($dimType) must be of type PHPStan\\Type\\Type, %s given", zend_zval_value_name(dimType));
		return zv::Val();
	}
	return phpstanturbo::VariableWriteOffset::fromType(dimType);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_variable_write_offset)
{
	reg::Class cls("PHPStan\\Analyser\\VariableWriteOffset");
	ptdecl::VariableWriteOffset::declareClass(cls);
	ptdecl::VariableWriteOffset::declareProperties(cls);

	cls.method(sigs::fromType, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
		if (UNEXPECTED(typeCe == NULL)) RETURN_THROWS();
		zval *dimType;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(dimType, typeCe)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(phpstanturbo::VariableWriteOffset::fromType(dimType));
	});

	cls.shadow(&pt_ce_variable_write_offset);
}

/* }}} */
