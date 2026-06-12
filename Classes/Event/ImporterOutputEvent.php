<?php

declare(strict_types=1);

namespace DirectMailTeam\DirectMail\Event;

final class ImporterOutputEvent
{
    public function __construct(private array $output) {}

    public function getOutput(): array
    {
        return $this->output;
    }

    public function setOutput(array $output): void
    {
        $this->output = $output;
    }
}
