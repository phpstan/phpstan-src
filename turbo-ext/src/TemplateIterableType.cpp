/*
 * PHPStanTurbo\TemplateIterableType — native implementation of
 * PHPStan\Type\Generic\TemplateIterableType.
 *
 * Declared as PHPStan\Type\Generic\TemplateIterableType itself at activation:
 * final, extending the native IterableType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct($bound->getKeyType(), $bound->getItemType()) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateIterableType.h"

namespace sigs = ptdecl::TemplateIterableType::sig;

zend_class_entry *pt_ce_template_iterable_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateIterableType. State lives in the parent's slots
 * and the trait's six. */
class TemplateIterableType
{
public:
	explicit TemplateIterableType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getKeyType(), $bound->getItemType()) */
		zv::Val keyType = pt_type_call(Z_OBJ_P(bound), PT_LC("getkeytype"), 0, NULL);
		if (UNEXPECTED(keyType.isUndef())) return false;
		zv::Val itemType = pt_type_call(Z_OBJ_P(bound), PT_LC("getitemtype"), 0, NULL);
		if (UNEXPECTED(itemType.isUndef())) return false;
		zv::Args args{keyType.raw(), itemType.raw()};
		if (UNEXPECTED(!pt_template_type_parent_construct(self, pt_ce_template_iterable_type, 2, args))) return false;
		pt_template_type_init(self, pt_ce_template_iterable_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateIterableType>(pt_ce_template_iterable_type, scope, strategy, variance, name, bound, defaultType);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateIterableType;

bool pt_template_iterable_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateIterableType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateIterableType(Z_OBJ_P(ZEND_THIS))

void pt_register_template_iterable_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateIterableType");
	ptdecl::TemplateIterableType::declareClass(cls);
	ptdecl::TemplateIterableType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateIterableType::registerTraits(cls);

	cls.shadow(&pt_ce_template_iterable_type);
}

/* }}} */
