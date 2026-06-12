<?php

declare(strict_types=1);

namespace DirectMailTeam\DirectMail\Event;

final class RecipientListCompileMailGroupEvent
{
    public function __construct(private array $idLists, private array $mailGroup) {}

    public function getIdLists(): array
    {
        return $this->idLists;
    }

    public function setIdLists(array $idLists): void
    {
        $this->idLists = $idLists;
    }

    public function getMailGroup(): array
    {
        return $this->mailGroup;
    }

    public function setMailGroup(array $mailGroup): void
    {
        $this->mailGroup = $mailGroup;
    }
}
