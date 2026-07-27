<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Carte scolaire</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #001335; font-size: 11px; margin: 0; }
        .carte {
            width: 340px; height: 214px; border: 2px solid #003fa2; border-radius: 10px;
            padding: 12px; box-sizing: border-box; position: relative;
        }
        .entete { border-bottom: 2px solid #fe9605; padding-bottom: 6px; margin-bottom: 8px; }
        .entete h1 { font-size: 13px; color: #003fa2; margin: 0; }
        .entete p { font-size: 9px; color: #001335; margin: 1px 0; }
        .contenu { }
        .photo {
            float: left; width: 70px; height: 85px; border: 1px solid #cbd5e1; margin-right: 10px;
            object-fit: cover;
        }
        .infos p { margin: 2px 0; font-size: 11px; }
        .infos .label { color: #64748b; font-size: 9px; }
        .qr { position: absolute; bottom: 10px; right: 10px; }
        .validite { font-size: 9px; color: #009ca0; font-weight: bold; margin-top: 6px; }
        .numero { position: absolute; top: 10px; right: 12px; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>
    <div class="carte">
        <div class="numero">
            N° {{ $etablissement->sigle ?? 'ETB' }}-{{ str_pad($carte->numero, 5, '0', STR_PAD_LEFT) }}
            @if($carte->numero_duplicata > 0)
                (Duplicata {{ $carte->numero_duplicata }})
            @endif
        </div>
        <div class="entete">
            <h1>{{ $etablissement->nom }}</h1>
            <p>Carte scolaire — {{ $anneeAcademique->libelle }}</p>
        </div>
        <div class="contenu">
            <img class="photo" src="{{ $photoDataUri }}" alt="Photo">
            <div class="infos">
                <p class="label">Nom et prénom</p>
                <p><strong>{{ $apprenant->nom }} {{ $apprenant->prenom }}</strong></p>
                <p class="label">Matricule</p>
                <p>{{ $apprenant->matricule }}</p>
                <p class="label">Classe</p>
                <p>{{ $classe->libelle }}</p>
                <p class="validite">Valable jusqu'au {{ $carte->date_expiration->format('d/m/Y') }}</p>
            </div>
        </div>
        <div class="qr">
            <img src="{{ $qrDataUri }}" width="50" height="50" alt="QR de vérification">
        </div>
    </div>
</body>
</html>
