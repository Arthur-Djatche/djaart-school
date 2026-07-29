<x-emails.layout :subject="'Nouvelle demande de démo'">
    <p style="font-size:15px; line-height:1.6;">Bonjour,</p>
    <p style="font-size:15px; line-height:1.6;">
        Une nouvelle demande de démo vient d'être soumise depuis la page publique DJAART SCHOOL :
    </p>
    <ul style="font-size:14px; line-height:1.8; padding-left:20px;">
        <li><strong>Nom :</strong> {{ $demande->nom }}</li>
        <li><strong>E-mail :</strong> {{ $demande->email }}</li>
        <li><strong>Téléphone :</strong> {{ $demande->telephone ?? '—' }}</li>
        <li><strong>Établissement :</strong> {{ $demande->nom_etablissement ?? '—' }}</li>
        <li><strong>Effectif estimé :</strong> {{ $demande->effectif_estime ?? '—' }}</li>
        @if ($demande->message)
            <li><strong>Message :</strong> {{ $demande->message }}</li>
        @endif
    </ul>
</x-emails.layout>
