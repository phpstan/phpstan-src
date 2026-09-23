/*
 * PHPStanTurbo\ArrayHandler — native implementation of
 * PHPStan\Analyser\ExprHandler\ArrayHandler.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. processExpr() is registered as the class's
 * handler entry (Engine.h). The twin's closures are native closures capturing
 * what the PHP closures capture: the typeCallback ($this, $expr,
 * $itemResults, $beforeScope); the item-type callback it hands to
 * InitializerExprTypeResolver::getArrayType() ($itemResults,
 * $nativeTypesPromoted) is a pt_ietr_get_type over the typeCallback's frame
 * (the resolver calls it synchronously); the specifyTypesCallback is
 * SpecifiedTypes::emptySpecifyCallback(), as in the twin.
 *
 * NodeScopeResolver, MutatingScope, ExpressionResult, ExpressionContext,
 * VariableFlow(Builder), VariableWriteOffset, TypeCombinator and the Type
 * kernel are called through their direct entries; the VariableWrite value
 * target through its slots (VariableFlow.cpp's pt_variable_write_slots_of()),
 * InitializerExprTypeResolver::getArrayType() through its direct entry; the
 * LiteralArrayItem / LiteralArrayNode / VariableWrite virtual
 * nodes and the synthetic is_callable() call are instantiated through the
 * class map.
 *
 * The twin's $nextIndex is int|null that `$nextIndex++` / `max($nextIndex,
 * $offset + 1)` can overflow into a float: it is kept as a zval with the
 * engine's own increment and comparison, a long fast path first.
 */

#include "support.h"
#include "generated/ArrayHandler.h"

namespace slots = ptdecl::ArrayHandler::slot;
namespace sigs = ptdecl::ArrayHandler::sig;
#include "CallHandlerSupport.h"

zend_class_entry *pt_ce_array_handler = nullptr;

