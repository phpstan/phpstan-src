/*
 * PHPStanTurbo\ExpressionTypeHolder — native implementation of
 * PHPStan\Analyser\ExpressionTypeHolder.
 *
 * Declared as PHPStan\Analyser\ExpressionTypeHolder itself at activation
 * (final, like the twin); instances are created by pt_holder_create() in
 * support.cpp.
 *
 * The type/certainty logic stays in the shared pt_holder_* helpers
 * (support.cpp), which ScopeOps also uses without crossing the method-call
 * ABI; the handle class below is the method-level face over them, structured
 * to mirror src/Analyser/ExpressionTypeHolder.php.
 */

#include "support.h"
#include "generated/ExpressionTypeHolder.h"

namespace sigs = ptdecl::ExpressionTypeHolder::sig;
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
	[[nodiscard]] bool equalTypes(zval *other, bool &out) const { return pt_holder_equal_types(self, other, &out); }

	/* false = pending exception */
	[[nodiscard]] bool equals(zval *other, bool &out) const { return pt_holder_equals(self, other, &out); }

	/* and() — a C++ keyword, hence the underscore; UNDEF = pending exception */
	zv::Val and_(zval *other) const
	{
		zval result;
		if (UNEXPECTED(!pt_holder_and(self, other, &result))) return zv::Val();
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


PT_MINIT_REGISTRATION(pt_register_expression_type_holder)
{
	reg::Class cls("PHPStan\\Analyser\\ExpressionTypeHolder");
	ptdecl::ExpressionTypeHolder::declareClass(cls);
	ptdecl::ExpressionTypeHolder::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type, *certainty;
		ZEND_PARSE_PARAMETERS_START(3, 3)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(type)
			Z_PARAM_OBJECT_OF_CLASS(certainty, pt_ce_trinary)
		ZEND_PARSE_PARAMETERS_END();
		ExpressionTypeHolder(ZEND_THIS).construct(zv::Ref(expr), zv::Ref(type), zv::Ref(certainty));
	});

	cls.method(sigs::createYes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expr, type)) RETURN_THROWS();
		ExpressionTypeHolder::createYes(expr, type).intoReturnValue(return_value);
	});

	cls.method(sigs::createMaybe, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr, *type;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, expr, type)) RETURN_THROWS();
		ExpressionTypeHolder::createMaybe(expr, type).intoReturnValue(return_value);
	});

	cls.method(sigs::equalTypes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		bool out;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ExpressionTypeHolder(ZEND_THIS).equalTypes(other, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::equals, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		bool out;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		if (UNEXPECTED(!ExpressionTypeHolder(ZEND_THIS).equals(other, out))) RETURN_THROWS();
		RETURN_BOOL(out);
	});

	cls.method(sigs::and_, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *other;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT_OF_CLASS(other, pt_ce_expr_type_holder)
		ZEND_PARSE_PARAMETERS_END();
		zv::Val result = ExpressionTypeHolder(ZEND_THIS).and_(other);
		if (UNEXPECTED(result.isUndef())) RETURN_THROWS();
		result.intoReturnValue(return_value);
	});

	cls.method(sigs::getExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionTypeHolder(ZEND_THIS).getExpr().intoReturnValue(return_value);
	});

	cls.method(sigs::getType, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionTypeHolder(ZEND_THIS).getType().intoReturnValue(return_value);
	});

	cls.method(sigs::getCertainty, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionTypeHolder(ZEND_THIS).getCertainty().intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_expr_type_holder);
}

/* }}} */
