/*
 * PHPStanTurbo\CountNarrowingHelper — native implementation of
 * PHPStan\Analyser\ExprHandler\Helper\CountNarrowingHelper.
 *
 * A DI service (#[AutowiredService]): the constructor keeps the twin's
 * arginfo so Nette autowires it. Its two public methods are exported for
 * BinaryOpHandler.cpp and IdenticalNarrowingHelper.cpp as
 * pt_count_narrowing_helper_is_normal_count_call() (the PT_TRI_* value of the
 * TrinaryLogic the method returns) and
 * pt_count_narrowing_helper_specify_count_size().
 *
 * CallLike::getArgs(), MutatingScope, ExpressionResultStorage,
 * ExpressionResult, TypeSpecifierContext, SpecifiedTypes, ExprPrinter,
 * DefaultNarrowingHelper, TypeCombinator, IntegerRangeType and the rest of
 * the Type kernel are called through their direct entries; the Type methods
 * without one (getArraySize(), getArrays(), truncateListToSize()) through
 * the engine.
 */

#include "support.h"
#include "generated/CountNarrowingHelper.h"

namespace slots = ptdecl::CountNarrowingHelper::slot;
namespace sigs = ptdecl::CountNarrowingHelper::sig;
#include "SimpleExprHandlers.h"

zend_class_entry *pt_ce_count_narrowing_helper = nullptr;

namespace {

using phpstanturbo::NullableLong;
using phpstanturbo::visitors::NodeProp;

NodeProp pt_cnh_arg_value = PT_NODE_PROP(PT_CLASS_ARG, "value");

/* $countFuncCall->getArgs()[$index]->value (dereferenced); NULL with the
 * engine's warning / Error pending */
zval *argValue(zval *countFuncCall, zend_ulong index)
{
	zv::Val hold;
	zval *args = pt_call_like_args(Z_OBJ_P(countFuncCall), hold);
	if (UNEXPECTED(args == NULL)) return NULL;
	zval *arg = Z_TYPE_P(args) == IS_ARRAY ? zend_hash_index_find(Z_ARRVAL_P(args), index) : NULL;
	if (arg != NULL) ZVAL_DEREF(arg);
	if (UNEXPECTED(arg == NULL)) {
		zend_error(E_WARNING, "Undefined array key " ZEND_ULONG_FMT, index);
		if (UNEXPECTED(EG(exception))) return NULL;
		zend_throw_error(NULL, "Attempt to read property \"value\" on null");
		return NULL;
	}
	if (UNEXPECTED(Z_TYPE_P(arg) != IS_OBJECT)) {
		zend_throw_error(NULL, "Attempt to read property \"value\" on %s", zend_zval_value_name(arg));
		return NULL;
	}
	/* the args array (and so the argument) stays alive in the call node */
	return ptoh::operand(pt_cnh_arg_value, arg);
}

/* $a->isSuperTypeOf($b) as a PT_TRI_* value; -1 = pending exception */
zend_long superTypeOf(zval *a, zval *b)
{
	if (UNEXPECTED(Z_TYPE_P(a) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function isSuperTypeOf() on %s", zend_zval_value_name(a));
		return -1;
	}
	zv::Val result = pt_type_op(Z_OBJ_P(a), PT_OP_IS_SUPER_TYPE_OF, 1, b);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_result_trinary(result.raw());
}

/* $type->getArraySize() */
zv::Val arraySizeOf(zval *type)
{
	if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function getArraySize() on %s", zend_zval_value_name(type));
		return zv::Val();
	}
	return pt_type_call(Z_OBJ_P(type), PT_LC("getarraysize"), 0, NULL);
}

/* a TypeSpecifierContext singleton as a zval (borrowed) */
inline zval objectZval(zend_object *object)
{
	zval z;
	ZVAL_OBJ(&z, object);
	return z;
}

/* $specifiedTypes->setRootExpr($rootExpr) of a value that must be an object */
zv::Val setRootExpr(zv::Val specifiedTypes, zval *rootExpr)
{
	if (UNEXPECTED(specifiedTypes.isUndef())) return zv::Val();
	if (UNEXPECTED(Z_TYPE_P(specifiedTypes.raw()) != IS_OBJECT)) {
		zend_throw_error(NULL, "Call to a member function setRootExpr() on %s", zend_zval_value_name(specifiedTypes.raw()));
		return zv::Val();
	}
	return pt_specified_types_set_root_expr(Z_OBJ_P(specifiedTypes.raw()), rootExpr);
}

/* TypeCombinator::union(...$types) of a list built by push */
zv::Val unionOf(zv::Arr &types)
{
	HashTable *table = types.table();
	uint32_t count = zend_hash_num_elements(table);
	return pt_type_combinator_union(count, count > 0 ? table->arPacked : NULL);
}

/* [$argExpr, new HasOffsetValueType(new ConstantIntegerType($offset), new MixedType())] */
zv::Val offsetEntry(zval *argExpr, zend_long offset)
{
	zval offsetType;
	if (UNEXPECTED(!pt_constant_integer_type_new(&offsetType, offset))) return zv::Val();
	zv::Val offsetValue = zv::Val::adopt(offsetType);
	zv::Val mixed = pt_type_new_mixed_type();
	if (UNEXPECTED(mixed.isUndef())) return zv::Val();
	zval hasOffsetValue;
	if (UNEXPECTED(!pt_has_offset_value_type_new(&hasOffsetValue, offsetValue.raw(), mixed.raw()))) return zv::Val();
	zv::Arr entry = zv::Arr::create(2);
	entry.push(zv::Ref(argExpr));
	entry.push(zv::Val::adopt(hasOffsetValue));
	return zv::Val(std::move(entry));
}

} // namespace

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExprHandler\Helper\CountNarrowingHelper; UNDEF =
 * pending exception. */
