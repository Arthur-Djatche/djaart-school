<?php

namespace Tests\Unit;

use App\Support\Mailer;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MailerTest extends TestCase
{
    public function test_envoyer_retourne_vrai_et_execute_le_callback_quand_tout_va_bien(): void
    {
        $execute = false;

        $resultat = Mailer::envoyer(function () use (&$execute) {
            $execute = true;
        });

        $this->assertTrue($resultat);
        $this->assertTrue($execute);
    }

    public function test_envoyer_rattrape_toute_exception_et_retourne_faux_sans_la_relancer(): void
    {
        Log::shouldReceive('error')->once();

        $resultat = Mailer::envoyer(function () {
            throw new \RuntimeException('Expected response code "250" but got code "550", recipient unexistant');
        });

        $this->assertFalse($resultat);
    }
}
