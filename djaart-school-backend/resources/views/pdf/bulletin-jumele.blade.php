<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin jumelé — {{ $apprenant->nom }} {{ $apprenant->prenom }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 9.5px; }
        .filigrane { position: fixed; top: 32%; left: 20%; width: 60%; opacity: 0.07; z-index: -1; }
        .header { display: table; width: 100%; border-bottom: 3px solid #003fa2; padding-bottom: 8px; margin-bottom: 10px; }
        .header .logo { display: table-cell; width: 55px; vertical-align: middle; }
        .header .logo img { width: 45px; height: 45px; object-fit: contain; }
        .header .identite { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header h1 { color: #003fa2; font-size: 16px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; font-size: 9px; }
        .entete-image { width: 100%; margin-bottom: 8px; }
        .entete-image img { width: 100%; }

        table.infos { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.infos td { padding: 4px 7px; border: 1px solid #cbd5e1; font-size: 9px; }
        table.infos td.label { background-color: #f1f5f9; font-weight: bold; width: 16%; color: #003fa2; }

        .bloc { margin-bottom: 14px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px; }
        .bloc h2 { font-size: 12px; color: #fe9605; text-transform: uppercase; margin: 0 0 8px; text-align: center; }
        .bloc .indisponible { text-align: center; color: #64748b; font-style: italic; padding: 16px 0; }

        table.notes { width: 100%; border-collapse: collapse; }
        table.notes th, table.notes td { padding: 3px 6px; border: 1px solid #cbd5e1; text-align: left; }
        table.notes th { background-color: #003fa2; color: #fff; font-size: 9px; }

        .resultat { font-size: 10px; font-weight: bold; color: #009ca0; margin: 8px 0 0; text-align: center; }

        .footer { margin-top: 16px; font-size: 8px; color: #64748b; text-align: center; }
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
            <div class="logo">@if($logoDataUri)<img src="{{ $logoDataUri }}" alt="Logo">@endif</div>
            <div class="identite">
                <h1>{{ $etablissement->nom }}</h1>
                <p>{{ $etablissement->sigle }} @if($etablissement->adresse) — {{ $etablissement->adresse }} @endif</p>
            </div>
        </div>
    @endif

    <table class="infos">
        <tr>
            <td class="label">Apprenant</td>
            <td>{{ $apprenant->nom }} {{ $apprenant->prenom }} ({{ $apprenant->matricule }})</td>
            <td class="label">Classe</td>
            <td>{{ $classe->libelle }} ({{ $effectif }} élèves)</td>
        </tr>
        <tr>
            <td class="label">Année académique</td>
            <td colspan="3">{{ $anneeAcademique->libelle }}</td>
        </tr>
    </table>

    @foreach($blocs as $bloc)
        <div class="bloc">
            <h2>{{ $bloc['sequence']?->libelle ?? 'Séquence jumelée' }}</h2>

            @if($bloc['bulletin'])
                <table class="notes">
                    <thead>
                        <tr>
                            <th>Matière</th>
                            <th>Coefficient</th>
                            <th>Note / 20</th>
                            <th>Appréciation</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bloc['bulletin']->details_lignes as $ligne)
                            <tr>
                                <td>{{ $ligne['matiere'] }}</td>
                                <td>{{ $ligne['coefficient'] }}</td>
                                <td>{{ $ligne['absent'] ? 'Absent' : number_format($ligne['valeur'], 2, ',', ' ') }}</td>
                                <td>{{ $ligne['absent'] ? '—' : $ligne['appreciation'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="resultat">
                    Moyenne : {{ number_format($bloc['bulletin']->moyenne_generale, 2, ',', ' ') }} / 20
                    — Rang : {{ $bloc['bulletin']->rang }} / {{ $effectif }}
                </p>
            @else
                <p class="indisponible">Bulletin non disponible (séquence pas encore clôturée).</p>
            @endif
        </div>
    @endforeach

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
