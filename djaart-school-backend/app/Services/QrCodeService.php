<?php

namespace App\Services;

use Endroid\QrCode\Builder\Builder;

class QrCodeService
{
    /**
     * Génère un QR code auto-porteur (encode directement les données
     * d'identification du document) et renvoie une data URI PNG, prête à être
     * embarquée dans une vue Blade via <img src="...">. Aucun portail de
     * vérification public n'existe dans ce projet — le QR n'encode donc pas
     * une URL, mais le texte des données elles-mêmes.
     */
    public function genererDataUri(string $donnees): string
    {
        return (new Builder(data: $donnees, size: 180, margin: 5))->build()->getDataUri();
    }
}
