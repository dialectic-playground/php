<?php

declare(strict_types=1);

namespace DialecticPlayground\Hello;

/**
 * What the invocation asks for once the argument scan has finished (CONV-12).
 */
enum Action
{
    case Greet;
    case Help;
    case Version;
}