namespace {

using namespace ptcall;

constexpr const char *pt_arh_closure_name = "PHPStan\\Analyser\\ExprHandler\\ArrayHandler::{closure}";

/* VariableWrite::KIND_ARRAY_LITERAL_ITEM */
constexpr zend_long PT_ARH_KIND_ARRAY_LITERAL_ITEM = 14;

/* {{{ the PHP collaborators (one site each; switch to their direct entries
 * once they are ported) */

/* $initializerExprTypeResolver->getArrayType($expr, $getTypeCallback) */
zv::Val getArrayType(zval *initializerExprTypeResolver, zval *expr, const pt_ietr_get_type &getTypeCallback)
{
	return pt_initializer_expr_type_resolver_get_array_type(initializerExprTypeResolver, expr, getTypeCallback);
}

/* }}} */

/* {{{ the PhpParser nodes' properties */

pt_property_site pt_arh_items_site;
pt_property_site pt_arh_item_key_site;
pt_property_site pt_arh_item_value_site;
pt_property_site pt_arh_item_unpack_site;
pt_property_site pt_arh_item_by_ref_site;

zval *exprItems(zval *expr) { return nodeProperty(pt_arh_items_site, expr, PT_LC("items")); }
zval *itemKey(zval *item) { return nodeProperty(pt_arh_item_key_site, item, PT_LC("key")); }
zval *itemValue(zval *item) { return nodeProperty(pt_arh_item_value_site, item, PT_LC("value")); }
zval *itemUnpack(zval *item) { return nodeProperty(pt_arh_item_unpack_site, item, PT_LC("unpack")); }
zval *itemByRef(zval *item) { return nodeProperty(pt_arh_item_by_ref_site, item, PT_LC("byRef")); }

/* }}} */

/* {{{ the VariableWrite value-flow target's getters */

/* $write->isOffsetWrite() / ->getVariableName() / ->getId(): the slot of
 * the final PHP class, the getter otherwise; UNDEF = pending exception */
zv::Val variableWriteGetter(zval *write, uint32_t pt_variable_write_slots::*member, const char *lcname, size_t len)
{
	bool error = false;
	const pt_variable_write_slots *writeSlots = pt_variable_write_slots_of(Z_OBJ_P(write), error);
	if (EXPECTED(writeSlots != NULL)) return zv::Val::copyOf(zv::ObjRef(write).propAtOffset(writeSlots->*member));
	if (UNEXPECTED(error)) return zv::Val();
	return pt_type_call(Z_OBJ_P(write), lcname, len, 0, NULL);
}

/* }}} */

/* {{{ small value helpers */

/* the permanent interned 'is_callable' (module startup) */
zend_string *pt_arh_is_callable = nullptr;

/* a PHP `$x !== null` read of a $nextIndex zval */
inline bool isSet(const zval *value)
{
	return Z_TYPE_P(value) != IS_NULL;
}

/* $nextIndex = max($nextIndex, $offset + 1) with $offset an int */
inline void advanceNextIndex(zval *nextIndex, zend_long offset)
{
	zval candidate;
	if (EXPECTED(offset != ZEND_LONG_MAX)) {
		ZVAL_LONG(&candidate, offset + 1);
	} else {
		ZVAL_DOUBLE(&candidate, (double) ZEND_LONG_MAX + 1.0);
	}
	if (EXPECTED(Z_TYPE_P(nextIndex) == IS_LONG && Z_TYPE(candidate) == IS_LONG)) {
		if (Z_LVAL(candidate) > Z_LVAL_P(nextIndex)) ZVAL_LONG(nextIndex, Z_LVAL(candidate));
		return;
	}
	// max() keeps the first argument unless a later one compares greater
	if (zend_compare(&candidate, nextIndex) > 0) ZVAL_COPY_VALUE(nextIndex, &candidate);
}

/* new ConstantIntegerType($nextIndex) (a float $nextIndex goes through the
 * constructor, which rejects it like the twin's call does) */
zv::Val newConstantIntegerTypeOf(zval *nextIndex)
{
	if (EXPECTED(Z_TYPE_P(nextIndex) == IS_LONG)) {
		zval out;
		if (UNEXPECTED(!pt_constant_integer_type_new(&out, Z_LVAL_P(nextIndex)))) return zv::Val();
		return zv::Val::adopt(out);
	}
	return pt_type_new_ce(pt_ce_constant_integer_type, 1, nextIndex);
}

/* }}} */

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\ArrayHandler; UNDEF = pending
 * exception. */
class ArrayHandler
{
public:
	explicit ArrayHandler(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *initializerExprTypeResolver, zval *expressionResultFactory) const
	{
		pt_write_slot(self, slots::initializerExprTypeResolver, initializerExprTypeResolver);
		pt_write_slot(self, slots::expressionResultFactory, expressionResultFactory);
	}

	/* Mirrors supports(); false = pending exception */
	[[nodiscard]] bool supports(zval *expr, bool &out) const
	{
		int is = isInstanceOf(expr, PT_CLASS_ARRAY_EXPR);
		if (UNEXPECTED(is < 0)) return false;
		out = is == 1;
		return true;
	}

	/* Mirrors processExpr(). */
	zv::Val processExpr(zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scopeArg, zval *storage, zval *nodeCallback, zval *context) const
	{
		zval *beforeScope = scopeArg;
		zv::Val scope = zv::Val::copyOf(zv::Ref(scopeArg));
		zval *itemsSlot = exprItems(expr);
		if (UNEXPECTED(itemsSlot == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(itemsSlot) != IS_ARRAY)) {
			zend_error(E_WARNING, "foreach() argument must be of type array|object, %s given", zend_zval_value_name(itemsSlot));
		}
		// foreach iterates the array it was handed
		zv::Val items = zv::Val::copyOf(zv::Ref(itemsSlot));
		uint32_t itemCount = Z_TYPE_P(items.raw()) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(items.raw())) : 0;

		zv::Arr itemNodes = zv::Arr::create(itemCount);
		zv::Arr itemResults = zv::Arr::create(itemCount);
		zv::Arr variableFlows = zv::Arr::create(itemCount);
		bool hasYield = false;
		zv::Val throwPoints = zv::Val(zv::Arr::empty());
		zv::Val impurePoints = zv::Val(zv::Arr::empty());
		bool isAlwaysTerminating = false;

