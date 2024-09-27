//
// Hoa
//
//
// @license
//
// New BSD License
//
// Copyright © 2007-2017, Hoa community. All rights reserved.
//
// Redistribution and use in source and binary forms, with or without
// modification, are permitted provided that the following conditions are met:
//     * Redistributions of source code must retain the above copyright
//       notice, this list of conditions and the following disclaimer.
//     * Redistributions in binary form must reproduce the above copyright
//       notice, this list of conditions and the following disclaimer in the
//       documentation and/or other materials provided with the distribution.
//     * Neither the name of the Hoa nor the names of its contributors may be
//       used to endorse or promote products derived from this software without
//       specific prior written permission.
//
// THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
// AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
// IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
// ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
// LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
// CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
// SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
// INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
// CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
// ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
// POSSIBILITY OF SUCH DAMAGE.
//
// Grammar \Hoa\Regex\Grammar.
//
// Provide grammar of PCRE (Perl Compatible Regular Expression)for the LL(k)
// parser. More informations at http://pcre.org/pcre.txt, sections pcrepattern &
// pcresyntax.
//
// @copyright  Copyright © 2007-2017 Hoa community.
// @license    New BSD License
//

// Character classes.
// tokens suffixed with "fc_" are the same as without such suffix but followed by "class:_class"
%token  negative_class_fc_       \[\^(?=\])         -> class_fc
%token  class_fc_                \[(?=\])           -> class_fc
%token class_fc:_class           \]                 -> class
%token  negative_class_          \[\^               -> class
%token  class_                   \[                 -> class
%token class:posix_class         \[:\^?[a-z]+:\]
%token class:class_              \[
%token class:_class              \]                 -> default
%token class:range               \-
// taken over from literals but class:character has \b support on top (backspace in character classes)
%token class:character           \\([aefnrtb]|c[\x00-\x7f])
%token class:dynamic_character   \\([0-7]{3}|x[0-9a-zA-Z]{2}|x{[0-9a-zA-Z]+})
%token class:character_type      \\([CdDhHNRsSvVwWX]|[pP]{[^}]+})
%token class:literal             \\.|.|\n

// Internal options.
// See https://www.regular-expressions.info/refmodifiers.html
// and https://www.php.net/manual/en/regexp.reference.internal-options.php
%token  internal_option          \(\?[imsxnJUX^]*-?[imsxnJUX^]+\)

// Lookahead and lookbehind assertions.
%token  lookahead_               \(\?=
%token  negative_lookahead_      \(\?!
%token  lookbehind_              \(\?<=
%token  negative_lookbehind_     \(\?<!

// Conditions.
%token  named_reference_         \(\?\(<            -> nc
%token  absolute_reference_      \(\?\((?=\d)       -> c
%token  relative_reference_      \(\?\((?=[\+\-])   -> c
%token  c:index                  [\+\-]?\d+         -> default
%token  assertion_reference_     \(\?\(

// Comments.
%token  comment_                 \(\?#              -> co
%token  co:_comment              \)                 -> default
%token  co:comment               .*?(?=(?<!\\)\))

// Marker verbs
%token  marker_                  \(\*:              -> mark
%token  mark:name                [^)]+
%token  mark:_marker             \)                 -> default

// Capturing group.
%token  named_capturing_         \(\?P?<            -> nc
%token  nc:_named_capturing      >                  -> default
%token  nc:capturing_name        .+?(?=(?<!\\)>)
%token  non_capturing_           \(\?:
%token  non_capturing_internal_option \(\?[imsxnJUX^]*-?[imsxnJUX^]+:
%token  non_capturing_reset_     \(\?\|
%token  atomic_group_            \(\?>
%token  capturing_               \(
%token _capturing                \)

// Quantifiers (by default, greedy).
%token  zero_or_one_possessive   \?\+
%token  zero_or_one_lazy         \?\?
%token  zero_or_one              \?
%token  zero_or_more_possessive  \*\+
%token  zero_or_more_lazy        \*\?
%token  zero_or_more             \*
%token  one_or_more_possessive   \+\+
%token  one_or_more_lazy         \+\?
%token  one_or_more              \+
%token  exactly_n                \{[0-9]+\}
%token  n_to_m_possessive        \{[0-9]+,[0-9]+\}\+
%token  n_to_m_lazy              \{[0-9]+,[0-9]+\}\?
%token  n_to_m                   \{[0-9]+,[0-9]+\}
%token  n_or_more_possessive     \{[0-9]+,\}\+
%token  n_or_more_lazy           \{[0-9]+,\}\?
%token  n_or_more                \{[0-9]+,\}

// Alternation.
%token alternation               \|

// Literal.
%token character                 \\([aefnrt]|c[\x00-\x7f])
%token dynamic_character         \\([0-7]{3}|x[0-9a-zA-Z]{2}|x{[0-9a-zA-Z]+})
// Please, see PCRESYNTAX(3), General Category properties, PCRE special category
// properties and script names for \p{} and \P{}.
%token character_type            \\([CdDhHNRsSvVwWX]|[pP]{[^}]+})
%token anchor                    \\([bBAZzG])|\^|\$
%token match_point_reset         \\K
%token literal                   \\.|.|\n


// Rules.

#expression:
    alternation()

alternation:
    concatenation()? ( <alternation> concatenation()? #alternation )*

concatenation:
    (   internal_options() | assertion() | quantification() | condition() )
    ( ( internal_options() | assertion() | quantification() | condition() ) #concatenation )*

#internal_options:
    <internal_option>

#condition:
    (
        ::named_reference_:: <capturing_name> ::_named_capturing:: #namedcondition
      | (
            ::relative_reference_:: #relativecondition
          | ::absolute_reference_:: #absolutecondition
        )
        <index>
      | ::assertion_reference_:: alternation() #assertioncondition
    )
    ::_capturing::
    alternation()
    ::_capturing::

assertion:
    (
        ::lookahead_::           #lookahead
      | ::negative_lookahead_::  #negativelookahead
      | ::lookbehind_::          #lookbehind
      | ::negative_lookbehind_:: #negativelookbehind
    )
    alternation()
    ::_capturing::

quantification:
    ( class() | simple() ) ( quantifier() #quantification )?

quantifier:
    <zero_or_one_possessive>  | <zero_or_one_lazy>  | <zero_or_one>
  | <zero_or_more_possessive> | <zero_or_more_lazy> | <zero_or_more>
  | <one_or_more_possessive>  | <one_or_more_lazy>  | <one_or_more>
  | <exactly_n>
  | <n_to_m_possessive>       | <n_to_m_lazy>       | <n_to_m>
  | <n_or_more_possessive>    | <n_or_more_lazy>    | <n_or_more>

#class:
    (
        ::negative_class_fc_:: #negativeclass
        <_class>
      | ::class_fc_::
        <_class>
      | ::negative_class_:: #negativeclass
      | ::class_::
    )
    <range>? ( <posix_class> | <class_> | range() <range>? | literal() )* <range>?
    ::_class::

#range:
    literal() ::range:: literal()

simple:
    capturing()
  | literal()

#capturing:
    ::marker_:: <name> ::_marker:: #mark
  | ::comment_:: <comment>? ::_comment:: #comment
  | (
        ::named_capturing_:: <capturing_name> ::_named_capturing:: #namedcapturing
      | ::non_capturing_:: #noncapturing
      | non_capturing_internal_options() #noncapturing
      | ::non_capturing_reset_:: #noncapturingreset
      | ::atomic_group_:: #atomicgroup
      | ::capturing_::
    )
    alternation()
    ::_capturing::

non_capturing_internal_options:
    <non_capturing_internal_option>

literal:
    <character>
  | <dynamic_character>
  | <character_type>
  | <anchor>
  | <match_point_reset>
  | <literal>
