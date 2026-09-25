/*
 * PHPStanTurbo\InternalEndStatementResult — native implementation of
 * PHPStan\Analyser\InternalEndStatementResult.
 *
 * The engine-side end statement of a branch (IfHandler creates one per
 * branch); InternalStatementResult's constructor reads each one's result
 * through the inline readers in AnalyserValues.h. State lives in the twin's
 * two promoted property slots, in its order.
 */

#include "support.h"
#include "generated/InternalEndStatementResult.h"

namespace slots = ptdecl::InternalEndStatementResult::slot;
namespace sigs = ptdecl::InternalEndStatementResult::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_internal_end_statement_result = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\InternalEndStatementResult. */
class InternalEndStatementResult
{
public:
	explicit InternalEndStatementResult(zend_object *self) : self(self) {}

	/* __construct(private Stmt $statement, private InternalStatementResult $result) */
	void construct(zval *statement, zval *result) const
	{
		pt_write_slot(self, slots::statement, statement);
		pt_write_slot(self, slots::result, result);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *statement, zval *result)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_internal_end_statement_result) != SUCCESS)) return zv::Val();
		InternalEndStatementResult(Z_OBJ(object)).construct(statement, result);
		return zv::Val::adopt(object);
	}

	/* Mirrors toPublic(): new EndStatementResult($this->statement, $this->result->toPublic()) */
	zv::Val toPublic() const
	{
		zval *statement = pt_typed_slot(self, slots::statement, self->ce, "statement");
		zval *result = statement != NULL ? pt_typed_slot(self, slots::result, self->ce, "result") : NULL;
		if (UNEXPECTED(result == NULL)) return zv::Val();
		zv::Val publicResult = pt_internal_statement_result_to_public(result);
		if (UNEXPECTED(publicResult.isUndef())) return zv::Val();
		return pt_end_statement_result_new(statement, publicResult.raw());
	}

	zv::Val getStatement() const { return read(slots::statement, "statement"); }
	zv::Val getResult() const { return read(slots::result, "result"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::InternalEndStatementResult;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_internal_end_statement_result_new(zval *statement, zval *result)
{
	return InternalEndStatementResult::create(statement, result);
}

/* the twin is final: the native class entry answers natively, anything
 * else (the PHP twin declared next to the native class in the differential
 * tests) through the method */
zv::Val pt_internal_end_statement_result_to_public(zval *endStatement)
{
	if (EXPECTED(Z_OBJCE_P(endStatement) == pt_ce_internal_end_statement_result)) return InternalEndStatementResult(Z_OBJ_P(endStatement)).toPublic();
	return pt_type_call(Z_OBJ_P(endStatement), PT_LC("topublic"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_internal_end_statement_result)
{
	reg::Class cls("PHPStan\\Analyser\\InternalEndStatementResult");
	ptdecl::InternalEndStatementResult::declareClass(cls);
	ptdecl::InternalEndStatementResult::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *statement, *result;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, statement, result)) RETURN_THROWS();
		InternalEndStatementResult(Z_OBJ_P(ZEND_THIS)).construct(statement, result);
	});

	cls.method<&InternalEndStatementResult::toPublic>(sigs::toPublic);

	cls.method<&InternalEndStatementResult::getStatement>(sigs::getStatement);

	cls.method<&InternalEndStatementResult::getResult>(sigs::getResult);

	cls.shadow(&pt_ce_internal_end_statement_result);
}

/* }}} */