		zv::Val literalWrite = zv::Val::null();
		{
			bool valueFlowDirect;
			if (UNEXPECTED(!pt_expression_context_is_value_flow_direct(context, valueFlowDirect))) return zv::Val();
			if (valueFlowDirect) {
				literalWrite = pt_expression_context_get_value_flow_target(context);
				if (UNEXPECTED(literalWrite.isUndef())) return zv::Val();
			}
		}
		if (!literalWrite.isNull()) {
			if (UNEXPECTED(!literalWrite.ref().isObject())) {
				zend_throw_error(NULL, "Call to a member function isOffsetWrite() on %s", zend_zval_value_name(literalWrite.raw()));
				return zv::Val();
			}
			zv::Val offsetWrite = variableWriteGetter(literalWrite.raw(), &pt_variable_write_slots::offsetWrite, PT_LC("isoffsetwrite"));
			if (UNEXPECTED(offsetWrite.isUndef())) return zv::Val();
			if (zend_is_true(offsetWrite.raw())) literalWrite = zv::Val::null();
		}
		zv::Val passedToType;
		{
			zv::Val contextType = pt_expression_context_get_passed_to_type(context);
			if (UNEXPECTED(contextType.isUndef())) return zv::Val();
			passedToType = getExpectedArrayType(contextType.raw());
			if (UNEXPECTED(passedToType.isUndef())) return zv::Val();
		}
		zv::Val nativePassedToType;
		{
			zv::Val contextType = pt_expression_context_get_native_passed_to_type(context);
			if (UNEXPECTED(contextType.isUndef())) return zv::Val();
			nativePassedToType = getExpectedArrayType(contextType.raw());
			if (UNEXPECTED(nativePassedToType.isUndef())) return zv::Val();
		}
		bool hasExpectedType = !passedToType.isNull() || !nativePassedToType.isNull();
		bool hasLiteralWrite = !literalWrite.isNull();
		zval nextIndex;
		ZVAL_LONG(&nextIndex, 0);

		zend_class_entry *arrayCe = NULL;
		zend_class_entry *closureCe = NULL;
		zend_class_entry *arrowFunctionCe = NULL;
		if (hasExpectedType) {
			arrayCe = pt_class(PT_CLASS_ARRAY_EXPR);
			closureCe = pt_class(PT_CLASS_CLOSURE_EXPR);
			arrowFunctionCe = pt_class(PT_CLASS_ARROW_FUNCTION);
			if (UNEXPECTED(arrayCe == NULL || closureCe == NULL || arrowFunctionCe == NULL)) return zv::Val();
		}

