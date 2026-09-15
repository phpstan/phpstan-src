/*
 * PHPStanTurbo\VariableFlowBuilder — native implementation of
 * PHPStan\Analyser\VariableFlowBuilder.
 *
 * Composes VariableFlow fragments for assignment targets and call
 * arguments: the node structure is read from the PhpParser nodes'
 * properties, the flows come from the native VariableFlow factories
 * (VariableFlow.cpp), stored results from the native
 * ExpressionResultStorage and their flows from the native ExpressionResult
 * (the methods for any other class), and the collaborators that stay PHP
 * (ArgsResult, MutatingScope, VariableWriteOffset, VariableWrite) are
 * called through the engine.
 */

#include "support.h"
#include "generated/VariableFlowBuilder.h"

namespace sigs = ptdecl::VariableFlowBuilder::sig;
#include "zv.h"
#include "TypeTraits.h"
#include "TypeOps.h"

#include <cstring>

static zend_class_entry *pt_ce_variable_flow_builder;

namespace {

/* the twin's VariableWrite::KIND_LIST_ITEM */
const zend_long PT_VFB_KIND_LIST_ITEM = 8;

/* $node->$name, dereferenced; raw() NULL when the class has no such
 * property */
zv::Ref nodeProp(zend_object *node, const char *name, size_t len)
{
	zv::Ref value = zv::ObjRef(node).prop(name, len);
	return value.raw() != NULL ? value.deref() : value;
}

/* $node->getStartFilePos() / getEndFilePos(): the attribute, -1 without */
zend_long filePos(zend_object *node, zend_string *attribute)
{
	zval *value = pt_node_attribute(node, attribute);
	if (value == NULL || Z_TYPE_P(value) == IS_NULL) return -1;
	return zval_get_long(value);
}

/* the class entries every builder method dispatches on */
struct NodeClasses
{
	zend_class_entry *node;
	zend_class_entry *expr;
	zend_class_entry *variable;
	zend_class_entry *list;
	zend_class_entry *array;
	zend_class_entry *arrayDimFetch;
	zend_class_entry *propertyFetch;
	zend_class_entry *nullsafePropertyFetch;
	zend_class_entry *staticPropertyFetch;
	zend_class_entry *closure;
	zend_class_entry *arrowFunction;
	zend_class_entry *callLike;
	zend_class_entry *accessFlow;
	zend_class_entry *sequenceFlow;

