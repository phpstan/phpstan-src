/*
 * PHPStanTurbo\EnsuredNonNullabilityResultExpression — native implementation
 * of PHPStan\Analyser\EnsuredNonNullabilityResultExpression.
 *
 * One expression NonNullabilityHelper narrowed to non-null for an isset /
 * empty / ?? / ?-> subject, with the types and certainty
 * revertNonNullability() restores. Created through
 * pt_ensured_non_nullability_result_expression_new(), read through the
 * inline readers in AnalyserValues.h. State lives in the twin's four
 * promoted property slots, in its order.
 */

#include "support.h"
#include "generated/EnsuredNonNullabilityResultExpression.h"

namespace slots = ptdecl::EnsuredNonNullabilityResultExpression::slot;
namespace sigs = ptdecl::EnsuredNonNullabilityResultExpression::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_ensured_non_nullability_result_expression = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\EnsuredNonNullabilityResultExpression. */
class EnsuredNonNullabilityResultExpression
{
public:
	explicit EnsuredNonNullabilityResultExpression(zend_object *self) : self(self) {}

	/* __construct(private Expr $expression, private Type $originalType, private
	 * Type $originalNativeType, private TrinaryLogic $certainty) */
	void construct(zval *expression, zval *originalType, zval *originalNativeType, zval *certainty) const
	{
		pt_write_slot(self, slots::expression, expression);
		pt_write_slot(self, slots::originalType, originalType);
		pt_write_slot(self, slots::originalNativeType, originalNativeType);
		pt_write_slot(self, slots::certainty, certainty);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *expression, zval *originalType, zval *originalNativeType, zval *certainty)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_ensured_non_nullability_result_expression) != SUCCESS)) return zv::Val();
		EnsuredNonNullabilityResultExpression(Z_OBJ(object)).construct(expression, originalType, originalNativeType, certainty);
		return zv::Val::adopt(object);
	}

	zv::Val getExpression() const { return read(slots::expression, "expression"); }
	zv::Val getOriginalType() const { return read(slots::originalType, "originalType"); }
	zv::Val getOriginalNativeType() const { return read(slots::originalNativeType, "originalNativeType"); }
	zv::Val getCertainty() const { return read(slots::certainty, "certainty"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::EnsuredNonNullabilityResultExpression;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_ensured_non_nullability_result_expression_new(zval *expression, zval *originalType, zval *originalNativeType, zval *certainty)
{
	return EnsuredNonNullabilityResultExpression::create(expression, originalType, originalNativeType, certainty);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_ensured_non_nullability_result_expression()
{
	reg::Class cls("PHPStan\\Analyser\\EnsuredNonNullabilityResultExpression");
	ptdecl::EnsuredNonNullabilityResultExpression::declareClass(cls);
	ptdecl::EnsuredNonNullabilityResultExpression::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expression, *originalType, *originalNativeType, *certainty;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, expression, originalType, originalNativeType, certainty)) RETURN_THROWS();
		EnsuredNonNullabilityResultExpression(Z_OBJ_P(ZEND_THIS)).construct(expression, originalType, originalNativeType, certainty);
	});

	cls.method<&EnsuredNonNullabilityResultExpression::getExpression>(sigs::getExpression);

	cls.method<&EnsuredNonNullabilityResultExpression::getOriginalType>(sigs::getOriginalType);

	cls.method<&EnsuredNonNullabilityResultExpression::getOriginalNativeType>(sigs::getOriginalNativeType);

	cls.method<&EnsuredNonNullabilityResultExpression::getCertainty>(sigs::getCertainty);

	cls.shadow(&pt_ce_ensured_non_nullability_result_expression);
}

/* }}} */
