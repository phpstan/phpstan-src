/*
 * PHPStanTurbo\RecordingNodeCallback — native implementation of
 * PHPStan\Analyser\RecordingNodeCallback.
 *
 * The node callback of a convergence pass: every (node, scope) emission is
 * appended to $pairs (~660K per self-analysis) and replayed once the pass
 * turns out to be the fixpoint. PHP invokes it as a callable object
 * (`$nodeCallback($node, $scope)` reaches the native __invoke() through the
 * engine's closure handler); native invokers of node callbacks recognize the
 * class in pt_type_call_callable() and record through
 * pt_recording_node_callback_record() without a call. State is the twin's
 * one property slot.
 */

#include "support.h"
#include "generated/RecordingNodeCallback.h"

namespace slots = ptdecl::RecordingNodeCallback::slot;
namespace sigs = ptdecl::RecordingNodeCallback::sig;
#include "zv.h"
#include "TypeTraits.h"

zend_class_entry *pt_ce_recording_node_callback = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Analyser\RecordingNodeCallback. */
class RecordingNodeCallback
{
public:
	explicit RecordingNodeCallback(zend_object *self) : self(self) {}

	/* Mirrors __invoke(): $this->pairs[] = [$node, $scope]; false = pending
	 * exception */
	[[nodiscard]] bool invoke(zval *node, zval *scope) const
	{
		zval *pairs = OBJ_PROP_NUM(self, slots::pairs);
		if (UNEXPECTED(Z_TYPE_P(pairs) != IS_ARRAY)) {
			/* never initialized: the typed property's `[] =` write turns it
			 * into an array, as the engine does */
			zval empty;
			ZVAL_EMPTY_ARRAY(&empty);
			pt_write_slot(self, slots::pairs, &empty);
			pairs = OBJ_PROP_NUM(self, slots::pairs);
		}
		zval pair;
		array_init_size(&pair, 2);
		Z_ADDREF_P(node);
		zend_hash_next_index_insert_new(Z_ARRVAL(pair), node);
		Z_ADDREF_P(scope);
		zend_hash_next_index_insert_new(Z_ARRVAL(pair), scope);
		SEPARATE_ARRAY(pairs);
		if (UNEXPECTED(zend_hash_next_index_insert(Z_ARRVAL_P(pairs), &pair) == NULL)) {
			zval_ptr_dtor(&pair);
			zend_throw_error(NULL, "Cannot add element to the array as the next element is already occupied");
			return false;
		}
		return true;
	}

	zv::Val getPairs() const
	{
		zval *pairs = pt_typed_slot(self, slots::pairs, self->ce, "pairs");
		return pairs != NULL ? zv::Val::copyOf(zv::Ref(pairs)) : zv::Val();
	}

	zv::Val count() const
	{
		zval *pairs = pt_typed_slot(self, slots::pairs, self->ce, "pairs");
		return pairs != NULL ? zv::Val::integer((zend_long) zend_hash_num_elements(Z_ARRVAL_P(pairs))) : zv::Val();
	}

private:
	zend_object *self;
};

} // namespace phpstanturbo

using phpstanturbo::RecordingNodeCallback;

/* {{{ exported helpers: the shadowing class for native callers */

bool pt_recording_node_callback_record(zend_object *callback, zval *node, zval *scope)
{
	return RecordingNodeCallback(callback).invoke(node, scope);
}

/* }}} */

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

PT_MINIT_REGISTRATION(pt_register_recording_node_callback)
{
	reg::Class cls("PHPStan\\Analyser\\RecordingNodeCallback");
	ptdecl::RecordingNodeCallback::declareClass(cls);
	ptdecl::RecordingNodeCallback::declareProperties(cls);

	cls.method(sigs::__invoke, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *node, *scope;
		if (!zp::parse<zp::Obj, zp::Obj>(execute_data, node, scope)) RETURN_THROWS();
		if (UNEXPECTED(!RecordingNodeCallback(Z_OBJ_P(ZEND_THIS)).invoke(node, scope))) RETURN_THROWS();
	});

	cls.method<&RecordingNodeCallback::getPairs>(sigs::getPairs);

	cls.method<&RecordingNodeCallback::count>(sigs::count);

	cls.shadow(&pt_ce_recording_node_callback);
}

/* }}} */