	/* false = pending exception (an unresolvable class-map entry) */
	[[nodiscard]] bool resolve()
	{
		node = pt_class(PT_CLASS_NODE);
		expr = pt_class(PT_CLASS_EXPR);
		variable = pt_class(PT_CLASS_VARIABLE);
		list = pt_class(PT_CLASS_LIST_EXPR);
		array = pt_class(PT_CLASS_ARRAY_EXPR);
		arrayDimFetch = pt_class(PT_CLASS_ARRAY_DIM_FETCH);
		propertyFetch = pt_class(PT_CLASS_PROPERTY_FETCH);
		nullsafePropertyFetch = pt_class(PT_CLASS_NULLSAFE_PROPERTY_FETCH);
		staticPropertyFetch = pt_class(PT_CLASS_STATIC_PROPERTY_FETCH);
		closure = pt_class(PT_CLASS_CLOSURE_EXPR);
		arrowFunction = pt_class(PT_CLASS_ARROW_FUNCTION);
		callLike = pt_class(PT_CLASS_CALL_LIKE);
		accessFlow = pt_class(PT_CLASS_VARIABLE_ACCESS_FLOW);
		sequenceFlow = pt_class(PT_CLASS_VARIABLE_SEQUENCE_FLOW);
		return node != NULL && expr != NULL && variable != NULL && list != NULL && array != NULL
			&& arrayDimFetch != NULL && propertyFetch != NULL && nullsafePropertyFetch != NULL
			&& staticPropertyFetch != NULL && closure != NULL && arrowFunction != NULL && callLike != NULL
			&& accessFlow != NULL && sequenceFlow != NULL;
	}
};

bool isA(zv::Ref value, zend_class_entry *ce)
{
	return value.raw() != NULL && value.isObject() && instanceof_function(Z_OBJCE_P(value.raw()), ce);
}

/* a Variable node's string name (borrowed), NULL for a variable variable */
zend_string *variableName(zend_object *variable)
{
	zv::Ref name = nodeProp(variable, PT_LC("name"));
	return name.raw() != NULL && name.isString() ? name.asString() : NULL;
}

/* $result !== null ? $result->getVariableFlow() : null for a stored
 * ExpressionResult (owned, may be PHP null); UNDEF = pending exception */
zv::Val resultFlow(zv::Val result)
{
	if (Z_TYPE_P(result.raw()) != IS_OBJECT) return zv::Val::null();
	return pt_expression_result_variable_flow(result.raw());
}

/* VariableWriteOffset::fromType($result->getType()); UNDEF = pending
 * exception */
zv::Val writeOffsetOf(zval *result)
{
	zv::Val type = pt_type_call(Z_OBJ_P(result), PT_LC("gettype"), 0, NULL);
	if (UNEXPECTED(type.isUndef())) return zv::Val();
	return pt_type_call_static(PT_CLASS_VARIABLE_WRITE_OFFSET, PT_LC("fromtype"), 1, type.raw());
}

/* new VariableWrite($variableName, $node, spl_object_id($node), $kind,
 * $offsetWrite, $offset, $parentId = null, $replacesOffset) */
zv::Val newVariableWrite(zend_string *variableName, zval *node, zend_long kind, bool offsetWrite, zval *offset, bool replacesOffset)
{
	zval argv[8];
	ZVAL_STR(&argv[0], variableName);
	ZVAL_COPY_VALUE(&argv[1], node);
	ZVAL_LONG(&argv[2], (zend_long) Z_OBJ_HANDLE_P(node));
	ZVAL_LONG(&argv[3], kind);
	ZVAL_BOOL(&argv[4], offsetWrite);
	if (offset != NULL) {
		ZVAL_COPY_VALUE(&argv[5], offset);
	} else {
		ZVAL_NULL(&argv[5]);
	}
	ZVAL_NULL(&argv[6]);
	ZVAL_BOOL(&argv[7], replacesOffset);
	return pt_type_new(PT_CLASS_VARIABLE_WRITE, 8, argv);
}

} // namespace

namespace phpstanturbo {

/*
 * Mirrors PHPStan\Analyser\VariableFlowBuilder. Methods return the flow (or
 * write), PHP null where the twin returns null, UNDEF for a pending
 * exception.
 */
class VariableFlowBuilder
{
public:
	/* Mirrors throws(). */
	static zv::Val throws(zval *expr, HashTable *throwPoints)
	{
		NodeClasses classes;
		if (UNEXPECTED(!classes.resolve())) return zv::Val();

		/* a callback the callee invokes immediately throws through the call -
		 * its throw points are re-created on the callback argument node;
		 * the identity set of those arguments, keyed by object handle */
		zv::ScratchTable callbackArguments(0);
		if (instanceof_function(Z_OBJCE_P(expr), classes.callLike)) {
			bool isFirstClassCallable;
			if (UNEXPECTED(!pt_call_like_is_first_class_callable(Z_OBJ_P(expr), isFirstClassCallable))) return zv::Val();
			if (!isFirstClassCallable) {
				zv::Ref args = nodeProp(Z_OBJ_P(expr), PT_LC("args"));
				if (args.raw() != NULL && args.isArray()) {
					for (auto entry : zv::TableRef(args.asArrayTable())) {
						zv::Ref arg = entry.value().deref();
						if (!arg.isObject()) continue;
						zv::Ref value = nodeProp(arg.asObject(), PT_LC("value"));
						if (!isA(value, classes.closure) && !isA(value, classes.arrowFunction)) continue;
						zval marker;
						ZVAL_TRUE(&marker);
						zend_hash_index_update(callbackArguments.table(), Z_OBJ_HANDLE_P(value.raw()), &marker);
					}
				}
			}
		}
		pt_init_strs();
		zend_long exprStart = filePos(Z_OBJ_P(expr), pt_str_start_file_pos);
		zend_long exprEnd = filePos(Z_OBJ_P(expr), pt_str_end_file_pos);
		zv::Arr throws = zv::Arr::create(zend_hash_num_elements(throwPoints));
		for (auto entry : zv::TableRef(throwPoints)) {
			zv::Ref throwPoint = entry.value().deref();
			if (UNEXPECTED(!throwPoint.isObject())) {
				zend_type_error("phpstan_turbo: expected InternalThrowPoint, got %s", zend_zval_value_name(throwPoint.raw()));
				return zv::Val();
			}
			zv::Val node = pt_type_call(throwPoint.asObject(), PT_LC("getnode"), 0, NULL);
			if (UNEXPECTED(node.isUndef())) return zv::Val();
			zend_object *throwNode = Z_OBJ_P(node.raw());
			if (throwNode != Z_OBJ_P(expr)
				&& !zend_hash_index_exists(callbackArguments.table(), throwNode->handle)
				&& (filePos(throwNode, pt_str_start_file_pos) != exprStart || filePos(throwNode, pt_str_end_file_pos) != exprEnd)) {
				continue;
			}

			zv::Val type = pt_type_call(throwPoint.asObject(), PT_LC("gettype"), 0, NULL);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zv::Val canContainAnyThrowable = pt_type_call(throwPoint.asObject(), PT_LC("cancontainanythrowable"), 0, NULL);
			if (UNEXPECTED(canContainAnyThrowable.isUndef())) return zv::Val();
			zv::Val flow = pt_variable_flow_throwing(type.raw(), true, Z_TYPE_P(canContainAnyThrowable.raw()) == IS_TRUE);
			if (UNEXPECTED(flow.isUndef())) return zv::Val();
			throws.push(std::move(flow));
		}
		return pt_variable_flow_sequence_list(throws.table());
	}

