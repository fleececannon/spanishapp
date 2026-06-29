<?php

namespace App\Services\Claude;

use App\Enums\ReviewResult;

readonly class GradeResult
{
    public function __construct(
        public ReviewResult $verdict,
        public string $acceptedEnglish,
        public ?string $nudge = null,
    ) {}

    public function passed(): bool
    {
        return $this->verdict === ReviewResult::Pass;
    }
}
