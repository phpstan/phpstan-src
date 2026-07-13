/*
 * PHPStanTurbo\ExpressionTypeHolder — native implementation of
 * PHPStan\Analyser\ExpressionTypeHolder.
 *
 * Not final: a PHP stub subclass extends this class, and instances are
 * created from the configured expressionTypeHolderImpl class (see
 * pt_holder_create() in support.cpp) so userland type hints keep working.
 *
 * The type/certainty logic stays in the shared pt_holder_* helpers
 * (support.cpp), which ScopeOps also uses without crossing the method-call
 * ABI; the handle class below is the method-level face over them, structured
 * to mirror src/Analyser/ExpressionTypeHolder.php.
 */

#include "support.h"
#include "zv.h"

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExpressionTypeHolder. State lives in the PHP
 * object's expr/type/certainty properties. */
class ExpressionTypeHolder
{
public:
	explicit ExpressionTypeHolder(zval *self) : self(self) {}

	void construct(zv::Ref expr, zv::Ref type, zv::Ref certainty)
	{
		zv::ObjRef obj(self);
		obj.propAtWrite(PT_ETH_PROP_EXPR, zv::Val::copyOf(expr));
		obj.propAtWrite(PT_ETH_PROP_TYPE, zv::Val::copyOf(type));
		obj.propAtWrite(PT_ETH_PROP_CERTAINTY, zv::Val::copyOf(certainty));
	}

	static zv::Val createYes(zval *expr, zval *type)
	{
		zval holder;
		pt_holder_create(&holder, expr, type, PT_TRI_YES);
		return zv::Val::adopt(holder);
	}

	static zv::Val createMaybe(zval *expr, zval *type)
	{
		zval holder;
		pt_holder_create(&holder, expr, type, PT_TRI_MAYBE);
		return zv::Val::adopt(holder);
	}

	/* false = pending exception */
	bool equalTypes(zval *other, bool &out) const { return pt_holder_equal_types(self, other, &out); }

	/* false = pending exception */
	bool equals(zval *other, bool &out) const { return pt_holder_equals(self, other, &out); }

	/* and() — a C++ keyword, hence the underscore; UNDEF = pending exception */
	zv::Val and_(zval *other) const
	{
		zval result;
		if (UNEXPECTED(!pt_holder_and(self, other, &result))) {
			return zv::Val();
		}
		return zv::Val::adopt(result);
	}

	zv::Val getExpr() const { return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_ETH_PROP_EXPR)); }
	zv::Val getType() const { return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_ETH_PROP_TYPE)); }
	zv::Val getCertainty() const { return zv::Val::copyOf(zv::ObjRef(self).propAt(PT_ETH_PROP_CERTAINTY)); }

private:
	zval *self;
};

} // namespace phpstanturbo

using phpstanturbo::ExpressionTypeHolder;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define ETH_CLASS "PHPStanTurbo\\ExpressionTypeHolder"
#define TRINARY_CLASS "PHPStanTurbo\\TrinaryLogic"

void pt_register_expression_type_holder()
{
	reg::Class cls("PHPStanTurbo\\ExpressionTypeHolder");
	/* not final: a PHP stub subclass extends this class; expr/type/certainty
	 * must stay in this order (OBJ_PROP_NUM slots) */
	cls.privateNullProperty("expr");
	cls.privateNullProperty("type");
	cls.privateNullProperty("certainty");

	cls.method("__construct", reg::Public, 3, { reg::any("expr"), reg::any("type"), reg::obj("certainty", TRINARY_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type, *certainty;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT_OF_CLASS(certainty, pt_ce_trinary)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionTypeHolder(ZEND_THIS).construct(zv::Ref(expr), zv::Ref(type), zv::Ref(certainty));
	});

	cls.method("createYes", reg::PublicStatic, 2, { reg::any("expr"), reg::any("type") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionTypeHolder::createYes(expr, type).intoReturnValue(return_value);
	});

	cls.method("createMaybe", reg::PublicStatic, 2, { reg::any("expr"), reg::any("type") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionTypeHolder::createMaybe(expr, type).intoReturnValue(return_value);
	});

	cls.method("equalTypes", reg::Public, 1, { reg::obj("other", ETH_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		bool out;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ExpressionTypeHolder(ZEND_THIS).equalTypes(other, out))) {
			RETURN_THROWS();
		}
		RETURN_BOOL(out);
	});

	cls.method("equals", reg::Public, 1, { reg::obj("other", ETH_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		bool out;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ExpressionTypeHolder(ZEND_THIS).equals(other, out))) {
			RETURN_THROWS();
		}
		RETURN_BOOL(out);
	});

	cls.method("and", reg::Public, 1, { reg::obj("other", ETH_CLASS) }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ExpressionTypeHolder(ZEND_THIS).and_(other);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("getExpr", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionTypeHolder(ZEND_THIS).getExpr().intoReturnValue(return_value);
	});

	cls.method("getType", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionTypeHolder(ZEND_THIS).getType().intoReturnValue(return_value);
	});

	cls.method("getCertainty", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionTypeHolder(ZEND_THIS).getCertainty().intoReturnValue(return_value);
	});

	pt_ce_expr_type_holder = cls.register_();
}

/* }}} */
