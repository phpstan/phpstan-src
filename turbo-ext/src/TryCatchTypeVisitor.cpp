/*
 * PHPStanTurbo\TryCatchTypeVisitor — native twin of
 * PHPStan\Parser\TryCatchTypeVisitor, declared under that name at
 * activation (final, extending PhpParser\NodeVisitorAbstract like the
 * original).
 *
 * Records, on every statement, the exception types the enclosing try/catch
 * blocks of the same function catch — a stack of type lists, reset at each
 * function boundary by a null entry.
 *
 * enterNode()/leaveNode() always return null and beforeTraverse() only
 * resets the stack, so the visitor is also registered with
 * pt_native_visitor_register(): the native NodeTraverser then runs it
 * directly per node instead of calling into the engine (ParserVisitors.h).
 */

#include "ParserVisitors.h"
#include "generated/TryCatchTypeVisitor.h"

namespace sigs = ptdecl::TryCatchTypeVisitor::sig;

static zend_class_entry *pt_ce_try_catch_type_visitor = nullptr;

/* the class's only property, `private array $typeStack = []` */
#define PT_TRY_CATCH_TYPE_PROP_STACK 0

static const char pt_try_catch_type_attribute[] = "tryCatchTypes";
static zend_string *pt_try_catch_type_attribute_str = nullptr;

namespace phpstanturbo {

using visitors::NodeProp;

/* Mirrors PHPStan\Parser\TryCatchTypeVisitor. */
class TryCatchTypeVisitor
{
public:
	/* beforeTraverse(); the twin only resets the stack and returns null */
	static void beforeTraverse(zend_object *visitor)
	{
		zv::ObjRef(visitor).propAtWrite(PT_TRY_CATCH_TYPE_PROP_STACK, zv::Arr::empty());
	}

	/* enterNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool enterNode(zend_object *visitor, zend_object *node)
	{
		zval *stack = stackOf(visitor);

		if (visitors::isInstanceOf(node, PT_CLASS_STMT) || visitors::isInstanceOf(node, PT_CLASS_MATCH)) {
			uint32_t depth = zend_hash_num_elements(Z_ARRVAL_P(stack));
			if (depth > 0) {
				/* array_last($this->typeStack) */
				zval *last = zend_hash_index_find(Z_ARRVAL_P(stack), (zend_ulong) (depth - 1));
				if (last != NULL) {
					visitors::setAttribute(node, pt_try_catch_type_attribute_str, last);
				}
			}
		}
		if (UNEXPECTED(EG(exception))) return false;

		if (visitors::isInstanceOf(node, PT_CLASS_FUNCTION_LIKE)) {
			zval nullValue;
			ZVAL_NULL(&nullValue);
			visitors::pushStack(stack, &nullValue);
		}
		if (UNEXPECTED(EG(exception))) return false;

		if (visitors::isInstanceOf(node, PT_CLASS_TRY_CATCH_STMT)) {
			pushCatchTypes(stack, node);
		}
		return !EG(exception);
	}

	/* leaveNode(); the twin always returns null, false = pending exception */
	[[nodiscard]] static bool leaveNode(zend_object *visitor, zend_object *node)
	{
		if (!visitors::isInstanceOf(node, PT_CLASS_TRY_CATCH_STMT) && !visitors::isInstanceOf(node, PT_CLASS_FUNCTION_LIKE)) return !EG(exception);
		visitors::popStack(stackOf(visitor));
		return true;
	}

