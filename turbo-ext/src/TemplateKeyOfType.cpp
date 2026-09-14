/*
 * PHPStanTurbo\TemplateKeyOfType — native implementation of
 * PHPStan\Type\Generic\TemplateKeyOfType.
 *
 * Declared as PHPStan\Type\Generic\TemplateKeyOfType itself at activation:
 * final, extending the native KeyOfType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and
 * UndecidedComparisonCompoundTypeTrait — the shared registrars in
 * TypeTraits.cpp supply those methods and the trait's six slots (after the
 * parent's `$type` and the LateResolvableTypeTrait's `$result`); the
 * constructor runs parent::__construct($bound->getType()) and then the
 * trait's slot writes. The class's own getResult() override is what the
 * inherited resolve() (LateResolvableTypeTrait, dispatching through the
 * object's class entry) reaches: the bound's getResult() re-templated
 * through TemplateTypeFactory::create().
 */

#include "TypeTraits.h"
#include "generated/TemplateKeyOfType.h"

namespace sigs = ptdecl::TemplateKeyOfType::sig;

zend_class_entry *pt_ce_template_key_of_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateKeyOfType. State lives in the
 * parent's slots and the trait's six. */
class TemplateKeyOfType
{
public:
	explicit TemplateKeyOfType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getType()) — through the bound's
		 * class entry (KeyOfType is not final) */
		zv::Val type = pt_type_call(Z_OBJ_P(bound), PT_LC("gettype"), 0, NULL);
		if (UNEXPECTED(type.isUndef())) return false;
		zv::Args args{type.raw()};
		if (UNEXPECTED(!pt_template_type_parent_construct(self, pt_ce_template_key_of_type, 1, args))) return false;
		pt_template_type_init(self, pt_ce_template_key_of_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new TemplateKeyOfType(...) — exactly the class, as the factory
	 * spells it; the bound checked as the twin's typed parameter checks
	 * it; UNDEF = pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateKeyOfType>(pt_ce_template_key_of_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* protected getResult(): TemplateTypeFactory::create($this->getScope(),
	 * $this->getName(), $this->getBound()->getResult(), $this->getVariance(),
	 * $this->getStrategy(), $this->getDefault()) — the trait's getters are
	 * the slots (the class is final), the bound's getResult() goes through
	 * its class entry (a plain KeyOfType, a PHP subclass, or another
	 * TemplateKeyOfType); UNDEF = pending exception */
	zv::Val getResult() const
	{
		zval *bound = pt_template_type_bound(self, pt_ce_template_key_of_type);
		if (UNEXPECTED(bound == NULL)) return zv::Val();
		zv::Val result = pt_type_call(Z_OBJ_P(bound), PT_LC("getresult"), 0, NULL);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		zval *scope = pt_template_type_scope(self, pt_ce_template_key_of_type);
		if (UNEXPECTED(scope == NULL)) return zv::Val();
		zend_string *name = pt_template_type_name(self, pt_ce_template_key_of_type);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval *variance = pt_template_type_variance(self, pt_ce_template_key_of_type);
		if (UNEXPECTED(variance == NULL)) return zv::Val();
		zval *strategy = pt_template_type_strategy(self, pt_ce_template_key_of_type);
		if (UNEXPECTED(strategy == NULL)) return zv::Val();
		zval *defaultType = pt_template_type_default(self, pt_ce_template_key_of_type);
		if (UNEXPECTED(defaultType == NULL)) return zv::Val();
		zval nameValue;
		ZVAL_STR(&nameValue, name);
		return pt_template_type_factory_create(scope, &nameValue, result.raw(), variance, strategy, defaultType);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateKeyOfType;

bool pt_template_key_of_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateKeyOfType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateKeyOfType(Z_OBJ_P(ZEND_THIS))

void pt_register_template_key_of_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateKeyOfType");
	ptdecl::TemplateKeyOfType::declareClass(cls);
	ptdecl::TemplateKeyOfType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	cls.method<&TemplateKeyOfType::getResult>(sigs::getResult);

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateKeyOfType::registerTraits(cls);

	cls.shadow(&pt_ce_template_key_of_type);
}

/* }}} */
