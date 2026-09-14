/*
 * PHPStanTurbo\TypeProjectionHelper — native implementation of
 * PHPStan\Type\Generic\TypeProjectionHelper.
 *
 * Declared as PHPStan\Type\Generic\TypeProjectionHelper itself at
 * activation: final, static. describe() prefixes a type's description with
 * its call-site variance (`*` for a bivariant projection).
 */

#include "TypeTraits.h"
#include "generated/TypeProjectionHelper.h"

namespace sigs = ptdecl::TypeProjectionHelper::sig;

zend_class_entry *pt_ce_type_projection_helper = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TypeProjectionHelper (static only). */
class TypeProjectionHelper
{
public:
	/* describe($type, $variance, $level) — $variance NULL or IS_NULL for
	 * null; an owned string, UNDEF = pending exception */
	static zv::Val describe(zval *type, zval *variance, zval *level)
	{
		/* the twin's `Type $type` (array_map() over more variances than
		 * types hands the twin's closure a null here — a TypeError) */
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_argument_type_error(1, "must be of type %s, %s given", ptcls::type, zend_zval_value_name(type));
			return zv::Val();
		}
		zv::Val describedType = pt_type_op(Z_OBJ_P(type), PT_OP_DESCRIBE, 1, level);
		if (UNEXPECTED(describedType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(describedType.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::describe() must return string", ZSTR_VAL(Z_OBJCE_P(type)->name));
			return zv::Val();
		}

		if (variance == NULL || Z_TYPE_P(variance) != IS_OBJECT) return describedType;
		zv::Val invariant = pt_type_call(Z_OBJ_P(variance), PT_LC("invariant"), 0, NULL);
		if (UNEXPECTED(invariant.isUndef())) return zv::Val();
		if (zend_is_true(invariant.raw())) return describedType;

		zv::Val bivariant = pt_type_call(Z_OBJ_P(variance), PT_LC("bivariant"), 0, NULL);
		if (UNEXPECTED(bivariant.isUndef())) return zv::Val();
		if (zend_is_true(bivariant.raw())) return zv::Val::string("*", 1);

		/* sprintf('%s %s', $variance->describe(), $describedType) */
		zv::Val varianceDescription = pt_type_op(Z_OBJ_P(variance), PT_OP_DESCRIBE, 0, NULL);
		if (UNEXPECTED(varianceDescription.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(varianceDescription.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::describe() must return string", ZSTR_VAL(Z_OBJCE_P(variance)->name));
			return zv::Val();
		}
		return zv::Val::adoptString(zend_string_concat3(Z_STRVAL_P(varianceDescription.raw()), Z_STRLEN_P(varianceDescription.raw()), " ", 1, Z_STRVAL_P(describedType.raw()), Z_STRLEN_P(describedType.raw())));
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeProjectionHelper;

zv::Val pt_type_projection_helper_describe(zval *type, zval *variance, zval *level)
{
	return TypeProjectionHelper::describe(type, variance, level);
}

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_type_projection_helper()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TypeProjectionHelper");
	ptdecl::TypeProjectionHelper::declareClass(cls);
	ptdecl::TypeProjectionHelper::declareProperties(cls);

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *variance, *level;
		if (!zp::parse<zp::Obj, zp::ObjOrNull, zp::Obj>(execute_data, type, variance, level)) RETURN_THROWS();
		PT_RETURN_VAL(TypeProjectionHelper::describe(type, variance, level));
	});

	cls.shadow(&pt_ce_type_projection_helper);
}

/* }}} */