		zv::Val hold;
		if (itemCount > 0) {
			for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(items.raw()))) {
				zval *arrayItem = entry.value().deref().raw();
				if (UNEXPECTED(Z_TYPE_P(arrayItem) != IS_OBJECT)) {
					// Array_ items are ArrayItem nodes (an empty element is a parse error)
					zend_type_error("PHPStan\\Node\\LiteralArrayItem::__construct(): Argument #2 ($arrayItem) must be of type ?PhpParser\\Node\\ArrayItem, %s given", zend_zval_value_name(arrayItem));
					return zv::Val();
				}
				{
					zv::Args itemNodeArgv{scope.raw(), arrayItem};
					zv::Val itemNode = pt_type_new(PT_CLASS_LITERAL_ARRAY_ITEM, 2, itemNodeArgv);
					if (UNEXPECTED(itemNode.isUndef())) return zv::Val();
					itemNodes.push(std::move(itemNode));
				}
				zv::Val itemCallbackScope = zv::Val::copyOf(zv::Ref(scope.raw()));
				zv::Val keyResult;
				zval *key = itemKey(arrayItem);
				if (UNEXPECTED(key == NULL)) return zv::Val();
				if (Z_TYPE_P(key) != IS_NULL) {
					zv::Val keyContext = pt_expression_context_enter_deep_keeping_value_flow(context);
					if (UNEXPECTED(keyContext.isUndef())) return zv::Val();
					zv::Val heldKey = zv::Val::copyOf(zv::Ref(key));
					keyResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, heldKey.raw(), scope.raw(), storage, nodeCallback, keyContext.raw());
					if (UNEXPECTED(keyResult.isUndef())) return zv::Val();
					if (UNEXPECTED(Z_TYPE_P(heldKey.raw()) != IS_OBJECT)) {
						zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(heldKey.raw()));
						return zv::Val();
					}
					if (UNEXPECTED(!storeItemResult(itemResults, NULL, heldKey.raw(), keyResult.raw(), variableFlows, hasYield, throwPoints, impurePoints, isAlwaysTerminating, scope, hold))) return zv::Val();
				}

				zval *unpack = itemUnpack(arrayItem);
				if (UNEXPECTED(unpack == NULL)) return zv::Val();
				bool isUnpack = zend_is_true(unpack);
				zv::Val valueContext;
				if (!hasLiteralWrite) {
					valueContext = pt_expression_context_enter_deep_keeping_value_flow(context);
					if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
				}
				zv::Val keyType;
				if (hasExpectedType && !isUnpack) {
					zval *value = itemValue(arrayItem);
					if (UNEXPECTED(value == NULL)) return zv::Val();
					if (Z_TYPE_P(value) == IS_OBJECT && (instanceof_function(Z_OBJCE_P(value), arrayCe) || instanceof_function(Z_OBJCE_P(value), closureCe) || instanceof_function(Z_OBJCE_P(value), arrowFunctionCe))) {
						if (!keyResult.isUndef()) {
							zv::Val keyResultType = pt_expression_result_get_type(keyResult.raw());
							if (UNEXPECTED(keyResultType.isUndef())) return zv::Val();
							if (UNEXPECTED(!keyResultType.ref().isObject())) {
								zend_throw_error(NULL, "Call to a member function toArrayKey() on %s", zend_zval_value_name(keyResultType.raw()));
								return zv::Val();
							}
							keyType = pt_type_op(Z_OBJ_P(keyResultType.raw()), PT_OP_TO_ARRAY_KEY, 0, NULL);
						} else if (isSet(&nextIndex)) {
							keyType = newConstantIntegerTypeOf(&nextIndex);
						} else {
							zval out;
							if (UNEXPECTED(!pt_integer_type_new(&out))) return zv::Val();
							keyType = zv::Val::adopt(out);
						}
						if (UNEXPECTED(keyType.isUndef())) return zv::Val();
					}
				}
				zval offset = {};
				ZVAL_NULL(&offset);
				zv::Val offsetHold;
				if (hasLiteralWrite || hasExpectedType) {
					if (isUnpack) {
						ZVAL_NULL(&nextIndex);
					} else if (keyResult.isUndef()) {
						ZVAL_COPY_VALUE(&offset, &nextIndex);
						if (isSet(&nextIndex)) increment_function(&nextIndex);
					} else {
						zv::Val keyResultType = pt_expression_result_get_type(keyResult.raw());
						if (UNEXPECTED(keyResultType.isUndef())) return zv::Val();
						offsetHold = pt_variable_write_offset_from_type(keyResultType.raw());
						if (UNEXPECTED(offsetHold.isUndef())) return zv::Val();
						ZVAL_COPY_VALUE(&offset, offsetHold.raw());
						if (Z_TYPE(offset) == IS_NULL) {
							ZVAL_NULL(&nextIndex);
						} else if (Z_TYPE(offset) == IS_LONG && isSet(&nextIndex)) {
							advanceNextIndex(&nextIndex, Z_LVAL(offset));
						}
					}
				}
				if (hasLiteralWrite) {
					zv::Val variableName = variableWriteGetter(literalWrite.raw(), &pt_variable_write_slots::variableName, PT_LC("getvariablename"));
					if (UNEXPECTED(variableName.isUndef())) return zv::Val();
					zv::Val parentId = variableWriteGetter(literalWrite.raw(), &pt_variable_write_slots::id, PT_LC("getid"));
					if (UNEXPECTED(parentId.isUndef())) return zv::Val();
					zv::Args writeArgv{variableName.raw(), arrayItem, (zend_long) Z_OBJ_HANDLE_P(arrayItem), PT_ARH_KIND_ARRAY_LITERAL_ITEM, true, &offset, parentId.raw()};
					zv::Val itemWrite = pt_type_new(PT_CLASS_VARIABLE_WRITE, 7, writeArgv);
					if (UNEXPECTED(itemWrite.isUndef())) return zv::Val();
					zv::Val writeFlow = pt_variable_flow_write(itemWrite.raw(), NULL);
					if (UNEXPECTED(writeFlow.isUndef())) return zv::Val();
					variableFlows.push(std::move(writeFlow));
					zv::Val deepContext = pt_expression_context_enter_deep(context);
					if (UNEXPECTED(deepContext.isUndef())) return zv::Val();
					valueContext = pt_expression_context_enter_value_flow(deepContext.raw(), itemWrite.raw(), false);
					if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
				}
				if (!keyType.isUndef()) {
					zv::Val expectedValueType = getExpectedValueType(passedToType.raw(), keyType.raw());
					if (UNEXPECTED(expectedValueType.isUndef())) return zv::Val();
					zv::Val expectedNativeValueType = getExpectedValueType(nativePassedToType.raw(), keyType.raw());
					if (UNEXPECTED(expectedNativeValueType.isUndef())) return zv::Val();
					valueContext = pt_expression_context_enter_passed_to_type(valueContext.raw(), expectedValueType.raw(), expectedNativeValueType.raw());
					if (UNEXPECTED(valueContext.isUndef())) return zv::Val();
				}
				zval *value = itemValue(arrayItem);
				if (UNEXPECTED(value == NULL)) return zv::Val();
				zv::Val heldValue = zv::Val::copyOf(zv::Ref(value));
				zv::Val valueResult = pt_node_scope_resolver_process_expr_node(nodeScopeResolver, stmt, heldValue.raw(), scope.raw(), storage, nodeCallback, valueContext.raw());
				if (UNEXPECTED(valueResult.isUndef())) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(heldValue.raw()) != IS_OBJECT)) {
					zend_type_error("spl_object_id(): Argument #1 ($object) must be of type object, %s given", zend_zval_value_name(heldValue.raw()));
					return zv::Val();
				}
				if (UNEXPECTED(!storeItemResult(itemResults, arrayItem, heldValue.raw(), valueResult.raw(), variableFlows, hasYield, throwPoints, impurePoints, isAlwaysTerminating, scope, hold))) return zv::Val();

				// the item's callback fires after its key and value were processed,
				// with the item's entry scope
				if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, arrayItem, itemCallbackScope.raw(), storage))) return zv::Val();
			}
		}
		{
			zv::Val itemNodesValue(std::move(itemNodes));
			zv::Args nodeArgv{expr, itemNodesValue.raw()};
			zv::Val literalArrayNode = pt_type_new(PT_CLASS_LITERAL_ARRAY_NODE, 2, nodeArgv);
			if (UNEXPECTED(literalArrayNode.isUndef())) return zv::Val();
			if (UNEXPECTED(!pt_node_scope_resolver_call_node_callback(nodeScopeResolver, nodeCallback, literalArrayNode.raw(), scope.raw(), storage))) return zv::Val();
		}

		zv::Val variableFlow = pt_variable_flow_sequence_list(variableFlows.table());
		if (UNEXPECTED(variableFlow.isUndef())) return zv::Val();
		zv::Val itemResultsValue(std::move(itemResults));
		zv::Val typeCallback = pt_native_closure(&typeCallbackBody, self, expr, itemResultsValue.raw(), beforeScope);
		zv::Val specifyTypesCallback = pt_specified_types_empty_specify_callback();
		if (UNEXPECTED(specifyTypesCallback.isUndef())) return zv::Val();

		pt_expression_result_args args(scope.raw(), beforeScope, expr, hasYield, isAlwaysTerminating, throwPoints.raw(), impurePoints.raw(), typeCallback.raw(), specifyTypesCallback.raw());
		args.withVariableFlow(variableFlow.raw());
		return pt_expression_result_create(OBJ_PROP_NUM(self, slots::expressionResultFactory), args);
	}

	/* the handler entry (Engine.h) */
	static zv::Val processExprEntry(zend_object *handler, zval *nodeScopeResolver, zval *stmt, zval *expr, zval *scope, zval *storage, zval *nodeCallback, zval *context)
	{
		return ArrayHandler(handler).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context);
	}

