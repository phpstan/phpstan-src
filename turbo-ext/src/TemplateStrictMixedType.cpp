/*
 * PHPStanTurbo\TemplateStrictMixedType — native implementation of
 * PHPStan\Type\Generic\TemplateStrictMixedType.
 *
 * Declared as PHPStan\Type\Generic\TemplateStrictMixedType itself at activation:
 * final, extending the native StrictMixedType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * no parent constructor (StrictMixedType declares none) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateStrictMixedType.h"

namespace sigs = ptdecl::TemplateStrictMixedType::sig;

zend_class_entry *pt_ce_template_strict_mixed_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateStrictMixedType. State lives in the parent's slots
 * and the trait's six. */
class TemplateStrictMixedType
{
public:
	explicit TemplateStrictMixedType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* the twin calls no parent constructor (StrictMixedType declares none) */
		pt_template_type_init(self, pt_ce_template_strict_mixed_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateStrictMixedType>(pt_ce_template_strict_mixed_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* $this->isSuperTypeOf($type) — the trait's body (the class is final) */
	zv::Val isSuperTypeOfMixed(zval *type) const
	{
		return pt_template_type_is_super_type_of(self, pt_ce_template_strict_mixed_type, type);
	}

	/* $this->isSubTypeOf($acceptingType)->toAcceptsResult() */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		zv::Val isSubType = pt_template_type_is_sub_type_of(self, pt_ce_template_strict_mixed_type, acceptingType);
		if (UNEXPECTED(isSubType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(isSubType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::isSubTypeOf() must return %s", ZSTR_VAL(self->ce->name), ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		return pt_type_call(Z_OBJ_P(isSubType.raw()), PT_LC("toacceptsresult"), 0, NULL);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateStrictMixedType;

bool pt_template_strict_mixed_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateStrictMixedType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateStrictMixedType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_template_strict_mixed_type)
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateStrictMixedType");
	ptdecl::TemplateStrictMixedType::declareClass(cls);
	ptdecl::TemplateStrictMixedType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	cls.method(sigs::isSuperTypeOfMixed, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(type, pt_ce_mixed_type)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.isSuperTypeOfMixed(type));
	});

	cls.method(sigs::isAcceptedBy, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *acceptingType;
		bool strictTypes;
		if (!zp::parse<zp::Obj, zp::Bool>(execute_data, acceptingType, strictTypes)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.isAcceptedBy(acceptingType));
	});

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateStrictMixedType::registerTraits(cls);

	cls.shadow(&pt_ce_template_strict_mixed_type);
}

/* }}} */
