<x-emails.layout :subject="'Bulletin disponible'">
    <p style="font-size:15px; line-height:1.6;">Bonjour,</p>
    <p style="font-size:15px; line-height:1.6;">
        Le bulletin de <strong>{{ $apprenant->prenom }} {{ $apprenant->nom }}</strong> (matricule {{ $apprenant->matricule }})
        pour la séquence <strong>{{ $sequenceLibelle }}</strong> est désormais disponible.
    </p>
    <p style="font-size:15px; line-height:1.6;">
        Moyenne générale : <strong>{{ number_format($bulletin->moyenne_generale, 2) }}/20</strong> — Rang : {{ $bulletin->rang }}
    </p>
    <p style="font-size:15px; line-height:1.6;">
        Il peut être téléchargé depuis votre espace DJAART SCHOOL.
    </p>
</x-emails.layout>
