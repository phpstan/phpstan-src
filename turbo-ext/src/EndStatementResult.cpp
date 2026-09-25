/*
 * PHPStanTurbo\EndStatementResult — native implementation of
 * PHPStan\Analyser\EndStatementResult.
 *
 * The public (@api) end statement a StatementResult hands to the rules:
 * InternalEndStatementResult::toPublic() creates one per end statement.
 * State lives in the twin's two promoted property slots, in its order.
 */

#include "support.h"
#include "generated/EndStatementResult.h"

namespace slots = ptdecl::EndStatementResult::slot;
namespace sigs = ptdecl::EndStatementResult::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_end_statement_result = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\EndStatementResult. */
class EndStatementResult
{
public:
	explicit EndStatementResult(zend_object *self) : self(self) {}

	/* __construct(private Stmt $statement, private StatementResult $result) */
	void construct(zval *statement, zval *result) const
	{
		pt_write_slot(self, slots::statement, statement);
		pt_write_slot(self, slots::result, result);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *statement, zval *result)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_end_statement_result) != SUCCESS)) return zv::Val();
		EndStatementResult(Z_OBJ(object)).construct(statement, result);
		return zv::Val::adopt(object);
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

using phpstanturbo::EndStatementResult;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_end_statement_result_new(zval *statement, zval *result)
{
	return EndStatementResult::create(statement, result);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_end_statement_result)
{
	reg::Class cls("PHPStan\\Analyser\\EndStatementResult");
	ptdecl::EndStatementResult::declareClass(cls);
	ptdecl::EndStatementResult::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *statement, *result;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, statement, result)) RETURN_THROWS();
		EndStatementResult(Z_OBJ_P(ZEND_THIS)).construct(statement, result);
	});

	cls.method<&EndStatementResult::getStatement>(sigs::getStatement);

	cls.method<&EndStatementResult::getResult>(sigs::getResult);

	cls.shadow(&pt_ce_end_statement_result);
}

/* }}} */
