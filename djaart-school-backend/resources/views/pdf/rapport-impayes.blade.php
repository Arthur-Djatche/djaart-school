<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport des impayés</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 12px; }
        .header { border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #003fa2; font-size: 20px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .titre { text-align: right; font-size: 14px; font-weight: bold; color: #fe9605; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 6px 8px; border: 1px solid #cbd5e1; text-align: left; }
        th { background-color: #003fa2; color: #fff; }
        .retard { color: #b91c1c; font-weight: bold; }
        .footer { margin-top: 30px; font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $etablissement->nom ?? 'DJAART SCHOOL — Tous établissements' }}</h1>
        @if($etablissement?->adresse)
            <p>{{ $etablissement->sigle }} — {{ $etablissement->adresse }}</p>
        @endif
    </div>

    <div class="titre">Rapport des impayés — {{ now()->format('d/m/Y') }}</div>

    @if($lignes->isEmpty())
        <p>Aucun impayé en retard à ce jour.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Apprenant</th>
                    <th>Matricule</th>
                    <th>Classe</th>
                    <th>Tranche</th>
                    <th>Solde (FCFA)</th>
                    <th>Retard</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td>{{ $ligne['apprenant'] }}</td>
                        <td>{{ $ligne['matricule'] }}</td>
                        <td>{{ $ligne['classe'] }}</td>
                        <td>n° {{ $ligne['tranche_numero'] }}</td>
                        <td>{{ number_format($ligne['solde'], 0, ',', ' ') }}</td>
                        <td class="retard">{{ $ligne['jours_retard'] }} j</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
