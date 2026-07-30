<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>{{ $typeLabel }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 13px; }
        .filigrane { position: fixed; top: 32%; left: 20%; width: 60%; opacity: 0.07; z-index: -1; }
        .entete-image { width: 100%; margin-bottom: 10px; }
        .entete-image img { width: 100%; }
        .header { display: table; width: 100%; border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 10px; }
        .header .logo { display: table-cell; width: 70px; vertical-align: middle; }
        .header .logo img { width: 55px; height: 55px; object-fit: contain; }
        .header .identite { display: table-cell; vertical-align: middle; text-align: center; }
        .header h1 { color: #003fa2; font-size: 20px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .titre { text-align: center; font-size: 18px; font-weight: bold; color: #fe9605; margin: 30px 0; text-transform: uppercase; }
        .numero { text-align: right; font-size: 12px; color: #64748b; }
        .corps { font-size: 14px; line-height: 1.8; margin: 30px 40px; text-align: justify; }
        .corps strong { color: #003fa2; }
        .signature { margin-top: 60px; text-align: right; margin-right: 40px; }
        .signature img { height: 45px; object-fit: contain; }
        .qr { text-align: center; margin-top: 40px; }
        .footer { margin-top: 40px; font-size: 11px; color: #64748b; text-align: center; }
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
            <div class="logo"></div>
        </div>
    @endif

    <div class="numero">N° {{ $etablissement->sigle ?? 'ETB' }}-ATT-{{ str_pad($attestation->numero, 5, '0', STR_PAD_LEFT) }}</div>

    <div class="titre">{{ $typeLabel }}</div>

    <div class="corps">
        {{ $etablissement->signature_titre ?? 'Le Directeur' }} de <strong>{{ $etablissement->nom }}</strong> atteste par la présente que :
        <br><br>
        <strong>{{ $apprenant->prenom }} {{ $apprenant->nom }}</strong>, matricule <strong>{{ $apprenant->matricule }}</strong>,
        né(e) le {{ $apprenant->date_naissance->format('d/m/Y') }}@if($apprenant->lieu_naissance) à <strong>{{ $apprenant->lieu_naissance }}</strong>@endif,
        est régulièrement inscrit(e) en classe de <strong>{{ $classe->libelle }}</strong>
        au titre de l'année académique <strong>{{ $anneeAcademique->libelle }}</strong>.
        <br><br>
        En foi de quoi la présente attestation lui est délivrée pour servir et valoir ce que de droit.
    </div>

    <div class="signature">
        Fait le {{ now()->format('d/m/Y') }}<br>
        <strong>{{ $etablissement->signature_titre ?? 'Le Directeur / La Directrice' }}</strong><br>
        @if($signatureDataUri)
            <img src="{{ $signatureDataUri }}" alt="Signature">
        @endif
    </div>

    <div class="qr">
        <img src="{{ $qrDataUri }}" width="90" height="90" alt="QR de vérification">
    </div>

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
