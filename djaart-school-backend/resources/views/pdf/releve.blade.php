<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé de notes</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 12px; }
        .filigrane { position: fixed; top: 32%; left: 20%; width: 60%; opacity: 0.07; z-index: -1; }
        .entete-image { width: 100%; margin-bottom: 10px; }
        .entete-image img { width: 100%; }
        .header { display: table; width: 100%; border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 16px; }
        .header .logo { display: table-cell; width: 60px; vertical-align: middle; }
        .header .logo img { width: 50px; height: 50px; object-fit: contain; }
        .header .identite { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header h1 { color: #003fa2; font-size: 19px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .titre { text-align: center; font-size: 14px; font-weight: bold; color: #fe9605; margin-bottom: 12px; text-transform: uppercase; }
        table.infos { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.infos td { padding: 6px 8px; border: 1px solid #cbd5e1; }
        table.infos td.label { background-color: #f1f5f9; font-weight: bold; width: 22%; color: #003fa2; }
        table.notes { width: 100%; border-collapse: collapse; margin-top: 16px; }
        table.notes th, table.notes td { padding: 7px 8px; border: 1px solid #cbd5e1; text-align: left; }
        table.notes th { background-color: #003fa2; color: #fff; }
        .resultat { font-size: 15px; font-weight: bold; color: #009ca0; margin-top: 18px; }
        .mention { font-size: 13px; font-weight: bold; color: #fe9605; }
        .signature { margin-top: 30px; display: table; width: 100%; }
        .signature .zone { display: table-cell; width: 50%; text-align: center; vertical-align: bottom; }
        .signature img { height: 45px; object-fit: contain; }
        .signature p { margin: 2px 0; font-size: 10px; }
        .footer { margin-top: 20px; font-size: 10px; color: #64748b; }
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

    <div class="titre">
        Relevé de notes officiel — {{ $semestre ? $semestre->libelle : $anneeAcademique->libelle }}
    </div>

    <table class="infos">
        <tr>
            <td class="label">Nom</td>
            <td>{{ $apprenant->nom }}</td>
            <td class="label">Prénom</td>
            <td>{{ $apprenant->prenom }}</td>
        </tr>
        <tr>
            <td class="label">Né(e) le</td>
            <td>{{ $apprenant->date_naissance->format('d/m/Y') }}</td>
            <td class="label">À</td>
            <td>{{ $apprenant->lieu_naissance ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Matricule</td>
            <td>{{ $apprenant->matricule }}</td>
            <td class="label">Classe</td>
            <td>{{ $classe->libelle }}</td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            @if($semestre)
                <tr>
                    <th>Unité d'enseignement</th>
                    <th>Crédits ECTS</th>
                    <th>Moyenne UE / 20</th>
                    <th>Validée</th>
                </tr>
            @else
                <tr>
                    <th>Séquence</th>
                    <th>Moyenne / 20</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                @if($semestre)
                    <tr>
                        <td>{{ $ligne['matiere'] }}</td>
                        <td>{{ $ligne['credits_ects'] }}</td>
                        <td>{{ number_format($ligne['moyenne_ue'], 2, ',', ' ') }}</td>
                        <td>{{ $ligne['validee'] ? 'Oui' : 'Non' }}</td>
                    </tr>
                @else
                    <tr>
                        <td>{{ $ligne['sequence'] }}</td>
                        <td>{{ number_format($ligne['moyenne'], 2, ',', ' ') }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
    </table>

    <p class="resultat">Moyenne générale : {{ number_format($releve->moyenne_generale, 2, ',', ' ') }} / 20</p>
    <p class="mention">Mention : {{ $releve->mention }}</p>

    <div class="signature">
        <div class="zone"></div>
        <div class="zone">
            @if($signatureDataUri)
                <img src="{{ $signatureDataUri }}" alt="Signature"><br>
            @endif
            <p>Fait le {{ now()->format('d/m/Y') }}</p>
            <p><strong>Le Directeur / La Directrice</strong></p>
        </div>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — Relevé N° {{ str_pad($releve->id, 5, '0', STR_PAD_LEFT) }} — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
