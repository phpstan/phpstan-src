/*
 * PHPStanTurbo\AbsorbedTemplateArgumentType — native implementation of
 * PHPStan\Type\Generic\AbsorbedTemplateArgumentType.
 *
 * Declared as PHPStan\Type\Generic\AbsorbedTemplateArgumentType itself at
 * activation: final, extending the native ErrorType. The twin only widens
 * equals() to any ErrorType; the constructor and its $reason slot are
 * inherited.
 */

#include "TypeTraits.h"
#include "generated/AbsorbedTemplateArgumentType.h"

namespace sigs = ptdecl::AbsorbedTemplateArgumentType::sig;

zend_class_entry *pt_ce_absorbed_template_argument_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\AbsorbedTemplateArgumentType. The twin adds
 * no state, and its one method reads no $this, so it is static. */
class AbsorbedTemplateArgumentType
{
public:
	/* $type instanceof ErrorType */
	static bool equals(zval *type) { return instanceof_function(Z_OBJCE_P(type), pt_ce_error_type); }
};

} // namespace phpstanturbo

using phpstanturbo::AbsorbedTemplateArgumentType;

bool pt_absorbed_template_argument_type_new(zval *out, zend_string *reason)
{
	if (UNEXPECTED(object_init_ex(out, pt_ce_absorbed_template_argument_type) != SUCCESS)) return false;
	/* the inherited ErrorType::__construct($reason) */
	pt_error_type_construct(Z_OBJ_P(out), reason);
	return true;
}

/* {{{ registration */

PT_MINIT_REGISTRATION(pt_register_absorbed_template_argument_type)
{
	reg::Class cls("PHPStan\\Type\\Generic\\AbsorbedTemplateArgumentType");
	ptdecl::AbsorbedTemplateArgumentType::declareClass(cls);
	ptdecl::AbsorbedTemplateArgumentType::declareProperties(cls);

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::TypeObj>(execute_data, type)) RETURN_THROWS();
		RETURN_BOOL(AbsorbedTemplateArgumentType::equals(type));
	});

	cls.shadow(&pt_ce_absorbed_template_argument_type);
}

/* }}} */
