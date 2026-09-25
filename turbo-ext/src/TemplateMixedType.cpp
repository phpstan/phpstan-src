/*
 * PHPStanTurbo\TemplateMixedType — native implementation of
 * PHPStan\Type\Generic\TemplateMixedType.
 *
 * Declared as PHPStan\Type\Generic\TemplateMixedType itself at activation:
 * final, extending the native MixedType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct(true) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateMixedType.h"

namespace sigs = ptdecl::TemplateMixedType::sig;

zend_class_entry *pt_ce_template_mixed_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateMixedType. State lives in the parent's slots
 * and the trait's six. */
class TemplateMixedType
{
public:
	explicit TemplateMixedType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct(true) — MixedType's constructor body */
		pt_mixed_type_construct(self, true, NULL);
		pt_template_type_init(self, pt_ce_template_mixed_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateMixedType>(pt_ce_template_mixed_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* $this->isSuperTypeOf($type) — the trait's body (the class is final) */
	zv::Val isSuperTypeOfMixed(zval *type) const
	{
		return pt_template_type_is_super_type_of(self, pt_ce_template_mixed_type, type);
	}

	/* $this->isSuperTypeOf($acceptingType)->toAcceptsResult() when that is
	 * no, AcceptsResult::createYes() otherwise */
	zv::Val isAcceptedBy(zval *acceptingType) const
	{
		zv::Val isSuperType = pt_template_type_is_super_type_of(self, pt_ce_template_mixed_type, acceptingType);
		if (UNEXPECTED(isSuperType.isUndef())) return zv::Val();
		if (UNEXPECTED(!zv::Ref(isSuperType.raw()).isObject())) {
			zend_type_error("phpstan_turbo: %s::isSuperTypeOf() must return %s", ZSTR_VAL(self->ce->name), ZSTR_VAL(pt_ce_is_super_type_of_result->name));
			return zv::Val();
		}
		zv::Val accepts = pt_type_call(Z_OBJ_P(isSuperType.raw()), PT_LC("toacceptsresult"), 0, NULL);
		if (UNEXPECTED(accepts.isUndef())) return zv::Val();
		zend_long value = pt_type_result_trinary(accepts.raw());
		if (UNEXPECTED(value < 0)) return zv::Val();
		if (value == PT_TRI_NO) return accepts;
		return pt_type_accepts_result(PT_TRI_YES);
	}

	/* new TemplateStrictMixedType($this->scope, $this->strategy, $this->variance, $this->name, new StrictMixedType(), $this->default) */
	zv::Val toStrictMixedType() const
	{
		zend_string *name = pt_template_type_name(self, pt_ce_template_mixed_type);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval *scope = pt_template_type_scope(self, pt_ce_template_mixed_type);
		zval *strategy = scope == NULL ? NULL : pt_template_type_strategy(self, pt_ce_template_mixed_type);
		zval *variance = strategy == NULL ? NULL : pt_template_type_variance(self, pt_ce_template_mixed_type);
		zval *defaultType = variance == NULL ? NULL : pt_template_type_default(self, pt_ce_template_mixed_type);
		if (UNEXPECTED(defaultType == NULL)) return zv::Val();
		zv::Val strictMixed = pt_type_new_ce(pt_ce_strict_mixed_type, 0, NULL);
		if (UNEXPECTED(strictMixed.isUndef())) return zv::Val();
		zval created;
		if (UNEXPECTED(!pt_template_strict_mixed_type_new(&created, scope, strategy, variance, name, strictMixed.raw(), defaultType))) return zv::Val();
		return zv::Val::adopt(created);
	}

	/* new GenericClassStringType($this) */
	zv::Val getClassStringType() const
	{
		zval thisValue;
		ZVAL_OBJ(&thisValue, self);
		return pt_type_new_generic_class_string(&thisValue);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateMixedType;

bool pt_template_mixed_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateMixedType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateMixedType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_template_mixed_type)
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateMixedType");
	ptdecl::TemplateMixedType::declareClass(cls);
	ptdecl::TemplateMixedType::declareProperties(cls);

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

	cls.method<&TemplateMixedType::toStrictMixedType>(sigs::toStrictMixedType);

	cls.method<&TemplateMixedType::getClassStringType>(sigs::getClassStringType);

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateMixedType::registerTraits(cls);

	cls.shadow(&pt_ce_template_mixed_type);
}

/* }}} */
