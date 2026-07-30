<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin {{ $sequence->libelle }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 11px; }
        .filigrane { position: fixed; top: 32%; left: 20%; width: 60%; opacity: 0.07; z-index: -1; }
        .header { display: table; width: 100%; border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 14px; }
        .header .logo { display: table-cell; width: 60px; vertical-align: middle; }
        .header .logo img { width: 50px; height: 50px; object-fit: contain; }
        .header .identite { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header h1 { color: #003fa2; font-size: 18px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; font-size: 10px; }
        .entete-image { width: 100%; margin-bottom: 10px; }
        .entete-image img { width: 100%; }
        .titre { text-align: center; font-size: 15px; font-weight: bold; color: #fe9605; margin: 12px 0; text-transform: uppercase; }
        .enseignant { display: block; font-size: 8px; font-weight: normal; color: #64748b; font-style: italic; }

        table.infos { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.infos td { padding: 5px 8px; border: 1px solid #cbd5e1; font-size: 10px; }
        table.infos td.label { background-color: #f1f5f9; font-weight: bold; width: 16%; color: #003fa2; }

        table.notes { width: 100%; border-collapse: collapse; margin-top: 6px; }
        table.notes th, table.notes td { padding: 5px 7px; border: 1px solid #cbd5e1; text-align: left; }
        table.notes th { background-color: #003fa2; color: #fff; font-size: 10px; }
        tr.groupe-titre td { background-color: #e2e8f0; font-weight: bold; color: #001335; }
        tr.groupe-total td { background-color: #f1f5f9; font-weight: bold; }

        .resultat { font-size: 13px; font-weight: bold; color: #009ca0; margin: 14px 0 10px; text-align: center; }

        .encadres { display: table; width: 100%; margin-top: 10px; }
        .encadre-col { display: table-cell; width: 33.33%; vertical-align: top; padding-right: 8px; }
        .encadre { border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px; min-height: 70px; }
        .encadre h3 { margin: 0 0 6px; font-size: 10px; color: #003fa2; text-transform: uppercase; }
        .encadre p { margin: 2px 0; font-size: 10px; }

        .signature { margin-top: 30px; display: table; width: 100%; }
        .signature .zone { display: table-cell; width: 50%; text-align: center; vertical-align: bottom; }
        .signature img { height: 45px; object-fit: contain; }
        .signature p { margin: 2px 0; font-size: 10px; }

        .footer { margin-top: 20px; font-size: 9px; color: #64748b; text-align: center; }
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

    <div class="titre">Bulletin — {{ $sequence->libelle }} ({{ $anneeAcademique->libelle }})</div>

    <table class="infos">
        <tr>
            <td class="label">Nom</td>
            <td>{{ $apprenant->nom }}</td>
            <td class="label">Prénom</td>
            <td>{{ $apprenant->prenom }}</td>
        </tr>
        <tr>
            <td class="label">Matricule</td>
            <td>{{ $apprenant->matricule }}</td>
            <td class="label">Sexe</td>
            <td>{{ $apprenant->sexe }}</td>
        </tr>
        <tr>
            <td class="label">Né(e) le</td>
            <td>{{ $apprenant->date_naissance->format('d/m/Y') }}</td>
            <td class="label">À</td>
            <td>{{ $apprenant->lieu_naissance ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Classe</td>
            <td>{{ $classe->libelle }} ({{ $effectif }} élèves)</td>
            <td class="label">Titulaire</td>
            <td>{{ $classe->professeurPrincipal?->name ?? '—' }}</td>
        </tr>
    </table>

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
            @foreach($detailsGroupes as $groupe)
                <tr class="groupe-titre"><td colspan="4">{{ $groupe['libelle'] }}</td></tr>
                @foreach($lignes as $ligne)
                    @if(($ligne['groupe'] ?? 'Non groupé') === $groupe['libelle'])
                        <tr>
                            <td>{{ $ligne['matiere'] }}<span class="enseignant">{{ $ligne['enseignant'] }}</span></td>
                            <td>{{ $ligne['coefficient'] }}</td>
                            <td>{{ $ligne['absent'] ? 'Absent' : number_format($ligne['valeur'], 2, ',', ' ') }}</td>
                            <td>{{ $ligne['absent'] ? '—' : $ligne['appreciation'] }}</td>
                        </tr>
                    @endif
                @endforeach
                <tr class="groupe-total">
                    <td>Total {{ $groupe['libelle'] }}</td>
                    <td>{{ $groupe['total_coefficient'] }}</td>
                    <td colspan="2">Moyenne : {{ number_format($groupe['moyenne'], 2, ',', ' ') }} / 20</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="resultat">
        Moyenne générale : {{ number_format($bulletin->moyenne_generale, 2, ',', ' ') }} / 20
        — Rang : {{ $bulletin->rang }} / {{ $effectif }}
    </p>

    <div class="encadres">
        <div class="encadre-col">
            <div class="encadre">
                <h3>Profil de la classe</h3>
                <p>Moyenne classe : {{ number_format($bulletin->moyenne_classe, 2, ',', ' ') }} / 20</p>
                <p>Taux de réussite : {{ number_format($bulletin->taux_reussite, 1, ',', ' ') }} %</p>
                <p>Moyenne max : {{ number_format($bulletin->moyenne_max, 2, ',', ' ') }} / 20</p>
                <p>Moyenne min : {{ number_format($bulletin->moyenne_min, 2, ',', ' ') }} / 20</p>
            </div>
        </div>
        <div class="encadre-col">
            <div class="encadre">
                <h3>Conduite</h3>
                <p>Absences : {{ $bulletin->absences }} (dont {{ $bulletin->absences_non_justifiees }} non justifiées)</p>
                <p>Retards : {{ $bulletin->retards }} (dont {{ $bulletin->retards_non_justifies }} non justifiés)</p>
                <p>Mention : {{ $bulletin->mention_conduite ? ucfirst(str_replace('_', ' ', $bulletin->mention_conduite)) : '—' }}</p>
            </div>
        </div>
        <div class="encadre-col" style="padding-right: 0;">
            <div class="encadre">
                <h3>Travail de l'élève</h3>
                @if($bulletin->tableau_honneur)
                    <p><strong>Tableau d'honneur</strong> (moyenne &gt;= 12)</p>
                @endif
                @if($bulletin->mention_travail)
                    <p>Mention : {{ ucfirst(str_replace('_', ' ', $bulletin->mention_travail)) }}</p>
                @endif
                @if(! $bulletin->tableau_honneur && ! $bulletin->mention_travail)
                    <p>—</p>
                @endif
            </div>
        </div>
    </div>

    <div class="signature">
        <div class="zone"></div>
        <div class="zone">
            @if($signatureDataUri)
                <img src="{{ $signatureDataUri }}" alt="Signature"><br>
            @endif
            <p>Fait le {{ now()->format('d/m/Y') }}</p>
            <p><strong>{{ $etablissement->signature_titre ?? 'Le Directeur / La Directrice' }}</strong></p>
        </div>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
