/*
 * PHPStanTurbo\TypeTraverser — native implementation of
 * PHPStan\Type\TypeTraverser.
 *
 * Not final: the generated stub PHPStan\Type\TypeTraverser extends this
 * class, and map() instantiates the called scope — the stub — so the
 * traverser userland callbacks receive is the class PHPStan's own code
 * knows. State lives in the PHP object's cb property, like the twin's.
 *
 * The recursion is the twin's, method for method: map() creates a traverser,
 * mapInternal() calls the user callback with a bound traverseInternal(), and
 * traverseInternal() hands mapInternal() to Type::traverse(). What the port
 * absorbs is the userland overhead the twin pays around those two calls —
 * per visited node a mapInternal() and a traverseInternal() frame plus a
 * freshly allocated [$this, ...] callable array each, and, for a
 * TypeTraverserCallable, one closure frame on top. Here both bound callables
 * are built once per traversal and reused for every node in it, and a
 * TypeTraverserCallable is dispatched straight to traverse().
 */

#include "support.h"
#include "zv.h"

/* declaration order in pt_register_type_traverser() */
#define PT_TT_PROP_CB 0
#define PT_TT_PROP_MAP_CALLABLE 1
#define PT_TT_PROP_TRAVERSE_CALLABLE 2

/* the traverser kept for the next traversal (see park()); owns a reference */
static zend_object *pt_tt_parked = nullptr;

/* method names of the bound callables; permanent interned strings */
static zend_string *pt_str_map_internal = nullptr;
static zend_string *pt_str_traverse_internal = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\TypeTraverser. */
class TypeTraverser
{
public:
	explicit TypeTraverser(zval *self) : self(self) {}

	/* UNDEF result means a pending exception */
	static zv::Val map(zend_class_entry *scope, zval *type, zval *cb)
	{
		zval selfZv;
		zend_object *reused = takeParked(scope);
		if (reused != NULL) {
			ZVAL_OBJ(&selfZv, reused);
		} else if (UNEXPECTED(object_init_ex(&selfZv, scope) != SUCCESS)) {
			return zv::Val();
		}

		zv::Val owned = zv::Val::adopt(selfZv);
		TypeTraverser traverser(owned.raw());
		traverser.construct(zv::Ref(cb));
		zv::Val result = traverser.mapInternal(type);
		/* the bound callables hold the traverser; dropping them here is what
		 * keeps the object's lifetime tied to this call, as the twin's is */
		traverser.releaseBoundCallables();
		traverser.parkIfUnescaped();

		return result;
	}

	void construct(zv::Ref cb)
	{
		zv::ObjRef(self).propAtWrite(PT_TT_PROP_CB, zv::Val::copyOf(cb));
	}

	/* ($this->cb)($type, [$this, 'traverseInternal']); UNDEF = pending exception */
	zv::Val mapInternal(zval *type)
	{
		zval *cb = OBJ_PROP_NUM(Z_OBJ_P(self), PT_TT_PROP_CB);
		bool isTraverserCallable = false;
		/* the twin wraps a TypeTraverserCallable in a closure once per
		 * traversal; calling traverse() directly saves that frame per node.
		 * Closures are the common cb and never implement the interface. */
		if (Z_TYPE_P(cb) == IS_OBJECT && Z_OBJCE_P(cb) != zend_ce_closure) {
			zend_class_entry *iface = pt_class(PT_CLASS_TYPE_TRAVERSER_CALLABLE);
			if (UNEXPECTED(iface == NULL)) {
				return zv::Val();
			}
			isTraverserCallable = instanceof_function(Z_OBJCE_P(cb), iface);
		}

		zval *traverse = boundCallable(PT_TT_PROP_TRAVERSE_CALLABLE, pt_str_traverse_internal);
		zval args[2];
		ZVAL_COPY_VALUE(&args[0], type);
		ZVAL_COPY_VALUE(&args[1], traverse);

		zval retval;
		ZVAL_UNDEF(&retval);
		if (isTraverserCallable) {
			zend_function *fn = pt_find_method(Z_OBJCE_P(cb), "traverse", sizeof("traverse") - 1);
			if (UNEXPECTED(fn == NULL)) {
				return zv::Val();
			}
			zend_call_known_instance_method(fn, Z_OBJ_P(cb), &retval, 2, args);
		} else if (UNEXPECTED(call_user_function(NULL, NULL, cb, &retval, 2, args) != SUCCESS)) {
			zval_ptr_dtor(&retval);
			return zv::Val();
		}
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&retval);
			return zv::Val();
		}
		/* the twin's ": Type" return type; only the object-ness is checked
		 * here — what the value is gets decided by the Type call it feeds,
		 * and an instanceof against the interface would cost a per-node
		 * interface-table scan */
		if (UNEXPECTED(Z_TYPE(retval) != IS_OBJECT)) {
			zval_ptr_dtor(&retval);
			zend_class_entry *typeCe = pt_class(PT_CLASS_TYPE);
			if (EXPECTED(typeCe != NULL)) {
				zend_type_error("Return value of the callback must be of type %s", ZSTR_VAL(typeCe->name));
			}
			return zv::Val();
		}

		return zv::Val::adopt(retval);
	}

	/* $type->traverse([$this, 'mapInternal']); UNDEF = pending exception */
	zv::Val traverseInternal(zval *type)
	{
		zend_function *fn = pt_find_method(Z_OBJCE_P(type), "traverse", sizeof("traverse") - 1);
		if (UNEXPECTED(fn == NULL)) {
			return zv::Val();
		}

		zval arg;
		ZVAL_COPY_VALUE(&arg, boundCallable(PT_TT_PROP_MAP_CALLABLE, pt_str_map_internal));

		zval retval;
		ZVAL_UNDEF(&retval);
		zend_call_known_instance_method(fn, Z_OBJ_P(type), &retval, 1, &arg);
		if (UNEXPECTED(EG(exception))) {
			zval_ptr_dtor(&retval);
			return zv::Val();
		}

		return zv::Val::adopt(retval);
	}

