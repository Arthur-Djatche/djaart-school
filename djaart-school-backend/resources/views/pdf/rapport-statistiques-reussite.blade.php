<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Statistiques de réussite</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 12px; }
        .header { border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #003fa2; font-size: 20px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .titre { text-align: right; font-size: 14px; font-weight: bold; color: #fe9605; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 6px 8px; border: 1px solid #cbd5e1; text-align: left; }
        th { background-color: #003fa2; color: #fff; }
        .total { font-weight: bold; color: #009ca0; }
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

    <div class="titre">Statistiques de réussite — {{ now()->format('d/m/Y') }}</div>

    @if($total === 0)
        <p>Aucun relevé de notes généré pour l'instant.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Mention</th>
                    <th>Effectif</th>
                    <th>Proportion</th>
                </tr>
            </thead>
            <tbody>
                @foreach($repartition as $mention => $effectif)
                    <tr>
                        <td>{{ $mention }}</td>
                        <td>{{ $effectif }}</td>
                        <td>{{ number_format($effectif / $total * 100, 1, ',', ' ') }} %</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total</td>
                    <td>{{ $total }}</td>
                    <td>100 %</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
