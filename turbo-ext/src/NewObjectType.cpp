/*
 * PHPStanTurbo\NewObjectType — native implementation of PHPStan\Type\NewObjectType.
 *
 * Declared as PHPStan\Type\NewObjectType itself at activation: not final (a PHP
 * subclass may extend it), implementing PHPStan\Type\CompoundType and
 * PHPStan\Type\LateResolvableType. State is the twin's promoted
 * `private Type $type` in slot 0; LateResolvableTypeTrait's `private ?Type
 * $result` follows it, declared by the shared registrar in TypeTraits.cpp
 * that also supplies the trait's forwards (their $this-calls — resolve(),
 * isResolvable(), getResult() — go through the object's class entry there,
 * so a subclass overriding them is honoured); NonGeneralizableTypeTrait's
 * generalize() comes from its registrar.
 *
 * The twin's own bodies make no $this-calls. `self` (equals(), the `new
 * self` of traverse()) is exactly NewObjectType, as the twin spells it;
 * another instance's private slot (`$type->type`) is read directly, as the
 * twin does from inside the class.
 */

#include "TypeTraits.h"
#include "generated/NewObjectType.h"

namespace slots = ptdecl::NewObjectType::slot;
namespace sigs = ptdecl::NewObjectType::sig;

zend_class_entry *pt_ce_new_object_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\NewObjectType. State lives in the PHP object's slot. */
class NewObjectType
{
public:
	explicit NewObjectType(zend_object *self) : self(self) {}

	/* __construct(private Type $type); borrowed */
	void construct(zval *type)
	{
		zval *p = OBJ_PROP_NUM(self, slots::type);
		/* the slot is overwritten in place: a repeated __construct() call
		 * would otherwise leak the first value */
		zval previous;
		ZVAL_COPY_VALUE(&previous, p);
		ZVAL_COPY(p, type);
		Z_PROP_FLAG_P(p) = 0; /* no longer IS_PROP_UNINIT */
		if (Z_TYPE(previous) != IS_UNDEF) {
			zval_ptr_dtor(&previous);
		}
	}

	/* new self($type) — exactly the class, as the twin's site spells it;
	 * UNDEF = pending exception */
	static zv::Val create(zval *type)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_new_object_type) != SUCCESS)) return zv::Val();
		NewObjectType(Z_OBJ(object)).construct(type);
		return zv::Val::adopt(object);
	}

	/* the slot (borrowed); NULL with an Error pending when the constructor
	 * never ran — the twin's typed-property read raises the same */
	[[nodiscard]] zval *type() const { return slot(self); }

	static zval *slot(zend_object *object)
	{
		zval *p = OBJ_PROP_NUM(object, slots::type);
		if (UNEXPECTED(Z_TYPE_P(p) == IS_UNDEF)) {
			zend_throw_error(NULL, "Typed property %s::$type must not be accessed before initialization", ZSTR_VAL(pt_ce_new_object_type->name));
			return NULL;
		}
		return p;
	}

	zv::Val getType() const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return zv::Val::copyOf(zv::Ref(t));
	}

	/* $this->type->getReferencedClasses() */
	zv::Val getReferencedClasses() const { return callType(PT_LC("getreferencedclasses"), 0, NULL); }

	/* $this->type->getReferencedTemplateTypes($positionVariance) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const { return callType(PT_LC("getreferencedtemplatetypes"), 1, positionVariance); }

	/* $type instanceof self && $this->type->equals($type->type); false with
	 * an exception pending */
	bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_new_object_type)) {
			out = false;
			return true;
		}
		zval *theirs = slot(Z_OBJ_P(type));
		if (UNEXPECTED(theirs == NULL)) return false;
		zv::Val equal = callType(PT_LC("equals"), 1, theirs);
		if (UNEXPECTED(equal.isUndef())) return false;
		out = zend_is_true(equal.raw());
		return true;
	}

	/* sprintf('new<%s>', $this->type->describe($level)) */
	zv::Val describe(zval *level) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_describe_generic_of(PT_LC("new"), t, level);
	}

	/* !TypeUtils::containsTemplateType($this->type); false = pending exception */
	[[nodiscard]] bool isResolvable(bool &out) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return false;
		bool contains;
		if (UNEXPECTED(!pt_type_utils_contains_template_type(t, contains))) return false;
		out = !contains;
		return true;
	}

	/* $this->type->getObjectTypeOrClassStringObjectType() */
	zv::Val getResult() const { return callType(PT_LC("getobjecttypeorclassstringobjecttype"), 0, NULL); }

	/* new self($cb($this->type)) when the callback changed it, $this
	 * otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return traversed(pt_type_traverse_call(fci, fcc, t));
	}

	/* $this for a $right of another class, else traverse() with $right's
	 * type as the callback's second argument */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_new_object_type)) return thisValue();
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval *theirs = slot(Z_OBJ_P(right));
		if (UNEXPECTED(theirs == NULL)) return zv::Val();
		return traversed(pt_type_traverse_call(fci, fcc, t, theirs));
	}

	/* new GenericTypeNode(new IdentifierTypeNode('new'), [$this->type->toPhpDocNode()]) */
	zv::Val toPhpDocNode() const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_generic_node_of(PT_LC("new"), t);
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	/* $this->type->method(...$args); UNDEF = pending exception */
	zv::Val callType(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		return pt_type_call(Z_OBJ_P(t), lcname, len, argc, argv);
	}

	/* the tail of traverse()/traverseSimultaneously(): `$this->type === $type
	 * ? $this : new self($type)` (UNDEF in = UNDEF out) */
	zv::Val traversed(zv::Val type) const
	{
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zval *t = this->type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		if (pt_type_same_object(t, type.raw())) return thisValue();
		return create(type.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::NewObjectType;

bool pt_new_object_type_new(zval *out, zval *type)
{
	return pt_val_into(NewObjectType::create(type), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS NewObjectType(Z_OBJ_P(ZEND_THIS))

void pt_register_new_object_type()
{
	reg::Class cls("PHPStan\\Type\\NewObjectType");
	ptdecl::NewObjectType::declareClass(cls);
	/* the slot must stay first (slots::type); the trait registrar
	 * declares $result after it */
	ptdecl::NewObjectType::declareProperties(cls);

	cls.method<&NewObjectType::construct, zp::Obj>(sigs::__construct);

	cls.method<&NewObjectType::getType>(sigs::getType);

	cls.method<&NewObjectType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &NewObjectType::getReferencedClasses>();

	cls.method<&NewObjectType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&NewObjectType::equals, zp::Obj>(sigs::equals);

	cls.method<&NewObjectType::describe, zp::Obj>(sigs::describe);

	cls.method<&NewObjectType::isResolvable>(sigs::isResolvable);

	cls.method<&NewObjectType::getResult>(sigs::getResult);

	cls.method(sigs::traverse, [](INTERNAL_FUNCTION_PARAMETERS) {
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(1, 1)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverse(&fci, &fcc));
	});

	cls.method(sigs::traverseSimultaneously, [](INTERNAL_FUNCTION_PARAMETERS) {
		zval *right;
		zend_fcall_info fci;
		zend_fcall_info_cache fcc;
		ZEND_PARSE_PARAMETERS_START(2, 2)
			Z_PARAM_OBJECT(right)
			Z_PARAM_FUNC(fci, fcc)
		ZEND_PARSE_PARAMETERS_END();
		PT_RETURN_VAL(PT_THIS.traverseSimultaneously(right, &fci, &fcc));
	});

	cls.method<&NewObjectType::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::NewObjectType::registerTraits(cls);

	cls.shadow(&pt_ce_new_object_type);
}

/* }}} */
