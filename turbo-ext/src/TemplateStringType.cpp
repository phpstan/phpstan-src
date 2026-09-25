/*
 * PHPStanTurbo\TemplateStringType — native implementation of
 * PHPStan\Type\Generic\TemplateStringType.
 *
 * Declared as PHPStan\Type\Generic\TemplateStringType itself at activation:
 * final, extending the native StringType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct() and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateStringType.h"

namespace sigs = ptdecl::TemplateStringType::sig;

zend_class_entry *pt_ce_template_string_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateStringType. State lives in the parent's slots
 * and the trait's six. */
class TemplateStringType
{
public:
	explicit TemplateStringType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		return pt_template_type_construct(self, pt_ce_template_string_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateStringType>(pt_ce_template_string_type, scope, strategy, variance, name, bound, defaultType);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateStringType;

bool pt_template_string_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateStringType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateStringType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_template_string_type)
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateStringType");
	ptdecl::TemplateStringType::declareClass(cls);
	ptdecl::TemplateStringType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateStringType::registerTraits(cls);

	cls.shadow(&pt_ce_template_string_type);
}

/* }}} */
