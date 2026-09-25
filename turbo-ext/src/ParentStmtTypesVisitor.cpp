/*
 * PHPStanTurbo\ParentStmtTypesVisitor — native twin of
 * PHPStan\Parser\ParentStmtTypesVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Records the enclosing statement (and closure) classes of every statement,
 * maintained as a stack over the traversal.
 *
 * enterNode()/leaveNode() always return null and beforeTraverse() only
 * resets the stack, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/ParentStmtTypesVisitor.h"

namespace sigs = ptdecl::ParentStmtTypesVisitor::sig;

static zend_class_entry *pt_ce_parent_stmt_types_visitor = nullptr;

/* the class's only property, `private array $typeStack = []` */
#define PT_PARENT_STMT_TYPES_PROP_STACK 0

static const char pt_parent_stmt_types_attribute[] = "parentStmtTypes";
static zend_string *pt_parent_stmt_types_attribute_str = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Parser\ParentStmtTypesVisitor. */
class ParentStmtTypesVisitor
{
public:
	/* beforeTraverse(); the twin only resets the stack and returns null */
	static void beforeTraverse(zend_object *visitor)
	{
		zv::ObjRef(visitor).propAtWrite(PT_PARENT_STMT_TYPES_PROP_STACK, zv::Arr::empty());
	}

	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		if (!tracks(node)) return !EG(exception);
		zval *stack = stackOf(visitor);
		if (zend_hash_num_elements(Z_ARRVAL_P(stack)) > 0) {
			visitors::setAttribute(node, pt_parent_stmt_types_attribute_str, stack);
		}
		zval className;
		ZVAL_STR_COPY(&className, node->ce->name);
		visitors::pushStack(stack, &className);
		zval_ptr_dtor(&className);
		return true;
	}

	/* leaveNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool leaveNode(zend_object *visitor, zend_object *node)
	{
		if (!tracks(node)) return !EG(exception);
		visitors::popStack(stackOf(visitor));
		return true;
	}

private:
	/* $node instanceof Stmt || $node instanceof Expr\Closure */
	static bool tracks(zend_object *node)
	{
		return visitors::isInstanceOf(node, PT_CLASS_STMT) || visitors::isInstanceOf(node, PT_CLASS_CLOSURE_EXPR);
	}

	static zval *stackOf(zend_object *visitor)
	{
		return zv::ObjRef(visitor).propAt(PT_PARENT_STMT_TYPES_PROP_STACK).deref().raw();
	}
};

} // namespace phpstanturbo

using phpstanturbo::ParentStmtTypesVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_parent_stmt_types_entry = {
	&pt_ce_parent_stmt_types_visitor,
	ParentStmtTypesVisitor::enterNode,
	ParentStmtTypesVisitor::leaveNode,
	ParentStmtTypesVisitor::beforeTraverse,
};

PT_MINIT_REGISTRATION(pt_register_parent_stmt_types_visitor)
{
	pt_parent_stmt_types_attribute_str = zend_string_init_interned(pt_parent_stmt_types_attribute, sizeof(pt_parent_stmt_types_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\ParentStmtTypesVisitor");
	ptdecl::ParentStmtTypesVisitor::declareClass(cls);
	/* "typeStack" must stay slot 0 (PT_PARENT_STMT_TYPES_PROP_STACK) */
	cls.privateTypedArrayPropertyDefaultEmpty("typeStack");
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_parent_stmt_types_attribute);

	cls.method(sigs::beforeTraverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *nodes;
		if (!zp::parse<zp::Ht>(execute_data, nodes)) RETURN_THROWS();
		(void) nodes;
		ParentStmtTypesVisitor::beforeTraverse(Z_OBJ_P(ZEND_THIS));
		RETURN_NULL();
	});

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ParentStmtTypesVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.method(sigs::leaveNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!ParentStmtTypesVisitor::leaveNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_parent_stmt_types_visitor);
	pt_native_visitor_register(&pt_parent_stmt_types_entry);
}

/* }}} */
