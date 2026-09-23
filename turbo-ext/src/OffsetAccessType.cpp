/*
 * PHPStanTurbo\OffsetAccessType — native implementation of
 * PHPStan\Type\OffsetAccessType.
 *
 * State is the twin's two promoted `private Type $type` / `private Type
 * $offset` in slots 0 and 1; LateResolvableTypeTrait's `private ?Type
 * $result` follows them, declared by the shared registrar in TypeTraits.cpp
 * that also supplies the trait's forwards (the class body's own
 * getObjectClassNames()/getObjectClassReflections() win over the trait's,
 * as in PHP); NonGeneralizableTypeTrait's generalize() comes from its
 * registrar.
 *
 * The one $this-call the twin makes (describe() printing
 * $this->toPhpDocNode()) goes through the object's class entry; another
 * instance's private slots are read directly, as the twin does from inside
 * the class.
 */

#include "TypeTraits.h"
#include "generated/OffsetAccessType.h"

namespace slots = ptdecl::OffsetAccessType::slot;
namespace sigs = ptdecl::OffsetAccessType::sig;

zend_class_entry *pt_ce_offset_access_type = nullptr;

namespace phpstanturbo {

/* Mirrors PHPStan\Type\OffsetAccessType. State lives in the PHP object's slots. */
class OffsetAccessType
{
public:
	explicit OffsetAccessType(zend_object *self) : self(self) {}

	/* __construct(private Type $type, private Type $offset); both borrowed */
	void construct(zval *type, zval *offset)
	{
		writeSlot(slots::type, type);
		writeSlot(slots::offset, offset);
	}

	/* new self($type, $offset); UNDEF = pending exception */
	static zv::Val create(zval *type, zval *offset)
	{
		zval object;
		if (UNEXPECTED(object_init_ex(&object, pt_ce_offset_access_type) != SUCCESS)) return zv::Val();
		OffsetAccessType(Z_OBJ(object)).construct(type, offset);
		return zv::Val::adopt(object);
	}

	/* the slots (borrowed); NULL with an Error pending when the constructor
	 * never ran */
	[[nodiscard]] zval *type() const { return slot(self, slots::type, "type"); }
	zval *offset() const { return slot(self, slots::offset, "offset"); }

	static zval *slot(zend_object *object, uint32_t index, const char *name) { return pt_typed_slot(object, index, pt_ce_offset_access_type, name); }

	/* array_merge($this->type->getReferencedClasses(), $this->offset->getReferencedClasses()) */
	zv::Val getReferencedClasses() const { return mergedOfBoth(PT_LC("getreferencedclasses"), 0, NULL); }

	/* array_merge($this->type->getReferencedTemplateTypes($positionVariance),
	 * $this->offset->getReferencedTemplateTypes($positionVariance)) */
	zv::Val getReferencedTemplateTypes(zval *positionVariance) const { return mergedOfBoth(PT_LC("getreferencedtemplatetypes"), 1, positionVariance); }

	/* $type instanceof self && $this->type->equals($type->type) &&
	 * $this->offset->equals($type->offset); false with an exception pending */
	[[nodiscard]] bool equals(zval *type, bool &out) const
	{
		if (!instanceof_function(Z_OBJCE_P(type), pt_ce_offset_access_type)) {
			out = false;
			return true;
		}
		zval *t = this->type();
		if (UNEXPECTED(t == NULL)) return false;
		zval *theirType = slot(Z_OBJ_P(type), slots::type, "type");
		if (UNEXPECTED(theirType == NULL)) return false;
		zv::Val typesEqual = pt_type_op(Z_OBJ_P(t), PT_OP_EQUALS, 1, theirType);
		if (UNEXPECTED(typesEqual.isUndef())) return false;
		if (!zend_is_true(typesEqual.raw())) {
			out = false;
			return true;
		}
		zval *o = offset();
		if (UNEXPECTED(o == NULL)) return false;
		zval *theirOffset = slot(Z_OBJ_P(type), slots::offset, "offset");
		if (UNEXPECTED(theirOffset == NULL)) return false;
		return pt_type_op_bool(Z_OBJ_P(o), PT_OP_EQUALS, 1, theirOffset, out);
	}

