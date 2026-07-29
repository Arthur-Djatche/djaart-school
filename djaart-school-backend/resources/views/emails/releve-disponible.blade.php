<x-emails.layout :subject="'Relevé de notes disponible'">
    <p style="font-size:15px; line-height:1.6;">Bonjour,</p>
    <p style="font-size:15px; line-height:1.6;">
        Le relevé de notes annuel de <strong>{{ $apprenant->prenom }} {{ $apprenant->nom }}</strong> (matricule {{ $apprenant->matricule }})
        est désormais disponible.
    </p>
    <p style="font-size:15px; line-height:1.6;">
        Moyenne générale : <strong>{{ number_format($releve->moyenne_generale, 2) }}/20</strong> — Mention : {{ $releve->mention }}
    </p>
    <p style="font-size:15px; line-height:1.6;">
        Il peut être téléchargé depuis votre espace DJAART SCHOOL.
    </p>
</x-emails.layout>
