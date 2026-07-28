<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Procès-verbal des notes</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 12px; }
        .filigrane { position: fixed; top: 32%; left: 20%; width: 60%; opacity: 0.07; z-index: -1; }
        .entete-image { width: 100%; margin-bottom: 10px; }
        .entete-image img { width: 100%; }
        .header { border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 16px; }
        .header h1 { color: #003fa2; font-size: 18px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .titre { text-align: center; font-size: 14px; font-weight: bold; color: #fe9605; margin-bottom: 6px; text-transform: uppercase; }
        .sous-titre { text-align: center; font-size: 12px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 6px 8px; border: 1px solid #cbd5e1; text-align: left; }
        th { background-color: #003fa2; color: #fff; }
        .signature { margin-top: 40px; display: table; width: 100%; }
        .signature .zone { display: table-cell; width: 50%; text-align: center; vertical-align: bottom; }
        .signature .ligne { display: inline-block; width: 160px; border-bottom: 1px solid #001335; margin-bottom: 4px; }
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
            <h1>{{ $etablissement->nom }}</h1>
            <p>{{ $etablissement->sigle }} @if($etablissement->adresse) — {{ $etablissement->adresse }} @endif</p>
        </div>
    @endif

    <div class="titre">Procès-verbal des notes</div>
    <div class="sous-titre">
        {{ $affectation->matiere->nom }} — {{ $affectation->classe->libelle }} — {{ $periode->libelle }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom et prénom</th>
                @foreach($types as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                <tr>
                    <td>{{ $ligne['apprenant']->matricule }}</td>
                    <td>{{ $ligne['apprenant']->nom }} {{ $ligne['apprenant']->prenom }}</td>
                    @foreach(array_keys($types) as $type)
                        <td>{{ $ligne['valeurs'][$type] }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signature">
        <div class="zone"></div>
        <div class="zone">
            <div class="ligne">&nbsp;</div>
            <p><strong>{{ $affectation->enseignant->name }}</strong></p>
            <p>Enseignant</p>
        </div>
    </div>

    <div class="footer">
        <p>Document de travail généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