private:
	/*
	 * The types of the entered try/catch, on top of the ones the enclosing
	 * try/catch blocks of the same function already catch (walking the stack
	 * from the top until the function boundary's null entry).
	 */
	static void pushCatchTypes(zval *stack, zend_object *node)
	{
		static NodeProp catchesProp = PT_NODE_PROP(PT_CLASS_TRY_CATCH_STMT, "catches");
		static NodeProp catchTypesProp = PT_NODE_PROP(PT_CLASS_CATCH_STMT, "types");
		static NodeProp nameProp = PT_NAME_PROP;

		zv::Arr types = zv::Arr::create(0);

		/* foreach (array_reverse($this->typeStack) as $stackTypes) */
		uint32_t depth = zend_hash_num_elements(Z_ARRVAL_P(stack));
		while (depth > 0) {
			depth--;
			zval *stackTypes = zend_hash_index_find(Z_ARRVAL_P(stack), (zend_ulong) depth);
			if (stackTypes == NULL || Z_TYPE_P(stackTypes) != IS_ARRAY) break; /* the null entry a FunctionLike pushed */
			for (auto entry : zv::ArrRef(stackTypes)) {
				types.push(entry.value());
			}
		}

		zval *catches = catchesProp.of(node);
		if (catches != NULL && Z_TYPE_P(catches) == IS_ARRAY) {
			for (auto catchEntry : zv::ArrRef(catches)) {
				zv::Ref catchNode = catchEntry.value().deref();
				if (!catchNode.isObject()) continue;
				zval *catchTypes = catchTypesProp.of(catchNode.asObject());
				if (catchTypes == NULL || Z_TYPE_P(catchTypes) != IS_ARRAY) continue;
				for (auto typeEntry : zv::ArrRef(catchTypes)) {
					zv::Ref type = typeEntry.value().deref();
					if (!type.isObject()) continue;
					/* Name::toString() */
					zend_string *name = visitors::nameString(type.asObject(), nameProp);
					if (name == NULL) continue;
					types.push(zv::Val::string(name));
				}
			}
		}

		visitors::pushStack(stack, types.raw());
	}

	static zval *stackOf(zend_object *visitor)
	{
		return zv::ObjRef(visitor).propAt(PT_TRY_CATCH_TYPE_PROP_STACK).deref().raw();
	}
};

} // namespace phpstanturbo

using phpstanturbo::TryCatchTypeVisitor;

/* {{{ engine ABI glue: parameter parsing + registration */

static const pt_native_visitor pt_try_catch_type_entry = {
	&pt_ce_try_catch_type_visitor,
	TryCatchTypeVisitor::enterNode,
	TryCatchTypeVisitor::leaveNode,
	TryCatchTypeVisitor::beforeTraverse,
};

void pt_register_try_catch_type_visitor()
{
	pt_try_catch_type_attribute_str = zend_string_init_interned(pt_try_catch_type_attribute, sizeof(pt_try_catch_type_attribute) - 1, 1);

	reg::Class cls("PHPStan\\Parser\\TryCatchTypeVisitor");
	ptdecl::TryCatchTypeVisitor::declareClass(cls);
	/* "typeStack" must stay slot 0 (PT_TRY_CATCH_TYPE_PROP_STACK) */
	cls.privateTypedArrayPropertyDefaultEmpty("typeStack");
	cls.publicClassConstantString("ATTRIBUTE_NAME", pt_try_catch_type_attribute);

	cls.method(sigs::beforeTraverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		HashTable *nodes;
		if (!zp::parse<zp::Ht>(execute_data, nodes)) RETURN_THROWS();
		(void) nodes;
		TryCatchTypeVisitor::beforeTraverse(Z_OBJ_P(ZEND_THIS));
		RETURN_NULL();
	});

	cls.method(sigs::enterNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!TryCatchTypeVisitor::enterNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.method(sigs::leaveNode, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node;
		if (!zp::parse<zp::Obj>(execute_data, node)) RETURN_THROWS();
		if (UNEXPECTED(!TryCatchTypeVisitor::leaveNode(Z_OBJ_P(ZEND_THIS), Z_OBJ_P(node)))) RETURN_THROWS();
		RETURN_NULL();
	});

	cls.shadow(&pt_ce_try_catch_type_visitor);
	pt_native_visitor_register(&pt_try_catch_type_entry);
}

/* }}} */