	/* Mirrors arguments(). */
	static zv::Val arguments(zval *call, zval *argsResult, zval *storage)
	{
		zv::Ref args = nodeProp(Z_OBJ_P(call), PT_LC("args"));
		if (UNEXPECTED(args.raw() == NULL || !args.isArray())) return zv::Val::null();
		zv::Arr flows = zv::Arr::create(zend_hash_num_elements(args.asArrayTable()));
		for (auto entry : zv::TableRef(args.asArrayTable())) {
			zv::Ref arg = entry.value().deref();
			if (UNEXPECTED(!arg.isObject())) continue;
			zv::Ref value = nodeProp(arg.asObject(), PT_LC("value"));
			if (UNEXPECTED(value.raw() == NULL || !value.isObject())) {
				zend_type_error("phpstan_turbo: expected an Arg with an Expr value");
				return zv::Val();
			}
			zv::Val result = pt_type_call(Z_OBJ_P(argsResult), PT_LC("findargresult"), 1, value.raw());
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			if (Z_TYPE_P(result.raw()) == IS_NULL) {
				result = pt_expression_result_storage_find(storage, value.raw());
				if (UNEXPECTED(result.isUndef())) return zv::Val();
			}
			zv::Val flow = resultFlow(std::move(result));
			if (UNEXPECTED(flow.isUndef())) return zv::Val();
			flows.push(std::move(flow));
			zv::Ref byRef = nodeProp(arg.asObject(), PT_LC("byRef"));
			if (byRef.raw() == NULL || !byRef.isTrue()) {
				zv::Val passedByReference = pt_type_call(Z_OBJ_P(argsResult), PT_LC("ispassedbyreference"), 1, value.raw());
				if (UNEXPECTED(passedByReference.isUndef())) return zv::Val();
				if (Z_TYPE_P(passedByReference.raw()) != IS_TRUE) continue;
			}

			zv::Val escape = escapeRoot(value.raw());
			if (UNEXPECTED(escape.isUndef())) return zv::Val();
			flows.push(std::move(escape));
		}
		return pt_variable_flow_sequence_list(flows.table());
	}