private:
	zval *self;

	/*
	 * [$this, $method], built on first use and reused for the rest of the
	 * traversal — where the twin allocates one array per node visit. The
	 * returned zval is borrowed from the property slot; callers pass it on as
	 * an argument, which copies it.
	 */
	zval *boundCallable(uint32_t slot, zend_string *method)
	{
		zend_object *obj = Z_OBJ_P(self);
		zval *cached = OBJ_PROP_NUM(obj, slot);
		if (EXPECTED(Z_TYPE_P(cached) == IS_ARRAY)) {
			return cached;
		}

		zval pair, entry;
		array_init_size(&pair, 2);
		ZVAL_OBJ_COPY(&entry, obj);
		zend_hash_next_index_insert_new(Z_ARRVAL(pair), &entry);
		ZVAL_STR_COPY(&entry, method);
		zend_hash_next_index_insert_new(Z_ARRVAL(pair), &entry);
		zv::ObjRef(obj).propAtWrite(slot, zv::Val::adopt(pair));

		return OBJ_PROP_NUM(obj, slot);
	}

	void releaseBoundCallables()
	{
		zv::ObjRef obj(self);
		obj.propAtWrite(PT_TT_PROP_MAP_CALLABLE, zv::Val::null());
		obj.propAtWrite(PT_TT_PROP_TRAVERSE_CALLABLE, zv::Val::null());
	}

	/*
	 * Keeps the traverser for the next traversal instead of letting map()
	 * free it: an analysis runs millions of traversals, each of which would
	 * otherwise allocate and free one object.
	 *
	 * Only when nothing outlives the traversal. Refcount 1 is map()'s own
	 * reference — with the bound callables already dropped, anything else
	 * means the callback kept one of them (a $traverse it can still call,
	 * which needs its cb, so that one is left to die with the traverser).
	 * A WeakReference holds no reference but would observe the reuse.
	 */
	void parkIfUnescaped()
	{
		zend_object *obj = Z_OBJ_P(self);
		if (pt_tt_parked != NULL || GC_REFCOUNT(obj) != 1 || (GC_FLAGS(obj) & IS_OBJ_WEAKLY_REFERENCED)) {
			return;
		}
		zv::ObjRef(obj).propAtWrite(PT_TT_PROP_CB, zv::Val::null());
		pt_tt_parked = obj;
		GC_ADDREF(obj);
	}

	/* the parked traverser, if it fits the requested class; its slot stays
	 * empty while it is in use, so nested traversals allocate their own */
	static zend_object *takeParked(zend_class_entry *scope)
	{
		zend_object *parked = pt_tt_parked;
		if (parked == NULL || parked->ce != scope) {
			return NULL;
		}
		pt_tt_parked = NULL;

		return parked;
	}
};

} // namespace phpstanturbo

using phpstanturbo::TypeTraverser;

/* {{{ engine ABI glue: parameter parsing + registration */

#include "reg.h"

void pt_type_traverser_rshutdown()
{
	if (pt_tt_parked != NULL) {
		zend_object_release(pt_tt_parked);
		pt_tt_parked = NULL;
	}
}

void pt_register_type_traverser()
{
	pt_str_map_internal = zend_string_init_interned("mapInternal", sizeof("mapInternal") - 1, 1);
	pt_str_traverse_internal = zend_string_init_interned("traverseInternal", sizeof("traverseInternal") - 1, 1);

	reg::Class cls("PHPStanTurbo\\TypeTraverser");
	/* not final: the stub subclass PHPStan\Type\TypeTraverser extends this
	 * class; "cb" must stay the first declared property (OBJ_PROP_NUM slot 0).
	 * The two callable slots have no counterpart in the twin — they are the
	 * traversal's memo of the bound callables it allocates per node. */
	cls.privateNullProperty("cb");
	cls.privateNullProperty("mapCallable");
	cls.privateNullProperty("traverseCallable");

	cls.method("map", reg::PublicStatic, 2, { reg::any("type"), reg::any("cb") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type, *cb;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(type)
			Z_PARAM_ZVAL(cb)
		ZEND_PARSE_PARAMETERS_END();

		ZVAL_DEREF(cb);
		/* the called scope is the stub subclass, so the traverser is the class
		 * PHPStan's own code knows; the declaring class covers the callable
		 * forms the engine invokes without one */
		zend_class_entry *scope = zend_get_called_scope(execute_data);
		zv::Val result = TypeTraverser::map(scope != NULL ? scope : execute_data->func->common.scope, type, cb);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("__construct", reg::Private, 1, { reg::any("cb") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *cb;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_ZVAL(cb)
		ZEND_PARSE_PARAMETERS_END();

		ZVAL_DEREF(cb);
		TypeTraverser(ZEND_THIS).construct(zv::Ref(cb));
	});

	cls.method("mapInternal", reg::Public, 1, { reg::any("type") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();

		zv::Val result = TypeTraverser(ZEND_THIS).mapInternal(type);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.method("traverseInternal", reg::Public, 1, { reg::any("type") }, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *type;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_OBJECT(type)
		ZEND_PARSE_PARAMETERS_END();

		zv::Val result = TypeTraverser(ZEND_THIS).traverseInternal(type);
		if (UNEXPECTED(result.isUndef())) {
			RETURN_THROWS();
		}
		result.intoReturnValue(return_value);
	});

	cls.register_();
}

/* }}} */
