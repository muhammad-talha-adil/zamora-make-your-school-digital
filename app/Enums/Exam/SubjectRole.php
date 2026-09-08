<?php

namespace App\Enums\Exam;

/**
 * What a subject is to a particular child.
 *
 * The module had one column for this and it was `is_exempt`, which says
 * something else: exempt means the child was not required to sit the paper, not
 * that the paper does not count.
 *
 * A fixed set the application reasons about, so it is an enum and the column is
 * cast — the rule in [docs/WORKING-RULES.md](docs/WORKING-RULES.md). A school
 * does not get to invent a fourth kind, because the arithmetic would not know
 * what to do with it.
 */
enum SubjectRole: string
{
    /** Everybody sits it, and it counts. */
    case Core = 'core';

    /**
     * Only the children who chose it sit it, and for them it counts.
     *
     * Computer instead of Biology — normal from Class 9 onwards. A child who
     * did not choose it simply has no line for that paper.
     */
    case Elective = 'elective';

    /**
     * Marked and graded, and deliberately not counted.
     *
     * An extra subject taken beyond the required ones. It appears on the result
     * card with its own grade, below the total, and changes neither the
     * percentage nor the position.
     */
    case Additional = 'additional';

    /**
     * Whether marks in this role are part of the total and the position.
     */
    public function counts(): bool
    {
        return $this !== self::Additional;
    }

    /**
     * What a school calls it on the result card.
     */
    public function label(): string
    {
        return match ($this) {
            self::Core => 'Compulsory',
            self::Elective => 'Elective',
            self::Additional => 'Additional',
        };
    }

    /**
     * The roles that count towards a total, for a `whereIn`.
     *
     * @return array<int, string>
     */
    public static function countedValues(): array
    {
        return [self::Core->value, self::Elective->value];
    }
}
