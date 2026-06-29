<?php

namespace App\Services\Claude;

use RuntimeException;

/** Thrown when a Claude call refuses, errors, or returns unparseable JSON. */
class ClaudeException extends RuntimeException
{
}
