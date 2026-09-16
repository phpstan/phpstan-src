/*
 * PHPStanTurbo\ExpressionResultStorageStack — native implementation of
 * PHPStan\Analyser\ExpressionResultStorageStack.
 *
 * The whole state is the twin's `private array $stack` in property slot 0:
 * push() appends, pop() drops the last entry (throwing the twin's
 * ShouldNotHappenException on an empty stack) and getCurrent() reads
 * $stack[count($stack) - 1].
 *
 * getCurrent() is asked once per old-world type question the native
 * MutatingScope answers (~1M crossings per self-analysis run), which is why
 * pt_expression_result_storage_stack_current() below reads the slot directly
 * for a native stack instead of calling the method.
 */

#include "support.h"
#include "generated/ExpressionResultStorageStack.h"

namespace slots = ptdecl::ExpressionResultStorageStack::slot;
#include "zv.h"

zend_class_entry *pt_ce_expression_result_storage_stack = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\ExpressionResultStorageStack. State lives in the
 * PHP object's stack property. */
class ExpressionResultStorageStack
{
public:
	explicit ExpressionResultStorageStack(zval *self) : self(self) {}

	void push(zval *storage) { zv::ArrRef(stack()).push(zv::Ref(storage)); }

	/* false = the twin's ShouldNotHappenException on an empty stack */
	bool pop()
	{
		zval *table = stack();
		if (UNEXPECTED(zend_hash_num_elements(Z_ARRVAL_P(table)) == 0)) {
			zend_class_entry *ce = pt_class(PT_CLASS_SHOULD_NOT_HAPPEN);
			if (ce != NULL) {
				zend_throw_exception(ce, "Unbalanced ExpressionResultStorageStack pop.", 0);
			}
			return false;
		}
		arrayPop(table);
		return true;
	}

	/* $this->stack[count($this->stack) - 1], or null on an empty stack; the
	 * stack is a list by construction (push() appends, pop() drops the last
	 * entry), so the count-1 index is always the last element */
	zv::Val getCurrent() const
	{
		zval *table = stack();
		uint32_t count = zend_hash_num_elements(Z_ARRVAL_P(table));
		if (count == 0) return zv::Val::null();
		zv::Ref found = zv::ArrRef(table).findIndex(count - 1);
		if (UNEXPECTED(found.raw() == NULL)) return zv::Val::null();
		return zv::Val::copyOf(found);
	}

private:
	zval *stack() const { return OBJ_PROP_NUM(Z_OBJ_P(self), slots::stack); }

	/* array_pop($array): the last element in order removed, the next free
	 * index pulled back when it was the last appended one */
	static void arrayPop(zval *array)
	{
		SEPARATE_ARRAY(array);
		HashTable *ht = Z_ARRVAL_P(array);
		uint32_t idx = ht->nNumUsed;
		if (HT_IS_PACKED(ht)) {
			while (idx > 0) {
				idx--;
				zval *p = &ht->arPacked[idx];
				if (Z_TYPE_P(p) != IS_UNDEF) {
					if ((zend_long) idx == ht->nNextFreeElement - 1) {
						ht->nNextFreeElement--;
					}
					zend_hash_index_del(ht, idx);
					return;
				}
			}
			return;
		}
		while (idx > 0) {
			idx--;
			Bucket *p = &ht->arData[idx];
			if (Z_TYPE(p->val) != IS_UNDEF) {
				if (p->key == NULL) {
					if ((zend_long) p->h == ht->nNextFreeElement - 1) {
						ht->nNextFreeElement--;
					}
					zend_hash_index_del(ht, p->h);
				} else {
					zend_hash_del(ht, p->key);
				}
				return;
			}
		}
	}

	zval *self;
};

} // namespace phpstanturbo

using phpstanturbo::ExpressionResultStorageStack;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_register_expression_result_storage_stack()
{
	reg::Class cls("PHPStan\\Analyser\\ExpressionResultStorageStack");
	ptdecl::ExpressionResultStorageStack::declareClass(cls);
	cls.privateTypedArrayPropertyDefaultEmpty("stack");

	cls.method("push", reg::Public, 1, { reg::any("storage") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *storage;
		if (!zp::parse<zp::Obj>(execute_data, storage)) RETURN_THROWS();
		ExpressionResultStorageStack(ZEND_THIS).push(storage);
	});

	cls.method("pop", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		if (UNEXPECTED(!ExpressionResultStorageStack(ZEND_THIS).pop())) RETURN_THROWS();
	});

	cls.method("getCurrent", reg::Public, 0, {}, [](INTERNAL_FUNCTION_PARAMETERS) {
		ZEND_PARSE_PARAMETERS_NONE();
		ExpressionResultStorageStack(ZEND_THIS).getCurrent().intoReturnValue(return_value);
	});

	cls.shadow(&pt_ce_expression_result_storage_stack);
}

/* }}} */

/* {{{ direct entry for the native MutatingScope (MutatingScope.cpp): the
 * native body for a native stack, the method for anything else (the PHP twin
 * under the prefixed differential activation) */

zv::Val pt_expression_result_storage_stack_current(zval *stack)
{
	/* the twin is final: an instance of the native class entry takes the
	 * native path */
	if (EXPECTED(Z_OBJCE_P(stack) == pt_ce_expression_result_storage_stack)) return ExpressionResultStorageStack(stack).getCurrent();
	return pt_type_call(Z_OBJ_P(stack), "getcurrent", sizeof("getcurrent") - 1, 0, NULL);
}

bool pt_expression_result_storage_stack_push(zval *stack, zval *storage)
{
	if (EXPECTED(Z_OBJCE_P(stack) == pt_ce_expression_result_storage_stack)) {
		ExpressionResultStorageStack(stack).push(storage);
		return true;
	}
	return !pt_type_call(Z_OBJ_P(stack), "push", sizeof("push") - 1, 1, storage).isUndef();
}

bool pt_expression_result_storage_stack_pop(zval *stack)
{
	if (EXPECTED(Z_OBJCE_P(stack) == pt_ce_expression_result_storage_stack)) return ExpressionResultStorageStack(stack).pop();
	return !pt_type_call(Z_OBJ_P(stack), "pop", sizeof("pop") - 1, 0, NULL).isUndef();
}

/* }}} */