private:
	zend_object *self;

	/* $itemResults[spl_object_id($node)] = $result; $variableFlows[] =
	 * $result->getVariableFlow(); (for a value: the escape of a by-reference
	 * item); the $hasYield / $throwPoints / $impurePoints /
	 * $isAlwaysTerminating folds; $scope = $result->getScope(); false =
	 * pending exception */
	[[nodiscard]] static bool storeItemResult(zv::Arr &itemResults, zval *arrayItem, zval *node, zval *result, zv::Arr &variableFlows, bool &hasYield, zv::Val &throwPoints, zv::Val &impurePoints, bool &isAlwaysTerminating, zv::Val &scope, zv::Val &hold)
	{
		zval stored;
		ZVAL_COPY(&stored, result);
		zend_hash_index_update(itemResults.table(), Z_OBJ_HANDLE_P(node), &stored);
		zv::Val flow = pt_expression_result_variable_flow(result);
		if (UNEXPECTED(flow.isUndef())) return false;
		variableFlows.push(std::move(flow));
		if (arrayItem != NULL) {
			// only the value side: `if ($arrayItem->byRef)`
			zval *byRef = itemByRef(arrayItem);
			if (UNEXPECTED(byRef == NULL)) return false;
			if (zend_is_true(byRef)) {
				zval *value = itemValue(arrayItem);
				if (UNEXPECTED(value == NULL)) return false;
				zv::Val escape = pt_variable_flow_builder_escape_root(value);
				if (UNEXPECTED(escape.isUndef())) return false;
				variableFlows.push(std::move(escape));
			}
		}
		if (!hasYield && UNEXPECTED(!pt_expression_result_has_yield(result, hasYield))) return false;
		zval *borrowed = pt_expression_result_throw_points(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		throwPoints = arrayMerge(throwPoints.raw(), borrowed);
		borrowed = pt_expression_result_impure_points(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		impurePoints = arrayMerge(impurePoints.raw(), borrowed);
		if (!isAlwaysTerminating && UNEXPECTED(!pt_expression_result_is_always_terminating(result, isAlwaysTerminating))) return false;
		borrowed = pt_expression_result_scope(result, hold);
		if (UNEXPECTED(borrowed == NULL)) return false;
		scope = zv::Val::copyOf(zv::Ref(borrowed));
		return true;
	}

	/* Mirrors getExpectedArrayType(); $type NULL / IS_NULL for null; PHP null
	 * for null, UNDEF = pending exception */
	static zv::Val getExpectedArrayType(zval *type)
	{
		if (type == NULL || Z_TYPE_P(type) == IS_NULL) return zv::Val::null();
		zend_long isIterable = pt_type_call_trinary(Z_OBJ_P(type), PT_LC("isiterable"), 0, NULL);
		if (UNEXPECTED(isIterable < 0)) return zv::Val();
		if (isIterable == PT_TRI_NO) return zv::Val::null();

		zend_long isArray = pt_type_op_trinary(Z_OBJ_P(type), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(isArray < 0)) return zv::Val();
		if (isArray == PT_TRI_YES) return zv::Val::copyOf(zv::Ref(type));

		zv::Val keyType = pt_type_new_mixed_type();
		if (UNEXPECTED(keyType.isUndef())) return zv::Val();
		zv::Val itemType = pt_type_new_mixed_type();
		if (UNEXPECTED(itemType.isUndef())) return zv::Val();
		zval arrayType;
		if (UNEXPECTED(!pt_array_type_new(&arrayType, keyType.raw(), itemType.raw()))) return zv::Val();
		zv::Val arrayTypeValue = zv::Val::adopt(arrayType);
		zv::Args argv{type, arrayTypeValue.raw()};
		return pt_type_combinator_intersect(2, argv);
	}

	/* Mirrors getExpectedValueType(); PHP null for null, UNDEF = pending
	 * exception */
	static zv::Val getExpectedValueType(zval *arrayType, zval *keyType)
	{
		if (Z_TYPE_P(arrayType) == IS_NULL) return zv::Val::null();
		zend_long hasOffsetValueType = pt_type_op_trinary(Z_OBJ_P(arrayType), PT_OP_HAS_OFFSET_VALUE_TYPE, 1, keyType);
		if (UNEXPECTED(hasOffsetValueType < 0)) return zv::Val();
		if (hasOffsetValueType == PT_TRI_NO) return zv::Val::null();

		return pt_type_op(Z_OBJ_P(arrayType), PT_OP_GET_OFFSET_VALUE_TYPE, 1, keyType);
	}

	/* static function (Expr $inner) use ($itemResults, $nativeTypesPromoted):
	 * Type — InitializerExprTypeResolver calls it synchronously, over the
	 * typeCallback's frame */
	struct ItemTypeFrame
	{
		zval *itemResults;
		bool nativeTypesPromoted;
	};

	static zv::Val itemTypeCallback(void *data, zval *inner)
	{
		ItemTypeFrame *frame = static_cast<ItemTypeFrame *>(data);
		ZVAL_DEREF(inner);
		zend_class_entry *exprCe = pt_class(PT_CLASS_EXPR);
		if (UNEXPECTED(exprCe == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(inner) != IS_OBJECT || !instanceof_function(Z_OBJCE_P(inner), exprCe))) {
			zend_type_error("%s(): Argument #1 ($inner) must be of type PhpParser\\Node\\Expr, %s given", pt_arh_closure_name, zend_zval_value_name(inner));
			return zv::Val();
		}
		zval *itemResult = zend_hash_index_find(Z_ARRVAL_P(frame->itemResults), Z_OBJ_HANDLE_P(inner));
		if (UNEXPECTED(itemResult == NULL)) {
			pt_throw_should_not_happen();
			return zv::Val();
		}
		return frame->nativeTypesPromoted ? pt_expression_result_get_native_type(itemResult) : pt_expression_result_get_type(itemResult);
	}

	/* the callback as a PHP callable that outlives the call (the resolver
	 * hands it to OversizedArrayBuilder): the closure over copies of
	 * $itemResults and $nativeTypesPromoted */
	static zv::Val itemTypeCallable(void *data)
	{
		ItemTypeFrame *frame = static_cast<ItemTypeFrame *>(data);
		return pt_native_closure(&itemTypeCallbackBody, frame->itemResults, frame->nativeTypesPromoted);
	}

	/* the same closure called from PHP — captures: $itemResults,
	 * $nativeTypesPromoted */
	static void itemTypeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_arh_closure_name))) return;
		ItemTypeFrame frame{&captures[0], Z_TYPE(captures[1]) == IS_TRUE};
		zv::Val type = itemTypeCallback(&frame, &argv[0]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}

	/* the typeCallback's body */
	zv::Val resolveType(bool nativeTypesPromoted, zval *expr, zval *itemResults, zval *beforeScope) const
	{
		zv::Val type;
		{
			ItemTypeFrame frame{itemResults, nativeTypesPromoted};
			pt_ietr_get_type getTypeCallback{&itemTypeCallback, &frame, &itemTypeCallable};
			type = getArrayType(OBJ_PROP_NUM(self, slots::initializerExprTypeResolver), expr, getTypeCallback);
			if (UNEXPECTED(type.isUndef())) return zv::Val();
		}

		zval *items = exprItems(expr);
		if (UNEXPECTED(items == NULL)) return zv::Val();
		if (UNEXPECTED(Z_TYPE_P(items) != IS_ARRAY)) {
			zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(items));
			return zv::Val();
		}
		if (zend_hash_num_elements(Z_ARRVAL_P(items)) != 2) return type;
		zval *first = zend_hash_index_find(Z_ARRVAL_P(items), 0);
		if (first == NULL || Z_TYPE_P(first) == IS_NULL) return type;
		zval *second = zend_hash_index_find(Z_ARRVAL_P(items), 1);
		if (second == NULL || Z_TYPE_P(second) == IS_NULL) return type;
		zv::Val isCallableCall;
		{
			zv::Val name = pt_type_new(PT_CLASS_FULLY_QUALIFIED, 1, zv::Args{pt_arh_is_callable});
			if (UNEXPECTED(name.isUndef())) return zv::Val();
			zv::Val arg = pt_type_new(PT_CLASS_ARG, 1, expr);
			if (UNEXPECTED(arg.isUndef())) return zv::Val();
			zv::Arr callArgs = zv::Arr::create(1);
			callArgs.push(std::move(arg));
			zv::Val callArgsValue(std::move(callArgs));
			zv::Args callArgv{name.raw(), callArgsValue.raw()};
			isCallableCall = pt_type_new(PT_CLASS_FUNC_CALL, 2, callArgv);
			if (UNEXPECTED(isCallableCall.isUndef())) return zv::Val();
		}
		zend_long hasExpressionType = pt_mutating_scope_has_expression_type(Z_OBJ_P(beforeScope), isCallableCall.raw());
		if (UNEXPECTED(hasExpressionType < 0)) return zv::Val();
		if (hasExpressionType != PT_TRI_YES) return type;
		// the narrowed type read from expressionTypes directly
		zv::Val trackedType = pt_mutating_scope_get_tracked_expression_type(Z_OBJ_P(beforeScope), Z_OBJ_P(isCallableCall.raw()));
		if (UNEXPECTED(trackedType.isUndef())) return zv::Val();
		if (UNEXPECTED(!trackedType.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isTrue() on %s", zend_zval_value_name(trackedType.raw()));
			return zv::Val();
		}
		zend_long isTrue = pt_type_call_trinary(Z_OBJ_P(trackedType.raw()), PT_LC("istrue"), 0, NULL);
		if (UNEXPECTED(isTrue < 0)) return zv::Val();
		if (isTrue != PT_TRI_YES) return type;
		// isCallable() is asked last - it reflects the class named by the first
		// item, which is expensive and unnecessary for arrays never narrowed by
		// is_callable()
		if (UNEXPECTED(!type.ref().isObject())) {
			zend_throw_error(NULL, "Call to a member function isCallable() on %s", zend_zval_value_name(type.raw()));
			return zv::Val();
		}
		zend_long isCallable = pt_type_op_trinary(Z_OBJ_P(type.raw()), PT_OP_IS_CALLABLE, 0, NULL);
		if (UNEXPECTED(isCallable < 0)) return zv::Val();
		if (isCallable != PT_TRI_MAYBE) return type;

		zval callableType;
		if (UNEXPECTED(!pt_callable_type_new(&callableType))) return zv::Val();
		zv::Val callableTypeValue = zv::Val::adopt(callableType);
		zv::Args intersectArgv{type.raw(), callableTypeValue.raw()};
		return pt_type_combinator_intersect(2, intersectArgv);
	}

	/* function (bool $nativeTypesPromoted) use ($expr, $itemResults,
	 * $beforeScope): Type — captures: $this, $expr, $itemResults, $beforeScope */
	static void typeCallbackBody(zval *captures, uint32_t argc, zval *argv, zval *return_value)
	{
		if (UNEXPECTED(!requireArguments(argc, 1, pt_arh_closure_name))) return;
		zv::Val type = ArrayHandler(Z_OBJ(captures[0])).resolveType(zend_is_true(&argv[0]), &captures[1], &captures[2], &captures[3]);
		if (UNEXPECTED(type.isUndef())) return;
		type.intoReturnValue(return_value);
	}
};

} // namespace phpstanturbo

