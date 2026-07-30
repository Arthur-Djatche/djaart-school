<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Relevé de notes</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 12px; }
        .filigrane { position: fixed; top: 32%; left: 20%; width: 60%; opacity: 0.07; z-index: -1; }
        .entete-image { width: 100%; margin-bottom: 8px; }
        .entete-image img { width: 100%; }
        .header { display: table; width: 100%; border-bottom: 3px solid #003fa2; padding-bottom: 8px; margin-bottom: 10px; }
        .header .logo { display: table-cell; width: 60px; vertical-align: middle; }
        .header .logo img { width: 50px; height: 50px; object-fit: contain; }
        .header .identite { display: table-cell; vertical-align: middle; padding-left: 10px; }
        .header h1 { color: #003fa2; font-size: 17px; margin: 0; }
        .header p { margin: 2px 0; color: #001335; }
        .titre { text-align: center; font-size: 13px; font-weight: bold; color: #fe9605; margin-bottom: 8px; text-transform: uppercase; }
        table.infos { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 10px; }
        table.infos td { padding: 3px 6px; border: 1px solid #cbd5e1; }
        table.infos td.label { background-color: #f1f5f9; font-weight: bold; width: 14%; color: #003fa2; }
        table.notes { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 9px; }
        table.notes th, table.notes td { padding: 3px 5px; border: 1px solid #cbd5e1; text-align: left; }
        table.notes th { background-color: #003fa2; color: #fff; font-size: 9px; }
        .semestre-titre td { background-color: #001335; color: #fff; font-weight: bold; font-size: 10px; padding: 4px 6px; }
        .ue-fondamentale td.ue-label { background-color: #dbeafe; }
        .ue-professionnelle td.ue-label { background-color: #dcfce7; }
        .ue-transversale td.ue-label { background-color: #fef3c7; }
        .ue-label { font-weight: bold; }
        .semestre-pied td { background-color: #f1f5f9; font-weight: bold; font-size: 9px; padding: 3px 6px; }
        .resultat { font-size: 14px; font-weight: bold; color: #009ca0; margin-top: 12px; }
        .mention { font-size: 12px; font-weight: bold; color: #fe9605; }
        .signature { margin-top: 16px; display: table; width: 100%; }
        .signature .zone { display: table-cell; width: 50%; text-align: center; vertical-align: bottom; }
        .signature img { height: 40px; object-fit: contain; }
        .signature p { margin: 2px 0; font-size: 9px; }
        .footer { margin-top: 10px; font-size: 9px; color: #64748b; }
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

    <div class="titre">
        Relevé de notes officiel — {{ $anneeAcademique->libelle }}
    </div>

    <table class="infos">
        <tr>
            <td class="label">Nom</td>
            <td>{{ $apprenant->nom }}</td>
            <td class="label">Prénom</td>
            <td>{{ $apprenant->prenom }}</td>
        </tr>
        <tr>
            <td class="label">Né(e) le</td>
            <td>{{ $apprenant->date_naissance->format('d/m/Y') }}</td>
            <td class="label">À</td>
            <td>{{ $apprenant->lieu_naissance ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Matricule</td>
            <td>{{ $apprenant->matricule }}</td>
            <td class="label">Classe</td>
            <td>{{ $classe->libelle }}</td>
        </tr>
    </table>

    @if($estLmd)
        <table class="notes">
            <thead>
                <tr>
                    <th style="width:8%">Code UE</th>
                    <th style="width:20%">Intitulé UE</th>
                    <th style="width:8%">Moy. UE</th>
                    <th style="width:8%">Code EC</th>
                    <th style="width:22%">Élément constitutif (EC)</th>
                    <th style="width:8%">Crédits</th>
                    <th style="width:8%">Note/20</th>
                    <th style="width:8%">Mention</th>
                    <th style="width:10%">Session</th>
                </tr>
            </thead>
            <tbody>
                @foreach($semestresData as $semestreData)
                    <tr class="semestre-titre"><td colspan="9">{{ $semestreData['libelle'] }}</td></tr>
                    @foreach($semestreData['unites'] as $unite)
                        @foreach($unite['matieres'] as $index => $matiere)
                            <tr class="ue-{{ $unite['type'] }}">
                                @if($index === 0)
                                    <td class="ue-label" rowspan="{{ count($unite['matieres']) }}">{{ $unite['code'] }}</td>
                                    <td class="ue-label" rowspan="{{ count($unite['matieres']) }}">{{ $unite['nom'] }}</td>
                                    <td class="ue-label" rowspan="{{ count($unite['matieres']) }}">{{ number_format($unite['moyenne'], 2, ',', ' ') }}</td>
                                @endif
                                <td>{{ $matiere['code'] }}</td>
                                <td>{{ $matiere['nom'] }}</td>
                                <td>{{ $matiere['credits_ects'] }}</td>
                                <td>{{ number_format($matiere['note'], 2, ',', ' ') }}</td>
                                <td>{{ $matiere['mention_lettre'] }}</td>
                                <td>{{ $matiere['session'] }}</td>
                            </tr>
                        @endforeach
                    @endforeach
                    <tr class="semestre-pied">
                        <td colspan="6">Crédits acquis dans le {{ $semestreData['libelle'] }} : {{ $semestreData['credits_acquis'] }}/{{ $semestreData['credits_total'] }}</td>
                        <td colspan="3">Moyenne : {{ number_format($semestreData['moyenne'], 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="resultat">
            Crédits acquis dans l'année : {{ $creditsAnnuelsAcquis }}/{{ $creditsAnnuelsTotal }} —
            Moyenne annuelle : {{ number_format($releve->moyenne_generale, 2, ',', ' ') }} / 20
        </p>
        <p class="mention">Mention : {{ $releve->mention }}</p>
    @else
        <table class="notes">
            <thead>
                <tr>
                    <th>Séquence</th>
                    <th>Moyenne / 20</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lignes as $ligne)
                    <tr>
                        <td>{{ $ligne['sequence'] }}</td>
                        <td>{{ number_format($ligne['moyenne'], 2, ',', ' ') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <p class="resultat">Moyenne générale : {{ number_format($releve->moyenne_generale, 2, ',', ' ') }} / 20</p>
        <p class="mention">Mention : {{ $releve->mention }}</p>
    @endif

    <div class="signature">
        <div class="zone">
            @if($estLmd && ($chefDepartementNom ?? null))
                <p>Fait le {{ now()->format('d/m/Y') }}</p>
                <p><strong>Le Chef de Département</strong></p>
                <p>{{ $chefDepartementNom }}</p>
            @endif
        </div>
        <div class="zone">
            @if($signatureDataUri)
                <img src="{{ $signatureDataUri }}" alt="Signature"><br>
            @endif
            <p>Fait le {{ now()->format('d/m/Y') }}</p>
            <p><strong>{{ $etablissement->signature_titre ?? 'Le Directeur / La Directrice' }}</strong></p>
        </div>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par DJAART SCHOOL — Relevé N° {{ str_pad($releve->id, 5, '0', STR_PAD_LEFT) }} — {{ now()->format('d/m/Y H:i') }}</p>
        <p>Il n'est délivré qu'un seul exemplaire original de ce relevé de notes.</p>
    </div>
</body>
</html>
