/*
 * PHPStanTurbo\ConditionalExpressionHolder — native implementation of
 * PHPStan\Analyser\ConditionalExpressionHolder.
 *
 * Not final: a PHP stub subclass extends this class. The getKey() string is
 * built by pt_ceh_key_build() in support.cpp, shared with ScopeOps.
 */

#include "support.h"
#include "zv.h"

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ConditionalExpressionHolder. State lives in the
 * PHP object's properties. */
class ConditionalExpressionHolder
{
public:
	explicit ConditionalExpressionHolder(zval *self) : self(self) {}

	/* false = pending exception (the twin throws on empty conditions) */
	bool construct(zv::ArrRef conditionExpressionTypeHolders, zv::Ref typeHolder)
	{
		if (UNEXPECTED(conditionExpressionTypeHolders.size() == 0)) {
			pt_throw_should_not_happen();
			return false;
		}
		zv::ObjRef obj(self);
		obj.propAtWrite(PT_CEH_PROP_CONDS, zv::Val::copyOf(conditionExpressionTypeHolders));
		obj.propAtWrite(PT_CEH_PROP_TYPEHOLDER, zv::Val::copyOf(typeHolder));
		return true;
	}

	zv::Val getConditionExpressionTypeHolders() const
	{
		return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_CEH_PROP_CONDS));
	}

	zv::Val getTypeHolder() const
	{
		return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_CEH_PROP_TYPEHOLDER));
	}

	/* UNDEF = pending exception */
	zv::Val getKey() const
	{
		zv::ObjRef obj(self);
		zv::Ref conds = obj.propAt(PT_CEH_PROP_CONDS);
		zv::Ref typeHolder = obj.propAt(PT_CEH_PROP_TYPEHOLDER);

		for (auto entry : zv::ArrRef(conds.raw())) {
			if (UNEXPECTED(!pt_check_holder(entry.value().deref().raw()))) {
				return zv::Val();
			}
		}

		zend_string *key = pt_ceh_key_build(conds.asArrayTable(), typeHolder.raw());
		if (UNEXPECTED(key == NULL)) {
			return zv::Val();
		}
		return zv::Val::adoptString(key);
	}

private:
	zval *self;
};

} // namespace phpstanturbo

using phpstanturbo::ConditionalExpressionHolder;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define ETH_CLASS "PHPStanTurbo\\ExpressionTypeHolder"

void pt_register_conditional_expression_holder()
{
	reg::Class cls("PHPStanTurbo\\ConditionalExpressionHolder");
	/* not final: a PHP stub subclass extends this class;
	 * conditionExpressionTypeHolders/typeHolder must stay in this order */
	cls.privateNullProperty("conditionExpressionTypeHolders");
	cls.privateNullProperty("typeHolder");

	cls.method("__construct", reg::Public, 2, { reg::arrayArg("conditionExpressionTypeHolders"), reg::obj("typeHolder", ETH_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *holders;
		zval *typeHolder;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_ARRAY(holders)
			Z_PARAM_OBJECT_OF_CLASS(typeHolder, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ConditionalExpressionHolder(ZEND_THIS).construct(zv::ArrRef(holders), zv::Ref(typeHolder)))) {
			RETURN_THROWS();
		}
	});

	cls.method("getConditionExpressionTypeHolders", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ConditionalExpressionHolder(ZEND_THIS).getConditionExpressionTypeHolders().intoReturnValue(return_value);
	});

	cls.method("getTypeHolder", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ConditionalExpressionHolder(ZEND_THIS).getTypeHolder().intoReturnValue(return_value);
	});

	cls.method("getKey", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		zv::Val key = ConditionalExpressionHolder(ZEND_THIS).getKey();
		if (UNEXPECTED(key.isUndef())) {
			RETURN_THROWS();
		}
		key.intoReturnValue(return_value);
	});

	pt_ce_cond_expr_holder = cls.register_();
}

/* }}} */
