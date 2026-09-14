/*
 * PHPStanTurbo\TemplateTypeReference — native implementation of
 * PHPStan\Type\Generic\TemplateTypeReference.
 *
 * When the extension is active, PHPStan\Type\Generic\TemplateTypeReference
 * is this class, declared under that name at activation (final, like the
 * twin): a pair of a template type and the variance of its position, both
 * the object's own slots.
 */

#include "support.h"
#include "generated/TemplateTypeReference.h"

namespace slots = ptdecl::TemplateTypeReference::slot;
namespace sigs = ptdecl::TemplateTypeReference::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_template_type_reference = NULL;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\Generic\TemplateTypeReference. State lives in the PHP
 * object's $type and $positionVariance. */
class TemplateTypeReference
{
public:
	explicit TemplateTypeReference(zend_object *self) : self(self) {}

	/* an object slot, borrowed; NULL with an Error pending when
	 * uninitialized */
	[[nodiscard]] zval *type() const { return slot(slots::type, "type"); }
	zval *positionVariance() const { return slot(slots::positionVariance, "positionVariance"); }

	void construct(zval *type, zval *positionVariance)
	{
		zv::ObjRef ref(self);
		ref.propAtWrite(slots::type, zv::Val::copyOf(zv::Ref(type)));
		ref.propAtWrite(slots::positionVariance, zv::Val::copyOf(zv::Ref(positionVariance)));
	}

	/* new self($type, $positionVariance); UNDEF = pending exception */
	static zv::Val create(zval *type, zval *positionVariance)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_template_type_reference) != SUCCESS)) return zv::Val();
		TemplateTypeReference(Z_OBJ(object)).construct(type, positionVariance);
		return zv::Val::adopt(object);
	}

	zv::Val getType() const
	{
		zval *value = type();
		return value == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(value));
	}

	zv::Val getPositionVariance() const
	{
		zval *value = positionVariance();
		return value == NULL ? zv::Val() : zv::Val::copyOf(zv::Ref(value));
	}

private:
	zend_object *self;

	zval *slot(uint32_t index, const char *name) const
	{
		zval *value = OBJ_PROP_NUM(self, index);
		if (UNEXPECTED(Z_TYPE_P(value) != IS_OBJECT)) {
			zend_throw_error(NULL, "Typed property %s::$%s must not be accessed before initialization", ZSTR_VAL(pt_ce_template_type_reference->name), name);
			return NULL;
		}
		return value;
	}
};

} // namespace phpstanturbo

using phpstanturbo::TemplateTypeReference;

/* {{{ exported helpers */

/* the twin's `TemplateType $type` parameter check; false with a TypeError
 * pending */
static bool pt_ttr_check_type(zval *type, uint32_t argNum)
{
	bool isTemplate;
	if (UNEXPECTED(!pt_type_instanceof(type, PT_CLASS_TEMPLATE_TYPE, isTemplate))) return false;
	if (isTemplate) return true;
	zend_class_entry *iface = pt_class(PT_CLASS_TEMPLATE_TYPE);
	zend_argument_type_error(argNum, "must be of type %s, %s given", iface != NULL ? ZSTR_VAL(iface->name) : "PHPStan\\Type\\Generic\\TemplateType", zend_zval_value_name(type));
	return false;
}

bool pt_template_type_reference_new(zval *out, zval *type, zval *positionVariance)
{
	if (UNEXPECTED(!pt_ttr_check_type(type, 1))) return false;
	if (UNEXPECTED(Z_TYPE_P(positionVariance) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(positionVariance), pt_ce_template_type_variance))) {
		zend_argument_type_error(2, "must be of type %s, %s given", ZSTR_VAL(pt_ce_template_type_variance->name), zend_zval_value_name(positionVariance));
		return false;
	}
	zv::Val reference = TemplateTypeReference::create(type, positionVariance);
	if (UNEXPECTED(reference.isUndef())) return false;
	reference.intoReturnValue(out);
	return true;
}

bool pt_template_type_reference_parts(zval *reference, zv::Val &type, zv::Val &positionVariance)
{
	if (UNEXPECTED(Z_TYPE_P(reference) != IS_OBJECT)) {
		zend_type_error("phpstan_turbo: expected %s, %s given", pt_ce_template_type_reference != NULL ? ZSTR_VAL(pt_ce_template_type_reference->name) : "PHPStan\\Type\\Generic\\TemplateTypeReference", zend_zval_value_name(reference));
		return false;
	}
	if (EXPECTED(Z_OBJCE_P(reference) == pt_ce_template_type_reference)) {
		TemplateTypeReference self(Z_OBJ_P(reference));
		type = self.getType();
		if (UNEXPECTED(type.isUndef())) return false;
		positionVariance = self.getPositionVariance();
		return !positionVariance.isUndef();
	}
	type = pt_type_call(Z_OBJ_P(reference), PT_LC("gettype"), 0, NULL);
	if (UNEXPECTED(type.isUndef())) return false;
	positionVariance = pt_type_call(Z_OBJ_P(reference), PT_LC("getpositionvariance"), 0, NULL);
	return !positionVariance.isUndef();
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_TTR_THIS TemplateTypeReference(Z_OBJ_P(ZEND_THIS))
#define PT_TTR_TEMPLATE_TYPE "PHPStan\\Type\\Generic\\TemplateType"

void pt_register_template_type_reference()
{

	reg::Class cls("PHPStan\\Type\\Generic\\TemplateTypeReference");
	ptdecl::TemplateTypeReference::declareClass(cls);
	/* the promoted slots in the twin's order: "type" (0), "positionVariance" (1) */
	ptdecl::TemplateTypeReference::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *positionVariance;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT_OF_CLASS(positionVariance, pt_ce_template_type_variance)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!pt_ttr_check_type(type, 1))) RETURN_THROWS();
		PT_TTR_THIS.construct(type, positionVariance);
	});

	cls.method(sigs::getType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTR_THIS.getType());
	});

	cls.method(sigs::getPositionVariance, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		PT_RETURN_VAL(PT_TTR_THIS.getPositionVariance());
	});

	cls.shadow(&pt_ce_template_type_reference);
}

/* }}} */
