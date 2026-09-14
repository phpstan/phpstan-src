/*
 * PHPStanTurbo\TemplateConstantIntegerType — native implementation of
 * PHPStan\Type\Generic\TemplateConstantIntegerType.
 *
 * Declared as PHPStan\Type\Generic\TemplateConstantIntegerType itself at activation:
 * final, extending the native ConstantIntegerType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct($bound->getValue()) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateConstantIntegerType.h"

namespace sigs = ptdecl::TemplateConstantIntegerType::sig;

zend_class_entry *pt_ce_template_constant_integer_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateConstantIntegerType. State lives in the parent's slots
 * and the trait's six. */
class TemplateConstantIntegerType
{
public:
	explicit TemplateConstantIntegerType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getValue()) */
		zend_long value;
		if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(bound), value))) return false;
		zval arg;
		ZVAL_LONG(&arg, value);
		if (UNEXPECTED(!pt_template_type_parent_construct(self, pt_ce_template_constant_integer_type, 1, &arg))) return false;
		pt_template_type_init(self, pt_ce_template_constant_integer_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateConstantIntegerType>(pt_ce_template_constant_integer_type, scope, strategy, variance, name, bound, defaultType);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateConstantIntegerType;

bool pt_template_constant_integer_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateConstantIntegerType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateConstantIntegerType(Z_OBJ_P(ZEND_THIS))

void pt_register_template_constant_integer_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateConstantIntegerType");
	ptdecl::TemplateConstantIntegerType::declareClass(cls);
	ptdecl::TemplateConstantIntegerType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateConstantIntegerType::registerTraits(cls);

	cls.shadow(&pt_ce_template_constant_integer_type);
}

/* }}} */