	/* Mirrors child(); $node NULL for null. */
	static zv::Val child(zval *node, zval *storage)
	{
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		if (node != NULL && Z_TYPE_P(node) == IS_OBJECT && instanceof_function(Z_OBJCE_P(node), exprCe)) {
			zv::Val result = pt_expression_result_storage_find(storage, node);
			if (UNEXPECTED(result.isUndef())) return zv::Val();
			return resultFlow(std::move(result));
		}
		return zv::Val::null();
	}

	/* Mirrors targetRead(); $targetId NULL for null. */
	static zv::Val targetRead(zval *target, zval *storage, bool read, zval *targetId)
	{
		NodeClasses classes;
		if (UNEXPECTED(!classes.resolve())) return zv::Val();
		zend_object *targetObj = Z_OBJ_P(target);
		if (instanceof_function(targetObj->ce, classes.variable)) {
			zv::Ref name = nodeProp(targetObj, PT_LC("name"));
			if (name.raw() != NULL && name.isString()) return read ? pt_variable_flow_read(name.asString(), targetId, false, NULL) : zv::Val::null();
			return child(name.raw(), storage);
		}
		if (instanceof_function(targetObj->ce, classes.list) || instanceof_function(targetObj->ce, classes.array)) return zv::Val::null();
		if (instanceof_function(targetObj->ce, classes.arrayDimFetch)) {
			zv::Ref var = nodeProp(targetObj, PT_LC("var"));
			zv::Ref dim = nodeProp(targetObj, PT_LC("dim"));
			zval *dimNode = dim.raw() != NULL && dim.isObject() ? dim.raw() : NULL;
			zv::Val dimChild = child(dimNode, storage);
			if (UNEXPECTED(dimChild.isUndef())) return zv::Val();
			zv::Val first;
			if (isA(var, classes.variable) && variableName(var.asObject()) != NULL) {
				zv::Val offset;
				if (read && dimNode != NULL) {
					zv::Val dimResult = pt_expression_result_storage_find(storage, dimNode);
					if (UNEXPECTED(dimResult.isUndef())) return zv::Val();
					if (Z_TYPE_P(dimResult.raw()) == IS_OBJECT) {
						offset = writeOffsetOf(dimResult.raw());
						if (UNEXPECTED(offset.isUndef())) return zv::Val();
					}
				}
				first = pt_variable_flow_read(variableName(var.asObject()), targetId, !read, offset.isUndef() ? NULL : offset.raw());
			} else {
				if (UNEXPECTED(var.raw() == NULL || !var.isObject())) return zv::Val::null();
				first = targetRead(var.raw(), storage, true, targetId);
			}
			if (UNEXPECTED(first.isUndef())) return zv::Val();
			zv::Args argv{first.raw(), dimChild.raw()};
			return pt_variable_flow_sequence(2, argv);
		}
		if (instanceof_function(targetObj->ce, classes.propertyFetch) || instanceof_function(targetObj->ce, classes.nullsafePropertyFetch)) {
			return childSequence(targetObj, PT_LC("var"), PT_LC("name"), storage);
		}
		if (instanceof_function(targetObj->ce, classes.staticPropertyFetch)) return childSequence(targetObj, PT_LC("class"), PT_LC("name"), storage);
		return child(target, storage);
	}

	/* Mirrors writes(); $flow NULL for null. */
	static zv::Val writes(zval *flow)
	{
		zv::Arr writes = zv::Arr::empty();
		if (UNEXPECTED(!collectWrites(flow, writes))) return zv::Val();
		return zv::Val(std::move(writes));
	}

