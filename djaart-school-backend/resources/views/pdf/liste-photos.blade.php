<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Liste de classe — séance photo</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 12px; }
        .header { border-bottom: 3px solid #003fa2; padding-bottom: 10px; margin-bottom: 20px; }
        .header h1 { color: #003fa2; font-size: 20px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .titre { text-align: right; font-size: 14px; font-weight: bold; color: #fe9605; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 7px 8px; border: 1px solid #cbd5e1; text-align: left; }
        th { background-color: #003fa2; color: #fff; }
        td.ordre { text-align: center; font-weight: bold; width: 8%; }
        .footer { margin-top: 30px; font-size: 10px; color: #64748b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $etablissement->nom }}</h1>
        <p>{{ $etablissement->sigle }} @if($etablissement->adresse) — {{ $etablissement->adresse }} @endif</p>
    </div>

    <div class="titre">Liste de classe — séance photo — {{ $classe->libelle }}</div>

    <table>
        <thead>
            <tr>
                <th class="ordre">Ordre</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Né(e) le</th>
                <th>Matricule</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lignes as $ligne)
                <tr>
                    <td class="ordre">{{ $ligne['ordre'] }}</td>
                    <td>{{ $ligne['nom'] }}</td>
                    <td>{{ $ligne['prenom'] }}</td>
                    <td>{{ $ligne['date_naissance']->format('d/m/Y') }}</td>
                    <td>{{ $ligne['matricule'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Photographiez les apprenants dans cet ordre exact, puis importez les photos dans le même ordre.</p>
        <p>Document de travail généré automatiquement par DJAART SCHOOL — {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
