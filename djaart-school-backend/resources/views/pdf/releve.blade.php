<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé de notes</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 13px; }
        .header { border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #003fa2; font-size: 20px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .titre { text-align: right; font-size: 14px; font-weight: bold; color: #fe9605; }
        table.infos { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.infos td { padding: 6px; border: 1px solid #cbd5e1; }
        table.infos td.label { background-color: #f1f5f9; font-weight: bold; width: 30%; }
        table.notes { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.notes th, table.notes td { padding: 8px; border: 1px solid #cbd5e1; text-align: left; }
        table.notes th { background-color: #003fa2; color: #fff; }
        .resultat { font-size: 16px; font-weight: bold; color: #009ca0; margin-top: 20px; }
        .mention { font-size: 14px; font-weight: bold; color: #fe9605; }
        .footer { margin-top: 40px; font-size: 11px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $etablissement->nom }}</h1>
        <p>{{ $etablissement->sigle }} @if($etablissement->adresse) — {{ $etablissement->adresse }} @endif</p>
    </div>

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

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — Relevé N° {{ str_pad($releve->id, 5, '0', STR_PAD_LEFT) }} — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