	/* Mirrors targetWrite(); $redundant NULL for null. */
	static zv::Val targetWrite(zval *target, zend_long kind, zval *scope, zval *storage, zval *redundant)
	{
		NodeClasses classes;
		if (UNEXPECTED(!classes.resolve())) return zv::Val();
		zend_object *targetObj = Z_OBJ_P(target);
		if (instanceof_function(targetObj->ce, classes.list) || instanceof_function(targetObj->ce, classes.array)) {
			zv::Ref items = nodeProp(targetObj, PT_LC("items"));
			if (UNEXPECTED(items.raw() == NULL || !items.isArray())) return zv::Val::null();
			zv::Arr writes = zv::Arr::create(zend_hash_num_elements(items.asArrayTable()));
			for (auto entry : zv::TableRef(items.asArrayTable())) {
				zv::Ref item = entry.value().deref();
				if (!item.isObject()) continue;
				zv::Ref key = nodeProp(item.asObject(), PT_LC("key"));
				zv::Ref value = nodeProp(item.asObject(), PT_LC("value"));
				zv::Ref byRef = nodeProp(item.asObject(), PT_LC("byRef"));
				if (UNEXPECTED(value.raw() == NULL || !value.isObject())) {
					zend_type_error("phpstan_turbo: expected an ArrayItem with an Expr value");
					return zv::Val();
				}
				zv::Val keyChild = child(key.raw() != NULL && key.isObject() ? key.raw() : NULL, storage);
				if (UNEXPECTED(keyChild.isUndef())) return zv::Val();
				zv::Val valueRead = targetRead(value.raw(), storage, false, NULL);
				if (UNEXPECTED(valueRead.isUndef())) return zv::Val();
				zv::Val valueWrite = targetWrite(value.raw(), PT_VFB_KIND_LIST_ITEM, scope, storage, NULL);
				if (UNEXPECTED(valueWrite.isUndef())) return zv::Val();
				zv::Val escape = byRef.raw() != NULL && byRef.isTrue() ? escapeRoot(value.raw()) : zv::Val::null();
				if (UNEXPECTED(escape.isUndef())) return zv::Val();
				zv::Args argv{keyChild.raw(), valueRead.raw(), valueWrite.raw(), escape.raw()};
				zv::Val itemFlow = pt_variable_flow_sequence(4, argv);
				if (UNEXPECTED(itemFlow.isUndef())) return zv::Val();
				writes.push(std::move(itemFlow));
			}
			return pt_variable_flow_sequence_list(writes.table());
		}
		zv::Val write = writeSite(target, kind, scope, storage);
		if (UNEXPECTED(write.isUndef())) return zv::Val();
		if (Z_TYPE_P(write.raw()) == IS_NULL) return zv::Val::null();
		return pt_variable_flow_write(write.raw(), redundant);
	}

	/* Mirrors writeSite(). */
	static zv::Val writeSite(zval *target, zend_long kind, zval *scope, zval *storage)
	{
		NodeClasses classes;
		if (UNEXPECTED(!classes.resolve())) return zv::Val();
		zend_object *targetObj = Z_OBJ_P(target);
		if (instanceof_function(targetObj->ce, classes.variable)) {
			zend_string *name = variableName(targetObj);
			if (name != NULL) {
				if (zend_string_equals_literal(name, "this") || pt_is_superglobal_name(name)) return zv::Val::null();
				return newVariableWrite(name, target, kind, false, NULL, true);
			}
		}
		if (!instanceof_function(targetObj->ce, classes.arrayDimFetch)) return zv::Val::null();
		zend_object *first = targetObj;
		for (;;) {
			zv::Ref var = nodeProp(first, PT_LC("var"));
			if (!isA(var, classes.arrayDimFetch)) break;
			first = var.asObject();
		}
		zv::Ref root = nodeProp(first, PT_LC("var"));
		if (!isA(root, classes.variable)) return zv::Val::null();
		zend_string *rootName = variableName(root.asObject());
		if (rootName == NULL || zend_string_equals_literal(rootName, "this") || pt_is_superglobal_name(rootName)) return zv::Val::null();
		zval rootNameValue;
		ZVAL_STR(&rootNameValue, rootName);
		zend_long hasVariableType = pt_type_call_trinary(Z_OBJ_P(scope), PT_LC("hasvariabletype"), 1, &rootNameValue);
		if (UNEXPECTED(hasVariableType < 0)) return zv::Val();
		if (hasVariableType != PT_TRI_NO) {
			zv::Val type = pt_type_call(Z_OBJ_P(scope), PT_LC("getvariabletype"), 1, &rootNameValue);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
			zend_long isArray = trinaryOp(type.raw(), PT_OP_IS_ARRAY);
			if (UNEXPECTED(isArray < 0)) return zv::Val();
			if (isArray != PT_TRI_YES) {
				zend_long isString = trinaryOp(type.raw(), PT_OP_IS_STRING);
				if (UNEXPECTED(isString < 0)) return zv::Val();
				if (isString != PT_TRI_YES) return zv::Val::null();
			}
		}
		zv::Ref dim = nodeProp(first, PT_LC("dim"));
		zv::Val offset;
		if (dim.raw() != NULL && dim.isObject()) {
			zv::Val dimResult = pt_expression_result_storage_find(storage, dim.raw());
			if (UNEXPECTED(dimResult.isUndef())) return zv::Val();
			if (Z_TYPE_P(dimResult.raw()) == IS_OBJECT) {
				offset = writeOffsetOf(dimResult.raw());
				if (UNEXPECTED(offset.isUndef())) return zv::Val();
			}
		}
		return newVariableWrite(rootName, target, kind, true, offset.isUndef() ? NULL : offset.raw(), first == targetObj);
	}

