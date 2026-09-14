/*
 * PHPStanTurbo\TemplateGenericObjectType — native implementation of
 * PHPStan\Type\Generic\TemplateGenericObjectType.
 *
 * Declared as PHPStan\Type\Generic\TemplateGenericObjectType itself at activation:
 * final, extending the native GenericObjectType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct($bound->getClassName(), $bound->getTypes(), variances: $bound->getVariances()) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateGenericObjectType.h"

namespace sigs = ptdecl::TemplateGenericObjectType::sig;

zend_class_entry *pt_ce_template_generic_object_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateGenericObjectType. State lives in the parent's slots
 * and the trait's six. */
class TemplateGenericObjectType
{
public:
	explicit TemplateGenericObjectType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getClassName(), $bound->getTypes(), variances: $bound->getVariances()) — the two skipped parameters at their null defaults */
		zv::Val className = pt_type_call(Z_OBJ_P(bound), PT_LC("getclassname"), 0, NULL);
		if (UNEXPECTED(className.isUndef())) return false;
		zv::Val types = pt_type_call(Z_OBJ_P(bound), PT_LC("gettypes"), 0, NULL);
		if (UNEXPECTED(types.isUndef())) return false;
		zv::Val variances = pt_type_call(Z_OBJ_P(bound), PT_LC("getvariances"), 0, NULL);
		if (UNEXPECTED(variances.isUndef())) return false;
		zv::Args args{className.raw(), types.raw(), zv::null, zv::null, variances.raw()};
		if (UNEXPECTED(!pt_template_type_parent_construct(self, pt_ce_template_generic_object_type, 5, args))) return false;
		pt_template_type_init(self, pt_ce_template_generic_object_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateGenericObjectType>(pt_ce_template_generic_object_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* new self($this->scope, $this->strategy, $this->variance, $this->name, $bound, $this->default) over a rebuilt bound; UNDEF = pending exception (also for an UNDEF $bound) */
	zv::Val rebuild(zv::Val bound) const { return pt_template_type_rebuild<TemplateGenericObjectType>(self, pt_ce_template_generic_object_type, std::move(bound)); }

	/* protected recreate(): new self($this->scope, $this->strategy, $this->variance, $this->name, $this->getBound(), $this->default) — the arguments unused, the bound kept */
	zv::Val recreate() const
	{
		zval *bound = pt_template_type_bound(self, pt_ce_template_generic_object_type);
		if (UNEXPECTED(bound == NULL)) return zv::Val();
		return rebuild(zv::Val::copyOf(zv::Ref(bound)));
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateGenericObjectType;

bool pt_template_generic_object_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateGenericObjectType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateGenericObjectType(Z_OBJ_P(ZEND_THIS))

void pt_register_template_generic_object_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateGenericObjectType");
	ptdecl::TemplateGenericObjectType::declareClass(cls);
	ptdecl::TemplateGenericObjectType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	cls.method(sigs::recreate, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(3, 4);
		PT_RETURN_VAL(PT_THIS.recreate());
	});

	/* the traits the twin uses, after the class's own methods */
	pt_type_trait_template_type(cls);
	pt_type_trait_undecided_comparison_compound(cls);
	pt_type_trait_undecided_comparison(cls);

	cls.shadow(&pt_ce_template_generic_object_type);
}

/* }}} */
