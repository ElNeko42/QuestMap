<?php

declare(strict_types=1);

namespace App\Exceptions;

class QuestClosedException extends ApiException
{
    public static function make(): self
    {
        return new self(
            'Esta misión no está disponible en este momento.',
            'quest_closed',
            422,
        );
    }

    public static function wrongValidationType(string $expected): self
    {
        $msg = $expected === 'checkin'
            ? 'Esta misión requiere subir una foto, no un check-in.'
            : 'Esta misión se completa con check-in, no con foto.';

        return new self($msg, 'wrong_validation_type', 422);
    }

    public static function alreadyCompleted(): self
    {
        return new self(
            'Ya has completado esta misión.',
            'already_completed',
            409,
        );
    }
}