	/* Mirrors escapeRoot(). */
	static zv::Val escapeRoot(zval *expr)
	{
		zend_class_entry *arrayDimFetchCe = pt_class(PT_CLASS_ARRAY_DIM_FETCH);
		zend_class_entry *variableCe = pt_class(PT_CLASS_VARIABLE);
		if (UNEXPECTED(arrayDimFetchCe == NULL || variableCe == NULL)) return zv::Val();
		zend_object *node = Z_OBJ_P(expr);
		while (instanceof_function(node->ce, arrayDimFetchCe)) {
			zv::Ref var = nodeProp(node, PT_LC("var"));
			if (UNEXPECTED(var.raw() == NULL || !var.isObject())) return zv::Val::null();
			node = var.asObject();
		}
		if (!instanceof_function(node->ce, variableCe)) return zv::Val::null();
		zend_string *name = variableName(node);
		return name != NULL ? pt_variable_flow_escape(name) : zv::Val::null();
	}

private:
	/* VariableFlow::sequence(self::child($node->$a, $storage), self::child($node->$b, $storage)) */
	static zv::Val childSequence(zend_object *node, const char *a, size_t aLen, const char *b, size_t bLen, zval *storage)
	{
		zv::Ref first = nodeProp(node, a, aLen);
		zv::Ref second = nodeProp(node, b, bLen);
		zv::Val firstChild = child(first.raw() != NULL && first.isObject() ? first.raw() : NULL, storage);
		if (UNEXPECTED(firstChild.isUndef())) return zv::Val();
		zv::Val secondChild = child(second.raw() != NULL && second.isObject() ? second.raw() : NULL, storage);
		if (UNEXPECTED(secondChild.isUndef())) return zv::Val();
		zv::Args argv{firstChild.raw(), secondChild.raw()};
		return pt_variable_flow_sequence(2, argv);
	}

	/* the recursion of writes(): appends the flow's writes to $writes;
	 * false = pending exception */
	[[nodiscard]] static bool collectWrites(zval *flow, zv::Arr &writes)
	{
		if (flow == NULL || Z_TYPE_P(flow) != IS_OBJECT) return true;
		zend_class_entry *accessFlowCe = pt_class(PT_CLASS_VARIABLE_ACCESS_FLOW);
		zend_class_entry *sequenceFlowCe = pt_class(PT_CLASS_VARIABLE_SEQUENCE_FLOW);
		if (UNEXPECTED(accessFlowCe == NULL || sequenceFlowCe == NULL)) return false;
		zend_object *flowObj = Z_OBJ_P(flow);
		if (instanceof_function(flowObj->ce, accessFlowCe)) {
			zv::Ref write = nodeProp(flowObj, PT_LC("write"));
			if (write.raw() != NULL && write.isObject()) {
				writes.push(write);
			}
			return true;
		}
		if (!instanceof_function(flowObj->ce, sequenceFlowCe)) return true;
		zv::Ref children = nodeProp(flowObj, PT_LC("children"));
		if (children.raw() == NULL || !children.isArray()) return true;
		for (auto entry : zv::TableRef(children.asArrayTable())) {
			if (UNEXPECTED(!collectWrites(entry.value().deref().raw(), writes))) return false;
		}
		return true;
	}