	/* (new Printer())->print($this->toPhpDocNode()) */
	zv::Val describe() const
	{
		zval selfZv;
		ZVAL_OBJ(&selfZv, self);
		return pt_callable_print_php_doc_node(&selfZv);
	}

	/* !TypeUtils::containsTemplateType($this->type) &&
	 * !TypeUtils::containsTemplateType($this->offset); false = pending
	 * exception */
	[[nodiscard]] bool isResolvable(bool &out) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return false;
		bool contains;
		if (UNEXPECTED(!pt_type_utils_contains_template_type(t, contains))) return false;
		if (contains) {
			out = false;
			return true;
		}
		zval *o = offset();
		if (UNEXPECTED(o == NULL)) return false;
		if (UNEXPECTED(!pt_type_utils_contains_template_type(o, contains))) return false;
		out = !contains;
		return true;
	}

	/* $this->type->getOffsetValueType($this->offset) */
	zv::Val getResult() const
	{
		zval *t = type();
		zval *o = t != NULL ? offset() : NULL; /* one Error at a time, as the twin's first read raises */
		if (UNEXPECTED(o == NULL)) return zv::Val();
		return pt_type_call(Z_OBJ_P(t), PT_LC("getoffsetvaluetype"), 1, o);
	}

	/* new self($cb($this->type), $cb($this->offset)) when the callback
	 * changed either, $this otherwise; UNDEF = pending exception */
	zv::Val traverse(zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zv::Val type = pt_type_traverse_call(fci, fcc, t);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zval *o = offset();
		if (UNEXPECTED(o == NULL)) return zv::Val();
		return traversed(std::move(type), pt_type_traverse_call(fci, fcc, o));
	}

	/* $this for a $right of another class, else traverse() with $right's
	 * slots as the callback's second arguments */
	zv::Val traverseSimultaneously(zval *right, zend_fcall_info *fci, zend_fcall_info_cache *fcc) const
	{
		if (!instanceof_function(Z_OBJCE_P(right), pt_ce_offset_access_type)) return thisValue();
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zval *theirType = slot(Z_OBJ_P(right), slots::type, "type");
		if (UNEXPECTED(theirType == NULL)) return zv::Val();
		zv::Val type = pt_type_traverse_call(fci, fcc, t, theirType);
		if (UNEXPECTED(type.isUndef())) return zv::Val();
		zval *o = offset();
		if (UNEXPECTED(o == NULL)) return zv::Val();
		zval *theirOffset = slot(Z_OBJ_P(right), slots::offset, "offset");
		if (UNEXPECTED(theirOffset == NULL)) return zv::Val();
		return traversed(std::move(type), pt_type_traverse_call(fci, fcc, o, theirOffset));
	}

	/* new OffsetAccessTypeNode($this->type->toPhpDocNode(), $this->offset->toPhpDocNode()) */
	zv::Val toPhpDocNode() const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zv::Val typeNode = pt_type_call(Z_OBJ_P(t), PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(typeNode.isUndef())) return zv::Val();
		zval *o = offset();
		if (UNEXPECTED(o == NULL)) return zv::Val();
		zv::Val offsetNode = pt_type_call(Z_OBJ_P(o), PT_LC("tophpdocnode"), 0, NULL);
		if (UNEXPECTED(offsetNode.isUndef())) return zv::Val();
		zv::Args args{typeNode.raw(), offsetNode.raw()};
		return pt_type_new(PT_CLASS_OFFSET_ACCESS_TYPE_NODE, 2, args);
	}