class CountNarrowingHelper
{
public:
	explicit CountNarrowingHelper(zend_object *self) : self(self) {}

	/* the constructor body: the promoted properties */
	void construct(zval *defaultNarrowingHelper, zval *exprPrinter) const
	{
		pt_write_slot(self, slots::defaultNarrowingHelper, defaultNarrowingHelper);
		pt_write_slot(self, slots::exprPrinter, exprPrinter);
	}

	/* Mirrors isNormalCountCall(): the PT_TRI_* value; -1 = pending exception */
	zend_long isNormalCountCall(zval *countFuncCall, zval *typeToCount, zval *scope) const
	{
		{
			zv::Val hold;
			zval *args = pt_call_like_args(Z_OBJ_P(countFuncCall), hold);
			if (UNEXPECTED(args == NULL)) return -1;
			if (UNEXPECTED(Z_TYPE_P(args) != IS_ARRAY)) {
				zend_type_error("count(): Argument #1 ($value) must be of type Countable|array, %s given", zend_zval_value_name(args));
				return -1;
			}
			if (zend_hash_num_elements(Z_ARRVAL_P(args)) == 1) return PT_TRI_YES;
		}

		zval *modeArg = argValue(countFuncCall, 1);
		if (UNEXPECTED(modeArg == NULL)) return -1;
		// the mode argument was processed with the call - a census over the suite
		// and self-analysis found every ask answered from the stored result
		zv::Val storage = pt_mutating_scope_get_current_expression_result_storage(Z_OBJ_P(scope));
		if (UNEXPECTED(storage.isUndef())) return -1;
		zv::Val modeResult = zv::Val::null();
		if (!storage.isNull()) {
			modeResult = pt_expression_result_storage_find(storage.raw(), modeArg);
			if (UNEXPECTED(modeResult.isUndef())) return -1;
		}
		if (modeResult.isNull()) {
			zv::Val startLine = pt_engine_node_get_attribute(Z_OBJ_P(modeArg), PT_LC("startLine"));
			if (UNEXPECTED(startLine.isUndef())) return -1;
			zend_long line = Z_TYPE_P(startLine.raw()) == IS_NULL ? -1 : zval_get_long(startLine.raw());
			zend_string *message = zend_strpprintf(0, "count() mode argument on line " ZEND_LONG_FMT " has no stored ExpressionResult.", line);
			zval messageZv;
			ZVAL_STR(&messageZv, message);
			zv::Val exception = pt_type_new(PT_CLASS_SHOULD_NOT_HAPPEN, 1, &messageZv);
			zend_string_release(message);
			if (UNEXPECTED(exception.isUndef())) return -1;
			zval raw = exception.take();
			zend_throw_exception_object(&raw);
			return -1;
		}
		bool nativeTypesPromoted;
		if (UNEXPECTED(!pt_mutating_scope_native_types_promoted(Z_OBJ_P(scope), nativeTypesPromoted))) return -1;
		zv::Val mode = pt_expression_result_get_type_on_scope(modeResult.raw(), scope, nativeTypesPromoted);
		if (UNEXPECTED(mode.isUndef())) return -1;

		zval countNormal;
		if (UNEXPECTED(!pt_constant_integer_type_new(&countNormal, 0))) return -1;
		zv::Val countNormalType = zv::Val::adopt(countNormal);
		zend_long isNormal = superTypeOf(countNormalType.raw(), mode.raw());
		if (UNEXPECTED(isNormal < 0)) return -1;

		if (UNEXPECTED(Z_TYPE_P(typeToCount) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function getIterableValueType() on %s", zend_zval_value_name(typeToCount));
			return -1;
		}
		zv::Val valueType = pt_type_op(Z_OBJ_P(typeToCount), PT_OP_GET_ITERABLE_VALUE_TYPE, 0, NULL);
		if (UNEXPECTED(valueType.isUndef())) return -1;
		if (UNEXPECTED(Z_TYPE_P(valueType.raw()) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isArray() on %s", zend_zval_value_name(valueType.raw()));
			return -1;
		}
		zend_long valueIsArray = pt_type_op_trinary(Z_OBJ_P(valueType.raw()), PT_OP_IS_ARRAY, 0, NULL);
		if (UNEXPECTED(valueIsArray < 0)) return -1;
		zend_long negated = valueIsArray == PT_TRI_YES ? PT_TRI_NO : (valueIsArray == PT_TRI_NO ? PT_TRI_YES : PT_TRI_MAYBE);

		return pt_trinary_or(isNormal, negated);
	}

	/* Mirrors specifyCountSize(): a SpecifiedTypes or null */
	zv::Val specifyCountSize(zval *countFuncCall, zval *type, zval *sizeType, zval *context, zval *scope, zval *rootExpr) const
	{
		if (UNEXPECTED(Z_TYPE_P(type) != IS_OBJECT)) {
			zend_throw_error(NULL, "Call to a member function isConstantArray() on %s", zend_zval_value_name(type));
			return zv::Val();
		}
		zend_object *typeObject = Z_OBJ_P(type);
		zend_long isConstantArray = pt_type_op_trinary(typeObject, PT_OP_IS_CONSTANT_ARRAY, 0, NULL);
		if (UNEXPECTED(isConstantArray < 0)) return zv::Val();
		zend_long isList = pt_type_op_trinary(typeObject, PT_OP_IS_LIST, 0, NULL);
		if (UNEXPECTED(isList < 0)) return zv::Val();
		zv::Val oneOrMore = pt_integer_range_from_interval(NullableLong::of(1), NullableLong::null(), 0);
		if (UNEXPECTED(oneOrMore.isUndef())) return zv::Val();

		{
			zend_long isNormal = isNormalCountCall(countFuncCall, type, scope);
			if (UNEXPECTED(isNormal < 0)) return zv::Val();
			if (isNormal != PT_TRI_YES) return zv::Val::null();
			if (isConstantArray != PT_TRI_YES && isList != PT_TRI_YES) return zv::Val::null();
			zend_long sizeInRange = superTypeOf(oneOrMore.raw(), sizeType);
			if (UNEXPECTED(sizeInRange < 0)) return zv::Val();
			if (sizeInRange != PT_TRI_YES) return zv::Val::null();
			zv::Val arraySize = arraySizeOf(type);
			if (UNEXPECTED(arraySize.isUndef())) return zv::Val();
			zend_long coversArraySize = superTypeOf(sizeType, arraySize.raw());
			if (UNEXPECTED(coversArraySize < 0)) return zv::Val();
			if (coversArraySize == PT_TRI_YES) return zv::Val::null();
		}

		zend_object *contextObject = Z_OBJ_P(context);
		bool falsey;
		if (UNEXPECTED(!pt_type_specifier_context_falsey(contextObject, falsey))) return zv::Val();
		if (falsey && isConstantArray == PT_TRI_YES) {
			zv::Val arraySize = arraySizeOf(type);
			if (UNEXPECTED(arraySize.isUndef())) return zv::Val();
			zv::Val remainingSize = pt_type_combinator_remove(arraySize.raw(), sizeType);
			if (UNEXPECTED(remainingSize.isUndef())) return zv::Val();
			if (!(Z_TYPE_P(remainingSize.raw()) == IS_OBJECT && instanceof_function(Z_OBJCE_P(remainingSize.raw()), pt_ce_never_type))) {
				bool isFalse;
				if (UNEXPECTED(!pt_type_specifier_context_false(contextObject, isFalse))) return zv::Val();
				zend_object *negatedContext = isFalse ? pt_type_specifier_context_create_true() : pt_type_specifier_context_create_truthy();
				if (UNEXPECTED(negatedContext == NULL)) return zv::Val();
				zval negatedContextZv = objectZval(negatedContext);
				zv::Val result;
				pt_engine_with_stack([&]() { result = specifyCountSize(countFuncCall, type, remainingSize.raw(), &negatedContextZv, scope, rootExpr); });
				if (UNEXPECTED(result.isUndef())) return zv::Val();
				if (!result.isNull()) return result;
			}

			// Fallback: directly filter constant arrays by their exact sizes.
			// This avoids using TypeCombinator::remove() with falsey context,
			// which can incorrectly remove arrays whose count doesn't match
			// but whose shape is a subtype of the matched array.
			zv::Val constantArrays = pt_type_op(typeObject, PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
			if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
			zv::Arr keptTypes = zv::Arr::create(Z_TYPE_P(constantArrays.raw()) == IS_ARRAY ? zend_hash_num_elements(Z_ARRVAL_P(constantArrays.raw())) : 0);
			if (Z_TYPE_P(constantArrays.raw()) == IS_ARRAY) {
				for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(constantArrays.raw()))) {
					zval *arrayType = entry.value().deref().raw();
					zv::Val arrayTypeSize = arraySizeOf(arrayType);
					if (UNEXPECTED(arrayTypeSize.isUndef())) return zv::Val();
					zend_long covers = superTypeOf(sizeType, arrayTypeSize.raw());
					if (UNEXPECTED(covers < 0)) return zv::Val();
					if (covers == PT_TRI_YES) continue;

					keptTypes.push(zv::Ref(arrayType));
				}
			}
			if (zend_hash_num_elements(keptTypes.table()) > 0) {
				zval *subject = argValue(countFuncCall, 0);
				if (UNEXPECTED(subject == NULL)) return zv::Val();
				zv::Val keptType = unionOf(keptTypes);
				if (UNEXPECTED(keptType.isUndef())) return zv::Val();
				zv::Val negated = pt_type_specifier_context_negate(contextObject);
				if (UNEXPECTED(negated.isUndef())) return zv::Val();
				return setRootExpr(pt_default_narrowing_helper_create_for_subject(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), subject, keptType.raw(), negated.raw(), scope, NULL), rootExpr);
			}
		}

		zv::Arr resultTypes = zv::Arr::create(0);
		{
			zv::Val arrays = pt_type_call(typeObject, PT_LC("getarrays"), 0, NULL);
			if (UNEXPECTED(arrays.isUndef())) return zv::Val();
			if (Z_TYPE_P(arrays.raw()) == IS_ARRAY) {
				for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(arrays.raw()))) {
					zval *arrayType = entry.value().deref().raw();
					zv::Val arrayTypeSize = arraySizeOf(arrayType);
					if (UNEXPECTED(arrayTypeSize.isUndef())) return zv::Val();
					zend_long isSizeSuperTypeOfArraySize = superTypeOf(sizeType, arrayTypeSize.raw());
					if (UNEXPECTED(isSizeSuperTypeOfArraySize < 0)) return zv::Val();
					if (isSizeSuperTypeOfArraySize == PT_TRI_NO) continue;

					bool contextFalsey;
					if (UNEXPECTED(!pt_type_specifier_context_falsey(contextObject, contextFalsey))) return zv::Val();
					if (contextFalsey && isSizeSuperTypeOfArraySize == PT_TRI_MAYBE) continue;

					zv::Val resultType;
					if (isList == PT_TRI_YES) {
						resultType = pt_type_call(Z_OBJ_P(arrayType), PT_LC("truncatelisttosize"), 1, sizeType);
					} else {
						zval nonEmpty;
						if (UNEXPECTED(!pt_non_empty_array_type_new(&nonEmpty))) return zv::Val();
						zv::Val nonEmptyType = zv::Val::adopt(nonEmpty);
						zv::Args intersectArgs{arrayType, nonEmptyType.raw()};
						resultType = pt_type_combinator_intersect(2, intersectArgs);
					}
					if (UNEXPECTED(resultType.isUndef())) return zv::Val();
					resultTypes.push(std::move(resultType));
				}
			}
		}

		bool truthy;
		if (UNEXPECTED(!pt_type_specifier_context_truthy(contextObject, truthy))) return zv::Val();
		if (truthy && isConstantArray == PT_TRI_YES && isList == PT_TRI_YES) {
			bool hasOptionalKeysOrUnsealed = false;
			zv::Val constantArrays = pt_type_op(typeObject, PT_OP_GET_CONSTANT_ARRAYS, 0, NULL);
			if (UNEXPECTED(constantArrays.isUndef())) return zv::Val();
			if (Z_TYPE_P(constantArrays.raw()) == IS_ARRAY) {
				for (zv::ArrayEntry entry : zv::TableRef(Z_ARRVAL_P(constantArrays.raw()))) {
					zval *arrayType = entry.value().deref().raw();
					if (UNEXPECTED(Z_TYPE_P(arrayType) != IS_OBJECT)) {
						zend_throw_error(NULL, "Call to a member function getOptionalKeys() on %s", zend_zval_value_name(arrayType));
						return zv::Val();
					}
					zv::Val optionalKeys = pt_type_op(Z_OBJ_P(arrayType), PT_OP_GET_OPTIONAL_KEYS, 0, NULL);
					if (UNEXPECTED(optionalKeys.isUndef())) return zv::Val();
					bool hasOptionalKeys = !(Z_TYPE_P(optionalKeys.raw()) == IS_ARRAY && zend_hash_num_elements(Z_ARRVAL_P(optionalKeys.raw())) == 0);
					if (!hasOptionalKeys) {
						zend_long isUnsealed = pt_type_op_trinary(Z_OBJ_P(arrayType), PT_OP_IS_UNSEALED, 0, NULL);
						if (UNEXPECTED(isUnsealed < 0)) return zv::Val();
						hasOptionalKeys = isUnsealed == PT_TRI_YES;
					}
					if (hasOptionalKeys) {
						// Unsealed CATs can't be narrowed via the
						// `HasOffsetValueType`-only shortcut below — the
						// intersection of an unsealed shape with a single-slot
						// constraint produces `NeverType`. Fall through to
						// the full builder-based narrowing, which carries the
						// unsealed slot via the loop above.
						hasOptionalKeysOrUnsealed = true;
						break;
					}
				}
			}

			if (!hasOptionalKeysOrUnsealed) {
				zval *argExpr = argValue(countFuncCall, 0);
				if (UNEXPECTED(argExpr == NULL)) return zv::Val();
				if (UNEXPECTED(Z_TYPE_P(argExpr) != IS_OBJECT)) {
					zend_type_error("PHPStan\\Node\\Printer\\ExprPrinter::printExpr(): Argument #1 ($expr) must be of type PhpParser\\Node\\Expr, %s given", zend_zval_value_name(argExpr));
					return zv::Val();
				}
				zend_string *argExprString = pt_expr_printer_print(OBJ_PROP_NUM(self, slots::exprPrinter), Z_OBJ_P(argExpr));
				if (UNEXPECTED(argExprString == NULL)) return zv::Val();
				zv::Str argExprKey = zv::Str::adopt(argExprString);

				NullableLong sizeMin = NullableLong::null();
				NullableLong sizeMax = NullableLong::null();
				if (Z_TYPE_P(sizeType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(sizeType), pt_ce_constant_integer_type)) {
					zend_long value;
					if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(sizeType), value))) return zv::Val();
					sizeMin = NullableLong::of(value);
					if (UNEXPECTED(!pt_constant_integer_get_value(Z_OBJ_P(sizeType), value))) return zv::Val();
					sizeMax = NullableLong::of(value);
				} else if (Z_TYPE_P(sizeType) == IS_OBJECT && instanceof_function(Z_OBJCE_P(sizeType), pt_ce_integer_range_type)) {
					if (UNEXPECTED(!pt_integer_range_bounds(Z_OBJ_P(sizeType), sizeMin, sizeMax))) return zv::Val();
				}

				zv::Arr sureTypes = zv::Arr::empty();
				zv::Arr sureNotTypes = zv::Arr::empty();

				if (!sizeMin.isNull && sizeMin.value >= 1) {
					zv::Val entry = offsetEntry(argExpr, sizeMin.value - 1);
					if (UNEXPECTED(entry.isUndef())) return zv::Val();
					sureTypes.set(argExprKey.get(), std::move(entry));
				}
				if (!sizeMax.isNull) {
					zv::Val entry = offsetEntry(argExpr, sizeMax.value);
					if (UNEXPECTED(entry.isUndef())) return zv::Val();
					sureNotTypes.set(argExprKey.get(), std::move(entry));
				}

				if (zend_hash_num_elements(sureTypes.table()) > 0 || zend_hash_num_elements(sureNotTypes.table()) > 0) {
					return setRootExpr(pt_specified_types_new(sureTypes.raw(), sureNotTypes.raw()), rootExpr);
				}
			}
		}

		zval *subject = argValue(countFuncCall, 0);
		if (UNEXPECTED(subject == NULL)) return zv::Val();
		zv::Val resultType = unionOf(resultTypes);
		if (UNEXPECTED(resultType.isUndef())) return zv::Val();
		return setRootExpr(pt_default_narrowing_helper_create_for_subject(OBJ_PROP_NUM(self, slots::defaultNarrowingHelper), subject, resultType.raw(), context, scope, NULL), rootExpr);
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::CountNarrowingHelper;