	/* $type->isArray() / isString() as a PT_TRI_* value; -1 = pending
	 * exception */
	[[nodiscard]] static zend_long trinaryOp(zval *type, pt_type_op_id op)
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_type_error("phpstan_turbo: expected a Type, got %s", zend_zval_value_name(type));
			return -1;
		}
		zv::Val result = pt_type_op(Z_OBJ_P(type), op, 0, NULL);
		if (UNEXPECTED(result.isUndef())) return -1;
		return pt_type_trinary_value(result.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::VariableFlowBuilder;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

#define PT_VFB_RETURN(expr) \
	do { \
		zv::Val pt_vfb_result = (expr); \
		if (UNEXPECTED(pt_vfb_result.isUndef())) { \
			RETURN_THROWS(); \
		} \
		pt_vfb_result.intoReturnValue(return_value); \
	} while (0)

namespace {

} // namespace

void pt_register_variable_flow_builder()
{
	reg::Class cls("PHPStan\\Analyser\\VariableFlowBuilder");
	ptdecl::VariableFlowBuilder::declareClass(cls);
	ptdecl::VariableFlowBuilder::declareProperties(cls);

	cls.method(sigs::throws, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		HashTable *throwPoints;
		if (!zp::parse<zp::Obj, zp::Ht>(execute_data, expr, throwPoints)) RETURN_THROWS();
		PT_VFB_RETURN(VariableFlowBuilder::throws(expr, throwPoints));
	});

	cls.method(sigs::arguments, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *call, *argsResult, *storage;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, call, argsResult, storage)) RETURN_THROWS();
		PT_VFB_RETURN(VariableFlowBuilder::arguments(call, argsResult, storage));
	});

	cls.method(sigs::child, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node, *storage;
		if (!zp::parse<zp::ObjOrNull, zp::Obj>(execute_data, node, storage)) RETURN_THROWS();
		PT_VFB_RETURN(VariableFlowBuilder::child(node, storage));
	});

	cls.method(sigs::targetRead, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *target, *storage;
		bool read;
		zend_long targetId = 0;
		bool targetIdIsNull = true;
		ZEND_PARSE_PARAMETERS_START(3, 4)
			Z_PARAM_OBJECT(target)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_BOOL(read)
			Z_PARAM_OPTIONAL
			Z_PARAM_LONG_OR_NULL(targetId, targetIdIsNull)
		ZEND_PARSE_PARAMETERS_END();
		zval targetIdValue;
		ZVAL_LONG(&targetIdValue, targetId);
		PT_VFB_RETURN(VariableFlowBuilder::targetRead(target, storage, read, targetIdIsNull ? NULL : &targetIdValue));
	});

	cls.method(sigs::writes, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *flow;
		if (!zp::parse<zp::ObjOrNull>(execute_data, flow)) RETURN_THROWS();
		PT_VFB_RETURN(VariableFlowBuilder::writes(flow));
	});

	cls.method(sigs::targetWrite, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *target, *scope, *storage, *redundant = NULL;
		zend_long kind;
		if (!zp::parse<zp::Obj, zp::Long, zp::Obj, zp::Obj, zp::Opt<zp::ObjOrNull>>(execute_data, target, kind, scope, storage, redundant)) RETURN_THROWS();
		PT_VFB_RETURN(VariableFlowBuilder::targetWrite(target, kind, scope, storage, redundant));
	});

	cls.method(sigs::writeSite, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *target, *scope, *storage;
		zend_long kind;
		if (!zp::parse<zp::Obj, zp::Long, zp::Obj, zp::Obj>(execute_data, target, kind, scope, storage)) RETURN_THROWS();
		PT_VFB_RETURN(VariableFlowBuilder::writeSite(target, kind, scope, storage));
	});

	cls.method(sigs::escapeRoot, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *expr;
		if (!zp::parse<zp::Obj>(execute_data, expr)) RETURN_THROWS();
		PT_VFB_RETURN(VariableFlowBuilder::escapeRoot(expr));
	});

	cls.shadow(&pt_ce_variable_flow_builder);
}

/* }}} */