private:
	zend_object *self;

	zv::Val thisValue() const { return pt_this_value(self); }

	void writeSlot(uint32_t index, zval *value) { pt_write_slot(self, index, value); }

	/* array_merge($this->type->method(...$args), $this->offset->method(...$args));
	 * UNDEF = pending exception */
	zv::Val mergedOfBoth(const char *lcname, size_t len, uint32_t argc, zval *argv) const
	{
		zval *t = type();
		if (UNEXPECTED(t == NULL)) return zv::Val();
		zv::Val ofType = pt_type_call(Z_OBJ_P(t), lcname, len, argc, argv);
		if (UNEXPECTED(ofType.isUndef())) return zv::Val();
		zval *o = offset();
		if (UNEXPECTED(o == NULL)) return zv::Val();
		zv::Val ofOffset = pt_type_call(Z_OBJ_P(o), lcname, len, argc, argv);
		if (UNEXPECTED(ofOffset.isUndef())) return zv::Val();
		zv::Arr merged = zv::Arr::create(0);
		if (UNEXPECTED(!pt_callable_array_merge_into(merged, ofType.raw()) || !pt_callable_array_merge_into(merged, ofOffset.raw()))) return zv::Val();
		return zv::Val(std::move(merged));
	}

	/* the tail of traverse()/traverseSimultaneously(): `$this->type === $type
	 * && $this->offset === $offset ? $this : new self($type, $offset)`
	 * (an UNDEF $offset = pending exception) */
	zv::Val traversed(zv::Val type, zv::Val offset) const
	{
		if (UNEXPECTED(offset.isUndef())) return zv::Val();
		zval *t = this->type();
		zval *o = t != NULL ? this->offset() : NULL;
		if (UNEXPECTED(o == NULL)) return zv::Val();
		if (pt_type_same_object(t, type.raw()) && pt_type_same_object(o, offset.raw())) return thisValue();
		return create(type.raw(), offset.raw());
	}
};

} // namespace phpstanturbo

using phpstanturbo::OffsetAccessType;

bool pt_offset_access_type_new(zval *out, zval *type, zval *offset)
{
	return pt_val_into(OffsetAccessType::create(type, offset), out);
}

/* {{{ engine ABI glue: parameter parsing + registration */

#define PT_THIS OffsetAccessType(Z_OBJ_P(ZEND_THIS))

static void ZEND_FASTCALL oaEmptyArray0(INTERNAL_FUNCTION_PARAMETERS)
{
	ZEND_PARSE_PARAMETERS_NONE();
	RETURN_EMPTY_ARRAY();
}

void pt_register_offset_access_type()
{
	reg::Class cls("PHPStan\\Type\\OffsetAccessType");
	ptdecl::OffsetAccessType::declareClass(cls);
	/* the slots must stay in this order (PT_OA_PROP_*); the trait registrar
	 * declares $result after them */
	ptdecl::OffsetAccessType::declareProperties(cls);

	cls.method<&OffsetAccessType::construct, zp::TypeObj, zp::TypeObj>(sigs::__construct);

	cls.method<&OffsetAccessType::getReferencedClasses>(sigs::getReferencedClasses);
	cls.op<PT_OP_GET_REFERENCED_CLASSES, &OffsetAccessType::getReferencedClasses>();
	cls.method(sigs::getObjectClassNames, oaEmptyArray0);
	cls.method(sigs::getObjectClassReflections, oaEmptyArray0);
	cls.op(PT_OP_GET_OBJECT_CLASS_REFLECTIONS, PT_OP_LAMBDA { return pt_op_empty_array(); });

	cls.method<&OffsetAccessType::getReferencedTemplateTypes, zp::Obj>(sigs::getReferencedTemplateTypes);

	cls.method<&OffsetAccessType::equals, zp::TypeObj>(sigs::equals);

	cls.method(sigs::describe, [](INTERNAL_FUNCTION_PARAMETERS) {
		PT_ARGS(1, 1);
		PT_RETURN_VAL(PT_THIS.describe());
	});

	cls.method<&OffsetAccessType::isResolvable>(sigs::isResolvable);

	cls.method<&OffsetAccessType::getResult>(sigs::getResult);

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

	cls.method<&OffsetAccessType::toPhpDocNode>(sigs::toPhpDocNode);

	/* the traits, in the twin's `use` order; the class body above wins over
	 * every name it declares */
	ptdecl::OffsetAccessType::registerTraits(cls);

	cls.shadow(&pt_ce_offset_access_type);
}

/* }}} */
