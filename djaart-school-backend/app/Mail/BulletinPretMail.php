<?php

namespace App\Mail;

use App\Models\Bulletin;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BulletinPretMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Bulletin $bulletin, public readonly string $sequenceLibelle)
    {
    }

    public function build(): self
    {
        return $this->subject('Bulletin disponible')
            ->view('emails.bulletin-pret', [
                'apprenant' => $this->bulletin->inscription->apprenant,
                'bulletin' => $this->bulletin,
                'sequenceLibelle' => $this->sequenceLibelle,
            ]);
    }
}