zend_long pt_count_narrowing_helper_is_normal_count_call(zval *helper, zval *countFuncCall, zval *typeToCount, zval *scope)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_count_narrowing_helper)) return CountNarrowingHelper(Z_OBJ_P(helper)).isNormalCountCall(countFuncCall, typeToCount, scope);
	zv::Args argv{countFuncCall, typeToCount, scope};
	zv::Val result = pt_type_call(Z_OBJ_P(helper), PT_LC("isnormalcountcall"), 3, argv);
	if (UNEXPECTED(result.isUndef())) return -1;
	return pt_type_trinary_value(result.raw());
}

zv::Val pt_count_narrowing_helper_specify_count_size(zval *helper, zval *countFuncCall, zval *type, zval *sizeType, zval *context, zval *scope, zval *rootExpr)
{
	if (EXPECTED(Z_OBJCE_P(helper) == pt_ce_count_narrowing_helper)) return CountNarrowingHelper(Z_OBJ_P(helper)).specifyCountSize(countFuncCall, type, sizeType, context, scope, rootExpr);
	zv::Args argv{countFuncCall, type, sizeType, context, scope, rootExpr};
	return pt_type_call(Z_OBJ_P(helper), PT_LC("specifycountsize"), 6, argv);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_count_narrowing_helper)
{
	reg::Class cls("PHPStan\\Analyser\\ExprHandler\\Helper\\CountNarrowingHelper");
	ptdecl::CountNarrowingHelper::declareClass(cls);
	ptdecl::CountNarrowingHelper::declareProperties(cls);

	/* the real parameter class names: the DI container autowires the
	 * service by reflecting the constructor */
	cls.method(sigs::__construct, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *defaultNarrowingHelper, *exprPrinter;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, defaultNarrowingHelper, exprPrinter)) RETURN_THROWS();
		CountNarrowingHelper(Z_OBJ_P(ZEND_THIS)).construct(defaultNarrowingHelper, exprPrinter);
	});

	cls.method(sigs::isNormalCountCall, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *countFuncCall, *typeToCount, *scope;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj>(execute_data, countFuncCall, typeToCount, scope)) RETURN_THROWS();
		zend_long value = CountNarrowingHelper(Z_OBJ_P(ZEND_THIS)).isNormalCountCall(countFuncCall, typeToCount, scope);
		if (UNEXPECTED(value < 0)) RETURN_THROWS();
		RETURN_COPY(pt_trinary_singleton(value));
	});

	cls.method(sigs::specifyCountSize, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *countFuncCall, *type, *sizeType, *context, *scope, *rootExpr;
		if (!zp::parse<zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj, zp::Obj>(execute_data, countFuncCall, type, sizeType, context, scope, rootExpr)) RETURN_THROWS();
		PT_RETURN_VAL(CountNarrowingHelper(Z_OBJ_P(ZEND_THIS)).specifyCountSize(countFuncCall, type, sizeType, context, scope, rootExpr));
	});

	cls.shadow(&pt_ce_count_narrowing_helper);
}

/* }}} */
