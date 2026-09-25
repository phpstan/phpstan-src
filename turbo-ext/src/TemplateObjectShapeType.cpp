/*
 * PHPStanTurbo\TemplateObjectShapeType — native implementation of
 * PHPStan\Type\Generic\TemplateObjectShapeType.
 *
 * Declared as PHPStan\Type\Generic\TemplateObjectShapeType itself at activation:
 * final, extending the native ObjectShapeType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct($bound->getProperties(), $bound->getOptionalProperties()) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateObjectShapeType.h"

namespace sigs = ptdecl::TemplateObjectShapeType::sig;

zend_class_entry *pt_ce_template_object_shape_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateObjectShapeType. State lives in the parent's slots
 * and the trait's six. */
class TemplateObjectShapeType
{
public:
	explicit TemplateObjectShapeType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getProperties(), $bound->getOptionalProperties()) */
		zv::Val properties = pt_type_call(Z_OBJ_P(bound), PT_LC("getproperties"), 0, NULL);
		if (UNEXPECTED(properties.isUndef())) return false;
		zv::Val optionalProperties = pt_type_call(Z_OBJ_P(bound), PT_LC("getoptionalproperties"), 0, NULL);
		if (UNEXPECTED(optionalProperties.isUndef())) return false;
		zv::Args args{properties.raw(), optionalProperties.raw()};
		if (UNEXPECTED(!pt_template_type_parent_construct(self, pt_ce_template_object_shape_type, 2, args))) return false;
		pt_template_type_init(self, pt_ce_template_object_shape_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateObjectShapeType>(pt_ce_template_object_shape_type, scope, strategy, variance, name, bound, defaultType);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateObjectShapeType;

bool pt_template_object_shape_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateObjectShapeType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateObjectShapeType(Z_OBJ_P(ZEND_THIS))

PT_MINIT_REGISTRATION(pt_register_template_object_shape_type)
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateObjectShapeType");
	ptdecl::TemplateObjectShapeType::declareClass(cls);
	ptdecl::TemplateObjectShapeType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateObjectShapeType::registerTraits(cls);

	cls.shadow(&pt_ce_template_object_shape_type);
}

/* }}} */
