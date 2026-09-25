/*
 * PHPStanTurbo\TemplateBenevolentUnionType — native implementation of
 * PHPStan\Type\Generic\TemplateBenevolentUnionType.
 *
 * Declared as PHPStan\Type\Generic\TemplateBenevolentUnionType itself at activation:
 * final, extending the native BenevolentUnionType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct($bound->getTypes()) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateBenevolentUnionType.h"

namespace sigs = ptdecl::TemplateBenevolentUnionType::sig;

zend_class_entry *pt_ce_template_benevolent_union_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateBenevolentUnionType. State lives in the parent's slots
 * and the trait's six. */
class TemplateBenevolentUnionType
{
public:
	explicit TemplateBenevolentUnionType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getTypes()) */
		zv::Val types = pt_union_type_get_types(Z_OBJ_P(bound));
		if (UNEXPECTED(types.isUndef())) return false;
		if (UNEXPECTED(!pt_template_type_parent_construct(self, pt_ce_template_benevolent_union_type, 1, types.raw()))) return false;
		pt_template_type_init(self, pt_ce_template_benevolent_union_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateBenevolentUnionType>(pt_ce_template_benevolent_union_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* $result when it is a template type already, else
	 * TemplateTypeFactory::create($this->getScope(), $this->getName(),
	 * $result, $this->getVariance(), $this->getStrategy(),
	 * $this->getDefault()) — the getters are the trait's own, the class
	 * being final; UNDEF = pending exception (also for an UNDEF $result) */
	zv::Val templateOf(zv::Val result) const
	{
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		bool isTemplate;
		if (UNEXPECTED(!pt_type_instanceof(result.raw(), PT_CLASS_TEMPLATE_TYPE, isTemplate))) return zv::Val();
		if (isTemplate) return result;
		zval *scope = pt_template_type_scope(self, pt_ce_template_benevolent_union_type);
		if (UNEXPECTED(scope == NULL)) return zv::Val();
		zend_string *name = pt_template_type_name(self, pt_ce_template_benevolent_union_type);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval *variance = pt_template_type_variance(self, pt_ce_template_benevolent_union_type);
		zval *strategy = variance == NULL ? NULL : pt_template_type_strategy(self, pt_ce_template_benevolent_union_type);
		zval *defaultType = strategy == NULL ? NULL : pt_template_type_default(self, pt_ce_template_benevolent_union_type);
		if (UNEXPECTED(defaultType == NULL)) return zv::Val();
		zval nameValue;
		ZVAL_STR(&nameValue, name);
		return pt_template_type_factory_create(scope, &nameValue, result.raw(), variance, strategy, defaultType);
	}

	/* new self($this->scope, $this->strategy, $this->variance, $this->name, $bound, $this->default) over a rebuilt bound; UNDEF = pending exception (also for an UNDEF $bound) */
	zv::Val rebuild(zv::Val bound) const { return pt_template_type_rebuild<TemplateBenevolentUnionType>(self, pt_ce_template_benevolent_union_type, std::move(bound)); }

	/* withTypes(): new self($this->scope, $this->strategy, $this->variance, $this->name, new BenevolentUnionType($types), $this->default) */
	zv::Val withTypes(zval *types) const
	{
		zval benevolent;
		if (UNEXPECTED(!pt_benevolent_union_type_new(&benevolent, types))) return zv::Val();
		return rebuild(zv::Val::adopt(benevolent));
	}

	/* parent::filterTypes($filterCb), rebuilt as a template type through
	 * TemplateTypeFactory::create() when the filtering lost the template */
	zv::Val filterTypes(zval *filterCb) const
	{
		zv::Val result = pt_type_call_parent(pt_ce_template_benevolent_union_type, self, PT_LC("filtertypes"), 1, filterCb);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		return templateOf(std::move(result));
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateBenevolentUnionType;

bool pt_template_benevolent_union_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateBenevolentUnionType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateBenevolentUnionType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_template_benevolent_union_type)
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateBenevolentUnionType");
	ptdecl::TemplateBenevolentUnionType::declareClass(cls);
	ptdecl::TemplateBenevolentUnionType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	cls.method<&TemplateBenevolentUnionType::withTypes, zp::Arr>(sigs::withTypes);

	cls.method(sigs::filterTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.filterTypes(&fci.function_name));
	});

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateBenevolentUnionType::registerTraits(cls);

	cls.shadow(&pt_ce_template_benevolent_union_type);
}

/* }}} */
