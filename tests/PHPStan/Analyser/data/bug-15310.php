<?php // lint >= 8.1

namespace Bug15310;

enum ExampleIdentifier { case A; case B; case C; case D; case E; case F; case G; case H; case I; case J; case K; case L; case M; case N; case O; }

/** @template-covariant TValue */
abstract class ExampleType
{
}

/**
 * @template T of ExampleIdentifier
 * @extends ExampleType<(
 *   T is ExampleIdentifier::A ? array :
 *   T is ExampleIdentifier::B ? bool :
 *   T is ExampleIdentifier::C ? callable :
 *   T is ExampleIdentifier::D ? false :
 *   T is ExampleIdentifier::E ? float :
 *   T is ExampleIdentifier::F ? int :
 *   T is ExampleIdentifier::G ? iterable :
 *   T is ExampleIdentifier::H ? mixed :
 *   T is ExampleIdentifier::I ? null :
 *   T is ExampleIdentifier::J ? object :
 *   T is ExampleIdentifier::K ? resource :
 *   T is ExampleIdentifier::L ? string :
 *   T is ExampleIdentifier::M ? true :
 *   T is ExampleIdentifier::N ? never :
 *   T is ExampleIdentifier::O ? void :
 *   mixed
 * )>
 */
final class ExampleBuiltinType extends ExampleType
{
    /** @param T $identifier */
    public function __construct(public ExampleIdentifier $identifier) {}
}

function reproduce(): ExampleType
{
    return new ExampleBuiltinType(ExampleIdentifier::A);
}