using phpstanturbo::ArrayHandler;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_array_handler()
{
	pt_arh_is_callable = zend_string_init_interned(PT_LC("is_callable"), 1);

	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\ArrayHandler");
	ptdecl::ArrayHandler::declareClass(cls);
	ptdecl::ArrayHandler::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *initializerExprTypeResolver, *expressionResultFactory;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, initializerExprTypeResolver, expressionResultFactory)) RETURN_THROWS();
		ArrayHandler(Z_OBJ_P(ZEND_THIS)).construct(initializerExprTypeResolver, expressionResultFactory);
	});

	cls.method<&ArrayHandler::supports, zp::Obj>(sigs::supports);

	cls.method(sigs::processExpr, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *nodeScopeResolver, *stmt, *expr, *scope, *storage, *nodeCallback, *context;
		ZEND_PARSE_PARAMETERS_START(7, 7)
			Z_PARAM_OBJECT(nodeScopeResolver)
			Z_PARAM_OBJECT(stmt)
			Z_PARAM_OBJECT(expr)
			Z_PARAM_OBJECT(scope)
			Z_PARAM_OBJECT(storage)
			Z_PARAM_ZVAL(nodeCallback)
			Z_PARAM_OBJECT(context)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(ArrayHandler(Z_OBJ_P(ZEND_THIS)).processExpr(nodeScopeResolver, stmt, expr, scope, storage, nodeCallback, context));
	});

	cls.shadow(&pt_ce_array_handler);
	pt_expr_handler_entry_register(&pt_ce_array_handler, &ArrayHandler::processExprEntry);
}

/* }}} */
