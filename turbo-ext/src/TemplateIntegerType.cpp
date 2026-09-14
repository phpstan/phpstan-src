/*
 * PHPStanTurbo\TemplateIntegerType — native implementation of
 * PHPStan\Type\Generic\TemplateIntegerType.
 *
 * Declared as PHPStan\Type\Generic\TemplateIntegerType itself at activation:
 * final, extending the native IntegerType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct() and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateIntegerType.h"

namespace sigs = ptdecl::TemplateIntegerType::sig;

zend_class_entry *pt_ce_template_integer_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateIntegerType. State lives in the parent's slots
 * and the trait's six. */
class TemplateIntegerType
{
public:
	explicit TemplateIntegerType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		return pt_template_type_construct(self, pt_ce_template_integer_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateIntegerType>(pt_ce_template_integer_type, scope, strategy, variance, name, bound, defaultType);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateIntegerType;

bool pt_template_integer_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateIntegerType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateIntegerType(Z_OBJ_P(ZEND_THIS))

void pt_register_template_integer_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateIntegerType");
	ptdecl::TemplateIntegerType::declareClass(cls);
	ptdecl::TemplateIntegerType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateIntegerType::registerTraits(cls);

	cls.shadow(&pt_ce_template_integer_type);
}

/* }}} */
