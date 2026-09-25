/*
 * PHPStanTurbo\EnsuredNonNullabilityResult — native implementation of
 * PHPStan\Analyser\EnsuredNonNullabilityResult.
 *
 * What NonNullabilityHelper::ensureNonNullability() /
 * ensureShallowNonNullability() return for every isset / empty / ?? / ?->
 * subject: the ensured scope and the specified expressions to revert. The
 * helper creates it through pt_ensured_non_nullability_result_new(), the
 * handlers read it through the inline readers in AnalyserValues.h. State
 * lives in the twin's two promoted property slots, in its order.
 */

#include "support.h"
#include "generated/EnsuredNonNullabilityResult.h"

namespace slots = ptdecl::EnsuredNonNullabilityResult::slot;
namespace sigs = ptdecl::EnsuredNonNullabilityResult::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_ensured_non_nullability_result = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\EnsuredNonNullabilityResult. */
class EnsuredNonNullabilityResult
{
public:
	explicit EnsuredNonNullabilityResult(zend_object *self) : self(self) {}

	/* __construct(private MutatingScope $scope, private array $specifiedExpressions) */
	void construct(zval *scope, zval *specifiedExpressions) const
	{
		pt_write_slot(self, slots::scope, scope);
		pt_write_slot(self, slots::specifiedExpressions, specifiedExpressions);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *scope, zval *specifiedExpressions)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_ensured_non_nullability_result) != SUCCESS)) return zv::Val();
		EnsuredNonNullabilityResult(Z_OBJ(object)).construct(scope, specifiedExpressions);
		return zv::Val::adopt(object);
	}

	zv::Val getScope() const { return read(slots::scope, "scope"); }
	zv::Val getSpecifiedExpressions() const { return read(slots::specifiedExpressions, "specifiedExpressions"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::EnsuredNonNullabilityResult;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_ensured_non_nullability_result_new(zval *scope, zval *specifiedExpressions)
{
	return EnsuredNonNullabilityResult::create(scope, specifiedExpressions);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_ensured_non_nullability_result)
{
	reg::Class cls("PHPStan\\Analyser\\EnsuredNonNullabilityResult");
	ptdecl::EnsuredNonNullabilityResult::declareClass(cls);
	ptdecl::EnsuredNonNullabilityResult::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *scope, *specifiedExpressions;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_ARRAY(specifiedExpressions)
		ZEND_PARSE_PARAMETERS_END();
		EnsuredNonNullabilityResult(Z_OBJ_P(ZEND_THIS)).construct(scope, specifiedExpressions);
	});

	cls.method<&EnsuredNonNullabilityResult::getScope>(sigs::getScope);

	cls.method<&EnsuredNonNullabilityResult::getSpecifiedExpressions>(sigs::getSpecifiedExpressions);

	cls.shadow(&pt_ce_ensured_non_nullability_result);
}

/* }}} */
