/*
 * PHPStanTurbo\TemplateObjectType — native implementation of
 * PHPStan\Type\Generic\TemplateObjectType.
 *
 * Declared as PHPStan\Type\Generic\TemplateObjectType itself at activation:
 * final, extending the native ObjectType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct($bound->getClassName()) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateObjectType.h"

namespace sigs = ptdecl::TemplateObjectType::sig;

zend_class_entry *pt_ce_template_object_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateObjectType. State lives in the parent's slots
 * and the trait's six. */
class TemplateObjectType
{
public:
	explicit TemplateObjectType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getClassName()) — ObjectType's constructor body */
		zv::Val className = pt_type_call(Z_OBJ_P(bound), PT_LC("getclassname"), 0, NULL);
		if (UNEXPECTED(className.isUndef())) return false;
		if (UNEXPECTED(!zv::Ref(className.raw()).isString())) {
			zend_type_error("phpstan_turbo: %s::getClassName() must return string", ZSTR_VAL(Z_OBJCE_P(bound)->name));
			return false;
		}
		pt_object_type_construct(self, Z_STR_P(className.raw()), NULL, NULL);
		pt_template_type_init(self, pt_ce_template_object_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateObjectType>(pt_ce_template_object_type, scope, strategy, variance, name, bound, defaultType);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateObjectType;

bool pt_template_object_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateObjectType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateObjectType(Z_OBJ_P(ZEND_THIS))

void pt_register_template_object_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateObjectType");
	ptdecl::TemplateObjectType::declareClass(cls);
	ptdecl::TemplateObjectType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	/* the traits the twin uses, after the class's own methods */
	pt_type_trait_template_type(cls);
	pt_type_trait_undecided_comparison_compound(cls);
	pt_type_trait_undecided_comparison(cls);

	cls.shadow(&pt_ce_template_object_type);
}

/* }}} */
