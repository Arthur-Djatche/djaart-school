<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin annuel détaillé — {{ $classe->libelle }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 9.5px; }
        .filigrane { position: fixed; top: 32%; left: 20%; width: 60%; opacity: 0.07; z-index: -1; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .header { display: table; width: 100%; border-bottom: 3px solid #003fa2; padding-bottom: 8px; margin-bottom: 10px; }
        .header .logo { display: table-cell; width: 55px; vertical-align: middle; }
        .header .logo img { width: 45px; height: 45px; object-fit: contain; }
        .header .identite { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header h1 { color: #003fa2; font-size: 16px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; font-size: 9px; }
        .entete-image { width: 100%; margin-bottom: 8px; }
        .entete-image img { width: 100%; }
        .titre { text-align: center; font-size: 13px; font-weight: bold; color: #fe9605; margin: 10px 0; text-transform: uppercase; }

        table.infos { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.infos td { padding: 4px 7px; border: 1px solid #cbd5e1; font-size: 9px; }
        table.infos td.label { background-color: #f1f5f9; font-weight: bold; width: 16%; color: #003fa2; }

        table.matrice { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.matrice th, table.matrice td { padding: 4px 6px; border: 1px solid #cbd5e1; text-align: center; }
        table.matrice th { background-color: #003fa2; color: #fff; font-size: 8.5px; }
        table.matrice td.matiere { text-align: left; font-weight: bold; }
        table.matrice td.moyenne-annuelle { background-color: #f1f5f9; font-weight: bold; }

        .resultat { font-size: 12px; font-weight: bold; color: #009ca0; margin: 12px 0 0; text-align: center; }
        .avertissement { text-align: center; color: #dc2626; font-size: 9px; margin-top: 4px; }

        .footer { margin-top: 16px; font-size: 8px; color: #64748b; text-align: center; }
    </style>
</head>
<body>
    @foreach($pages as $page)
        <div class="page">
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

            <div class="titre">Bulletin annuel détaillé — {{ $anneeAcademique->libelle }}</div>

            <table class="infos">
                <tr>
                    <td class="label">Apprenant</td>
                    <td>{{ $page['apprenant']->nom }} {{ $page['apprenant']->prenom }} ({{ $page['apprenant']->matricule }})</td>
                    <td class="label">Classe</td>
                    <td>{{ $classe->libelle }}</td>
                </tr>
            </table>

            <table class="matrice">
                <thead>
                    <tr>
                        <th>Matière</th>
                        @foreach($sequences as $sequence)
                            <th>{{ $sequence->libelle }}</th>
                        @endforeach
                        <th>Moyenne annuelle</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($page['matrice'] as $ligne)
                        <tr>
                            <td class="matiere">{{ $ligne['matiere'] }}</td>
                            @foreach($sequences as $sequence)
                                <td>{{ $ligne['notes_par_sequence'][$sequence->id] }}</td>
                            @endforeach
                            <td class="moyenne-annuelle">{{ $ligne['moyenne_annuelle'] !== null ? number_format($ligne['moyenne_annuelle'], 2, ',', ' ') : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <p class="resultat">
                Moyenne générale annuelle :
                {{ $page['moyenne_annuelle'] !== null ? number_format($page['moyenne_annuelle'], 2, ',', ' ').' / 20' : '—' }}
            </p>
            @if($page['sequences_manquantes'] > 0)
                <p class="avertissement">{{ $page['sequences_manquantes'] }} séquence(s) pas encore clôturée(s) — moyenne calculée sur les séquences disponibles uniquement.</p>
            @endif

            <div class="footer">
                <p>Document généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    @endforeach
</body>
</html>
