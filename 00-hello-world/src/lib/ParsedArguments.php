<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * The result of a syntactically valid argument scan. Values are recorded, not validated:
 * the last --name and --count win (CONV-11), and only the first operand is kept (FR-09).
 */
final readonly class ParsedArguments
{
    public function __construct(
        public Action $action = Action::Greet,
        public ?OptionValue $name = null,
        public ?OptionValue $count = null,
        public ?string $firstOperand = null,
    ) {}
}
