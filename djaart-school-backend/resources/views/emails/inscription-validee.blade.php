<x-emails.layout :subject="'Inscription validée'">
    <p style="font-size:15px; line-height:1.6;">Bonjour,</p>
    <p style="font-size:15px; line-height:1.6;">
        L'inscription de <strong>{{ $apprenant->prenom }} {{ $apprenant->nom }}</strong>
        (matricule {{ $apprenant->matricule }}) en classe <strong>{{ $classeLibelle }}</strong>
        vient d'être validée, les frais d'inscription ayant été réglés.
    </p>
    <p style="font-size:15px; line-height:1.6;">
        Établissement : {{ $etablissementNom }}
    </p>
</x-emails.layout>
