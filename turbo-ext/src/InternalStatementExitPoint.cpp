/*
 * PHPStanTurbo\InternalStatementExitPoint — native implementation of
 * PHPStan\Analyser\InternalStatementExitPoint.
 *
 * The engine-side exit point (return, break, continue, throw) the statement
 * handlers collect; InternalStatementResult's constructor reads each one's
 * scope (~125K per self-analysis) through the inline readers in
 * AnalyserValues.h. State lives in the twin's two promoted property slots,
 * in its order.
 */

#include "support.h"
#include "generated/InternalStatementExitPoint.h"

namespace slots = ptdecl::InternalStatementExitPoint::slot;
namespace sigs = ptdecl::InternalStatementExitPoint::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_internal_statement_exit_point = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\InternalStatementExitPoint. */
class InternalStatementExitPoint
{
public:
	explicit InternalStatementExitPoint(zend_object *self) : self(self) {}

	/* __construct(private Stmt $statement, private MutatingScope $scope) */
	void construct(zval *statement, zval *scope) const
	{
		pt_write_slot(self, slots::statement, statement);
		pt_write_slot(self, slots::scope, scope);
	}

	/* new self(...); UNDEF = pending exception */
	static zv::Val create(zval *statement, zval *scope)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_internal_statement_exit_point) != SUCCESS)) return zv::Val();
		InternalStatementExitPoint(Z_OBJ(object)).construct(statement, scope);
		return zv::Val::adopt(object);
	}

	/* Mirrors toPublic(): new StatementExitPoint($this->statement, $this->scope) */
	zv::Val toPublic() const
	{
		zval *statement = pt_typed_slot(self, slots::statement, self->ce, "statement");
		zval *scope = statement != NULL ? pt_typed_slot(self, slots::scope, self->ce, "scope") : NULL;
		if (UNEXPECTED(scope == NULL)) return zv::Val();
		return pt_statement_exit_point_new(statement, scope);
	}

	zv::Val getStatement() const { return read(slots::statement, "statement"); }
	zv::Val getScope() const { return read(slots::scope, "scope"); }

private:
	zend_object *self;

	zv::Val read(uint32_t index, const char *name) const
	{
		zval *value = pt_typed_slot(self, index, self->ce, name);
		return value != NULL ? zv::Val::copyOf(zv::Ref(value)) : zv::Val();
	}
};

} // namespace phpstanturbo

using phpstanturbo::InternalStatementExitPoint;

/* {{{ exported helpers: the shadowing class for native callers */

zv::Val pt_internal_statement_exit_point_new(zval *statement, zval *scope)
{
	return InternalStatementExitPoint::create(statement, scope);
}

/* the twin is final: the native class entry answers natively, anything
 * else (the PHP twin declared next to the native class in the differential
 * tests) through the method */
zv::Val pt_internal_statement_exit_point_to_public(zval *exitPoint)
{
	if (EXPECTED(Z_OBJCE_P(exitPoint) == pt_ce_internal_statement_exit_point)) return InternalStatementExitPoint(Z_OBJ_P(exitPoint)).toPublic();
	return pt_type_call(Z_OBJ_P(exitPoint), PT_LC("topublic"), 0, NULL);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_internal_statement_exit_point)
{
	reg::Class cls("PHPStan\\Analyser\\InternalStatementExitPoint");
	ptdecl::InternalStatementExitPoint::declareClass(cls);
	ptdecl::InternalStatementExitPoint::declareProperties(cls);

	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *statement, *scope;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, statement, scope)) RETURN_THROWS();
		InternalStatementExitPoint(Z_OBJ_P(ZEND_THIS)).construct(statement, scope);
	});

	cls.method<&InternalStatementExitPoint::toPublic>(sigs::toPublic);

	cls.method<&InternalStatementExitPoint::getStatement>(sigs::getStatement);

	cls.method<&InternalStatementExitPoint::getScope>(sigs::getScope);

	cls.shadow(&pt_ce_internal_statement_exit_point);
}

/* }}} */
