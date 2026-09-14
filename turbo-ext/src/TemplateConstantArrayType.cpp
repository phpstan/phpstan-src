/*
 * PHPStanTurbo\TemplateConstantArrayType — native implementation of
 * PHPStan\Type\Generic\TemplateConstantArrayType.
 *
 * Declared as PHPStan\Type\Generic\TemplateConstantArrayType itself at activation:
 * final, extending the native ConstantArrayType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct() over the bound's key types, value types, next auto-indexes, optional keys and list flag and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateConstantArrayType.h"

namespace sigs = ptdecl::TemplateConstantArrayType::sig;

zend_class_entry *pt_ce_template_constant_array_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateConstantArrayType. State lives in the parent's slots
 * and the trait's six. */
class TemplateConstantArrayType
{
public:
	explicit TemplateConstantArrayType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getKeyTypes(), $bound->getValueTypes(), $bound->getNextAutoIndexes(), $bound->getOptionalKeys(), $bound->isList()) */
		zv::Val keyTypes = pt_type_call(Z_OBJ_P(bound), PT_LC("getkeytypes"), 0, NULL);
		if (UNEXPECTED(keyTypes.isUndef())) return false;
		zv::Val valueTypes = pt_type_call(Z_OBJ_P(bound), PT_LC("getvaluetypes"), 0, NULL);
		if (UNEXPECTED(valueTypes.isUndef())) return false;
		zv::Val nextAutoIndexes = pt_type_call(Z_OBJ_P(bound), PT_LC("getnextautoindexes"), 0, NULL);
		if (UNEXPECTED(nextAutoIndexes.isUndef())) return false;
		zv::Val optionalKeys = pt_type_call(Z_OBJ_P(bound), PT_LC("getoptionalkeys"), 0, NULL);
		if (UNEXPECTED(optionalKeys.isUndef())) return false;
		zv::Val isList = pt_type_call(Z_OBJ_P(bound), PT_LC("islist"), 0, NULL);
		if (UNEXPECTED(isList.isUndef())) return false;
		zv::Args args{keyTypes.raw(), valueTypes.raw(), nextAutoIndexes.raw(), optionalKeys.raw(), isList.raw()};
		if (UNEXPECTED(!pt_template_type_parent_construct(self, pt_ce_template_constant_array_type, 5, args))) return false;
		pt_template_type_init(self, pt_ce_template_constant_array_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateConstantArrayType>(pt_ce_template_constant_array_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* new self($this->scope, $this->strategy, $this->variance, $this->name, $bound, $this->default) over a rebuilt bound; UNDEF = pending exception (also for an UNDEF $bound) */
	zv::Val rebuild(zv::Val bound) const { return pt_template_type_rebuild<TemplateConstantArrayType>(self, pt_ce_template_constant_array_type, std::move(bound)); }

	/* protected recreate(): new self($this->scope, $this->strategy, $this->variance, $this->name, new ConstantArrayType($keyTypes, $valueTypes, $nextAutoIndexes, $optionalKeys, $isList, $unsealed), $this->default) */
	zv::Val recreate(zval *keyTypes, zval *valueTypes, zval *nextAutoIndexes, zval *optionalKeys, zval *isList, zval *unsealed) const
	{
		zval constantArray;
		if (UNEXPECTED(!pt_constant_array_type_new(&constantArray, keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed))) return zv::Val();
		return rebuild(zv::Val::adopt(constantArray));
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateConstantArrayType;

bool pt_template_constant_array_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateConstantArrayType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateConstantArrayType(Z_OBJ_P(ZEND_THIS))

void pt_register_template_constant_array_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateConstantArrayType");
	ptdecl::TemplateConstantArrayType::declareClass(cls);
	ptdecl::TemplateConstantArrayType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	cls.method(sigs::recreate, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *keyTypes, *valueTypes, *nextAutoIndexes, *optionalKeys, *isList, *unsealed;
		if (!zp::parse<zp::Arr, zp::Arr, zp::Arr, zp::Arr, zp::ObjOrNull, zp::ArrOrNull>(execute_data, keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed)) RETURN_THROWS();
		PT_RETURN_VAL(PT_THIS.recreate(keyTypes, valueTypes, nextAutoIndexes, optionalKeys, isList, unsealed));
	});

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateConstantArrayType::registerTraits(cls);

	cls.shadow(&pt_ce_template_constant_array_type);
}

/* }}} */
