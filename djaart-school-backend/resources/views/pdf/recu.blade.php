<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $recu->numero_recu }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 13px; }
        .filigrane { position: fixed; top: 32%; left: 20%; width: 60%; opacity: 0.07; z-index: -1; }
        .entete-image { width: 100%; margin-bottom: 10px; }
        .entete-image img { width: 100%; }
        .header { display: table; width: 100%; border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 16px; }
        .header .logo { display: table-cell; width: 60px; vertical-align: middle; }
        .header .logo img { width: 50px; height: 50px; object-fit: contain; }
        .header .identite { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header h1 { color: #003fa2; font-size: 19px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .numero { text-align: right; font-size: 14px; font-weight: bold; color: #fe9605; margin-bottom: 8px; }
        table.details { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.details td { padding: 8px; border: 1px solid #cbd5e1; }
        table.details td.label { background-color: #f1f5f9; font-weight: bold; width: 40%; color: #003fa2; }
        .montant { font-size: 18px; font-weight: bold; color: #009ca0; margin-top: 20px; }
        .solde { font-size: 12px; color: {{ $soldeRestant > 0 ? '#fe9605' : '#009ca0' }}; margin-top: 4px; }
        .signature { margin-top: 40px; display: table; width: 100%; }
        .signature .zone { display: table-cell; width: 50%; text-align: center; vertical-align: bottom; }
        .signature .ligne { display: inline-block; width: 160px; border-bottom: 1px solid #001335; margin-bottom: 4px; }
        .signature p { margin: 2px 0; font-size: 10px; }
        .footer { margin-top: 30px; font-size: 11px; color: #64748b; }
    </style>
</head>
<body>
    @if($logoDataUri)
        <img class="filigrane" src="{{ $logoDataUri }}" alt="">
    @endif

    @if($enteteDataUri)
        <div class="entete-image"><img src="{{ $enteteDataUri }}" alt="En-tête"></div>
    @else
        <div class="header">
            <div class="logo">
                @if($logoDataUri)
                    <img src="{{ $logoDataUri }}" alt="Logo">
                @endif
            </div>
            <div class="identite">
                <h1>{{ $etablissement->nom }}</h1>
                <p>{{ $etablissement->sigle }} @if($etablissement->adresse) — {{ $etablissement->adresse }} @endif</p>
            </div>
        </div>
    @endif

    <div class="numero">Reçu N° {{ $etablissement->sigle ?? 'ETB' }}-{{ str_pad($recu->numero_recu, 5, '0', STR_PAD_LEFT) }}</div>

    <table class="details">
        <tr>
            <td class="label">Nom</td>
            <td>{{ $apprenant->nom }}</td>
        </tr>
        <tr>
            <td class="label">Prénom</td>
            <td>{{ $apprenant->prenom }}</td>
        </tr>
        <tr>
            <td class="label">Matricule</td>
            <td>{{ $apprenant->matricule }}</td>
        </tr>
        <tr>
            <td class="label">Classe</td>
            <td>{{ $classe->libelle }}</td>
        </tr>
        <tr>
            <td class="label">Année académique</td>
            <td>{{ $anneeAcademique->libelle }}</td>
        </tr>
        <tr>
            <td class="label">Tranche</td>
            <td>Tranche n°{{ $tranche->numero }}</td>
        </tr>
        <tr>
            <td class="label">Mode de paiement</td>
            <td>{{ $modeLabel }}</td>
        </tr>
        <tr>
            <td class="label">Date de paiement</td>
            <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
        </tr>
    </table>

    <p class="montant">Montant encaissé : {{ number_format($paiement->montant, 2, ',', ' ') }}</p>
    <p class="solde">
        @if($soldeRestant > 0)
            Solde restant sur cette tranche : {{ number_format($soldeRestant, 2, ',', ' ') }}
        @else
            Tranche intégralement soldée
        @endif
    </p>

    <div class="signature">
        <div class="zone"></div>
        <div class="zone">
            <div class="ligne">&nbsp;</div>
            <p><strong>Signature du caissier</strong></p>
        </div>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
