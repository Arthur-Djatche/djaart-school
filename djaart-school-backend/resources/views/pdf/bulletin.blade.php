<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bulletin {{ $sequence->libelle }}</title>
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
        .footer { margin-top: 40px; font-size: 11px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $etablissement->nom }}</h1>
        <p>{{ $etablissement->sigle }} @if($etablissement->adresse) — {{ $etablissement->adresse }} @endif</p>
    </div>

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
            <td class="label">Classe</td>
            <td>{{ $classe->libelle }}</td>
        </tr>
    </table>

    <table class="notes">
        <thead>
            <tr>
                <th>Matière</th>
                <th>Coefficient</th>
                <th>Note / 20</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                <tr>
                    <td>{{ $ligne['matiere'] }}</td>
                    <td>{{ $ligne['coefficient'] }}</td>
                    <td>{{ $ligne['absent'] ? 'Absent' : number_format($ligne['valeur'], 2, ',', ' ') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="resultat">
        Moyenne générale : {{ number_format($bulletin->moyenne_generale, 2, ',', ' ') }} / 20
        — Rang : {{ $bulletin->rang }} / {{ $effectif }}
    </p>

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
