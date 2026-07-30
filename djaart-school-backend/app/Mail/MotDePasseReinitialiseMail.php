<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MotDePasseReinitialiseMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly User $user, public readonly string $motDePasse)
    {
    }

    public function build(): self
    {
        return $this->subject('Votre mot de passe DJAART SCHOOL a été réinitialisé')
            ->view('emails.mot-de-passe-reinitialise', [
                'user' => $this->user,
                'motDePasse' => $this->motDePasse,
                'loginUrl' => rtrim(config('app.frontend_url'), '/').'/login',
            ]);
    }
}
