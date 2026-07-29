<?php

namespace App\Mail;

use App\Models\User;
use App\Support\GrantablePermissions;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DroitsAccesModifiesMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user)
    {
    }

    public function build(): self
    {
        $labels = $this->user->getDirectPermissions()
            ->pluck('name')
            ->map(fn ($cle) => GrantablePermissions::CATALOGUE[$cle] ?? $cle)
            ->values()
            ->all();

        return $this->subject("Vos droits d'accès ont été mis à jour")
            ->view('emails.droits-modifies', [
                'user' => $this->user,
                'labels' => $labels,
                'loginUrl' => rtrim(config('app.frontend_url'), '/').'/login',
            ]);
    }
}
