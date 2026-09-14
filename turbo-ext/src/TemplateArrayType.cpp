/*
 * PHPStanTurbo\TemplateArrayType — native implementation of
 * PHPStan\Type\Generic\TemplateArrayType.
 *
 * Declared as PHPStan\Type\Generic\TemplateArrayType itself at activation:
 * final, extending the native ArrayType and implementing the PHP
 * TemplateType interface. The twin composes TemplateTypeTrait and UndecidedComparisonCompoundTypeTrait
 * — the shared registrars in TypeTraits.cpp supply those methods and the
 * trait's six slots (after the parent's); the constructor runs
 * parent::__construct($bound->getKeyType(), $bound->getItemType()) and then the trait's slot writes.
 */

#include "TypeTraits.h"
#include "generated/TemplateArrayType.h"

namespace sigs = ptdecl::TemplateArrayType::sig;

zend_class_entry *pt_ce_template_array_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateArrayType. State lives in the parent's slots
 * and the trait's six. */
class TemplateArrayType
{
public:
	explicit TemplateArrayType(zend_object *self) : self(self) {}

	/* __construct($scope, $templateTypeStrategy, $templateTypeVariance, $name, $bound, $default); every argument borrowed ($defaultType NULL or IS_NULL for null); false = pending exception */
	[[nodiscard]] bool construct(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType) const
	{
		/* parent::__construct($bound->getKeyType(), $bound->getItemType()) */
		zv::Val keyType = pt_array_type_get_key_type(Z_OBJ_P(bound));
		if (UNEXPECTED(keyType.isUndef())) return false;
		zv::Val itemType = pt_array_type_get_item_type(Z_OBJ_P(bound));
		if (UNEXPECTED(itemType.isUndef())) return false;
		zv::Args args{keyType.raw(), itemType.raw()};
		if (UNEXPECTED(!pt_template_type_parent_construct(self, pt_ce_template_array_type, 2, args))) return false;
		pt_template_type_init(self, pt_ce_template_array_type, scope, strategy, variance, name, bound, defaultType);
		return true;
	}

	/* new self(...) — exactly the class, as the twin's sites spell it; the
	 * bound checked as the twin's typed parameter checks it; UNDEF =
	 * pending exception */
	static zv::Val create(zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
	{
		return pt_template_type_create<TemplateArrayType>(pt_ce_template_array_type, scope, strategy, variance, name, bound, defaultType);
	}

	/* new self($this->scope, $this->strategy, $this->variance, $this->name, $bound, $this->default) over a rebuilt bound; UNDEF = pending exception (also for an UNDEF $bound) */
	zv::Val rebuild(zv::Val bound) const { return pt_template_type_rebuild<TemplateArrayType>(self, pt_ce_template_array_type, std::move(bound)); }

	/* protected withTypes(): new self($this->scope, $this->strategy, $this->variance, $this->name, new ArrayType($keyType, $itemType), $this->default) */
	zv::Val withTypes(zval *keyType, zval *itemType) const
	{
		zval arrayType;
		if (UNEXPECTED(!pt_array_type_new(&arrayType, keyType, itemType))) return zv::Val();
		return rebuild(zv::Val::adopt(arrayType));
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::TemplateArrayType;

bool pt_template_array_type_new(zval *out, zval *scope, zval *strategy, zval *variance, zend_string *name, zval *bound, zval *defaultType)
{
	return pt_val_into(TemplateArrayType::create(scope, strategy, variance, name, bound, defaultType), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS TemplateArrayType(Z_OBJ_P(ZEND_THIS))

void pt_register_template_array_type()
{
	reg::Class cls("PHPStan\\Type\\Generic\\TemplateArrayType");
	ptdecl::TemplateArrayType::declareClass(cls);
	ptdecl::TemplateArrayType::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		pt_template_ctor_args args;
		PT_TEMPLATE_TYPE_PARSE_CTOR(args);
		if (UNEXPECTED(!PT_THIS.construct(args.scope, args.strategy, args.variance, args.name, args.bound, args.defaultType))) RETURN_THROWS();
	});

	cls.method<&TemplateArrayType::withTypes, zp::Obj, zp::Obj>(sigs::withTypes);

	/* the traits the twin uses, after the class's own methods */
	ptdecl::TemplateArrayType::registerTraits(cls);

	cls.shadow(&pt_ce_template_array_type);
}

/* }}} */
