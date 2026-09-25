/*
 * PHPStanTurbo\TemplateObjectWithoutClassType — native implementation of
 * PHPStan\Type\Generic\TemplateObjectWithoutClassType.
 *
 * Declared as PHPStan\Type\Generic\TemplateObjectWithoutClassType itself at activation:
 * NOT final (a PHP subclass may extend it — the trait's $this-calls go through the object's class entry), extending the native ObjectWithoutClassType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct() and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateObjectWithoutClassType.h"

namespace sigs = ptdecl::TemplateObjectWithoutClassType::sig;

zend_class_entry *pt_ce_template_object_without_class_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateObjectWithoutClassType. State lives in the parent's slots
 * and the trait's six. */
class TemplateObjectWithoutClassType
{
public:
	explicit TemplateObjectWithoutClassType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		return pt_template_type_construct(self, pt_ce_template_object_without_class_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateObjectWithoutClassType>(pt_ce_template_object_without_class_type, scope, strategy, variance, name, bound, defaultType);
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

using phpstanturbo::TemplateObjectWithoutClassType;

bool pt_template_object_without_class_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateObjectWithoutClassType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateObjectWithoutClassType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_template_object_without_class_type)
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateObjectWithoutClassType");
	ptdecl::TemplateObjectWithoutClassType::declareClass(cls);
	ptdecl::TemplateObjectWithoutClassType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	cls.method<&TemplateObjectWithoutClassType::getClassStringType>(sigs::getClassStringType);

	/* the traits the twin uses, after the class's own methods */
	pt_type_trait_template_type(cls);
	pt_type_trait_undecided_comparison_compound(cls);
	pt_type_trait_undecided_comparison(cls);

	cls.shadow(&pt_ce_template_object_without_class_type);
}

/* }}} */
