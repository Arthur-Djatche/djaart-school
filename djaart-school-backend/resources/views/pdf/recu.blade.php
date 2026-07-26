<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Reçu {{ $recu->numero_recu }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 13px; }
        .header { border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #003fa2; font-size: 20px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .numero { text-align: right; font-size: 14px; font-weight: bold; color: #fe9605; }
        table.details { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.details td { padding: 8px; border: 1px solid #cbd5e1; }
        table.details td.label { background-color: #f1f5f9; font-weight: bold; width: 40%; }
        .montant { font-size: 18px; font-weight: bold; color: #009ca0; margin-top: 20px; }
        .footer { margin-top: 40px; font-size: 11px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $etablissement->nom }}</h1>
        <p>{{ $etablissement->sigle }} @if($etablissement->adresse) — {{ $etablissement->adresse }} @endif</p>
    </div>

    <div class="numero">Reçu N° {{ $etablissement->sigle ?? 'ETB' }}-{{ str_pad($recu->numero_recu, 5, '0', STR_PAD_LEFT) }}</div>

    <table class="details">
        <tr>
            <td class="label">Apprenant</td>
            <td>{{ $apprenant->prenom }} {{ $apprenant->nom }} ({{ $apprenant->matricule }})</td>
        </tr>
        <tr>
            <td class="label">Classe</td>
            <td>{{ $classe->libelle }}</td>
        </tr>
        <tr>
            <td class="label">Tranche</td>
            <td>Tranche n°{{ $tranche->numero }}</td>
        </tr>
        <tr>
            <td class="label">Mode de paiement</td>
            <td>{{ $modeLabel }}</td>
        </tr>
        <tr>
            <td class="label">Date de paiement</td>
            <td>{{ $paiement->date_paiement->format('d/m/Y') }}</td>
        </tr>
    </table>

    <p class="montant">Montant encaissé : {{ number_format($paiement->montant, 2, ',', ' ') }}</p>

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
