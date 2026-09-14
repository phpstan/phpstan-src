/*
 * PHPStanTurbo\NonAcceptingNeverType — native implementation of
 * PHPStan\Type\NonAcceptingNeverType.
 *
 * Declared as PHPStan\Type\NonAcceptingNeverType itself at activation: not
 * final, extending the native NeverType, without state of its own — the
 * twin's constructor is parent::__construct(true), which goes to NeverType's
 * constructor body directly. Its three overrides (isSuperTypeOf(),
 * accepts(), describe()) read no $this, so they are static; everything
 * else is inherited.
 */

#include "TypeTraits.h"
#include "generated/NonAcceptingNeverType.h"

namespace sigs = ptdecl::NonAcceptingNeverType::sig;

zend_class_entry *pt_ce_non_accepting_never_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\NonAcceptingNeverType. */
class NonAcceptingNeverType
{
public:
	/* __construct() { parent::__construct(true); } */
	static void construct(zend_object *self) { pt_never_type_construct(self, true, NULL); }

	/* new NonAcceptingNeverType(); UNDEF = pending exception */
	static zv::Val create()
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_non_accepting_never_type) != SUCCESS)) return zv::Val();
		construct(Z_OBJ(object));
		return zv::Val::adopt(object);
	}

	/* yes for a NonAcceptingNeverType, maybe for any other NeverType or a
	 * TemplateType, no otherwise; UNDEF = pending exception */
	static zv::Val isSuperTypeOf(zval *type)
	{
		zend_class_entry *ce = Z_OBJCE_P(type);
		/* $type instanceof self */
		if (instanceof_function(ce, pt_ce_non_accepting_never_type)) return pt_type_is_super_type_of_result(PT_TRI_YES);
		/* $type instanceof parent */
		if (instanceof_function(ce, pt_ce_never_type)) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (isTemplate) return pt_type_is_super_type_of_result(PT_TRI_MAYBE);
		return pt_type_is_super_type_of_result(PT_TRI_NO);
	}

	/* yes for a NeverType, no otherwise; UNDEF = pending exception */
	static zv::Val accepts(zval *type)
	{
		if (instanceof_function(Z_OBJCE_P(type), pt_ce_never_type)) return pt_type_accepts_result(PT_TRI_YES);
		return pt_type_accepts_result(PT_TRI_NO);
	}

	static const char *describe() { return "never"; }
};

} // namespace phpstanturbo

using phpstanturbo::NonAcceptingNeverType;

bool pt_non_accepting_never_type_new(zval *out)
{
	return pt_val_into(NonAcceptingNeverType::create(), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

void pt_register_non_accepting_never_type()
{
	reg::Class cls("PHPStan\\Type\\NonAcceptingNeverType");
	ptdecl::NonAcceptingNeverType::declareClass(cls);
	ptdecl::NonAcceptingNeverType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		NonAcceptingNeverType::construct(Z_OBJ_P(ZEND_THIS));
	});

	cls.method(sigs::isSuperTypeOf, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		if (!zp::parse<zp::Obj>(execute_data, type)) RETURN_THROWS();
		PT_RETURN_VAL(NonAcceptingNeverType::isSuperTypeOf(type));
	});

	cls.method(sigs::accepts, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, type, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(NonAcceptingNeverType::accepts(type));
	});

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		RETURN_STRING(NonAcceptingNeverType::describe());
	});

	cls.shadow(&pt_ce_non_accepting_never_type);
}

/* }}} */
