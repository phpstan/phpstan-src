/*
 * PHPStanTurbo\TemplateUnionType — native implementation of
 * PHPStan\Type\Generic\TemplateUnionType.
 *
 * Declared as PHPStan\Type\Generic\TemplateUnionType itself at activation:
 * final, extending the native UnionType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct($bound->getTypes()) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateUnionType.h"

namespace sigs = ptdecl::TemplateUnionType::sig;

zend_class_entry *pt_ce_template_union_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateUnionType. State lives in the parent's slots
 * and the trait's six. */
class TemplateUnionType
{
public:
	explicit TemplateUnionType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getTypes()) — UnionType's constructor body */
		zv::Val types = pt_union_type_get_types(Z_OBJ_P(bound));
		if (UNEXPECTED(types.isUndef())) return false;
		if (UNEXPECTED(!pt_union_type_construct(self, types.raw(), false))) return false;
		pt_template_type_init(self, pt_ce_template_union_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateUnionType>(pt_ce_template_union_type, scope, strategy, variance, name, bound, defaultType);
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
		zval *scope = pt_template_type_scope(self, pt_ce_template_union_type);
		if (UNEXPECTED(scope == NULL)) return zv::Val();
		zend_string *name = pt_template_type_name(self, pt_ce_template_union_type);
		if (UNEXPECTED(name == NULL)) return zv::Val();
		zval *variance = pt_template_type_variance(self, pt_ce_template_union_type);
		zval *strategy = variance == NULL ? NULL : pt_template_type_strategy(self, pt_ce_template_union_type);
		zval *defaultType = strategy == NULL ? NULL : pt_template_type_default(self, pt_ce_template_union_type);
		if (UNEXPECTED(defaultType == NULL)) return zv::Val();
		zval nameValue;
		ZVAL_STR(&nameValue, name);
		return pt_template_type_factory_create(scope, &nameValue, result.raw(), variance, strategy, defaultType);
	}

	/* parent::filterTypes($filterCb) (UnionType's body), rebuilt as a
	 * template type through TemplateTypeFactory::create() when the
	 * filtering lost the template */
	zv::Val filterTypes(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zv::Val result = pt_union_type_filter_types(self, fci, fcc);
		if (UNEXPECTED(result.isUndef())) return zv::Val();
		return templateOf(std::move(result));
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateUnionType;

bool pt_template_union_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateUnionType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateUnionType(Z_OBJ_P(ZEND_THIS))

void pt_register_template_union_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateUnionType");
	ptdecl::TemplateUnionType::declareClass(cls);
	ptdecl::TemplateUnionType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	cls.method(sigs::filterTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.filterTypes(&fci, &fcc));
	});

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateUnionType::registerTraits(cls);

	cls.shadow(&pt_ce_template_union_type);
}

/* }}} */
