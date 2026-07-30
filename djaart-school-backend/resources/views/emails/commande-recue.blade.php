<x-emails.layout :subject="'Nouvelle commande DJAART SCHOOL'">
    <p style="font-size:15px; line-height:1.6;">Bonjour,</p>
    <p style="font-size:15px; line-height:1.6;">
        Une nouvelle commande vient d'être soumise depuis la page publique DJAART SCHOOL :
    </p>
    <ul style="font-size:14px; line-height:1.8; padding-left:20px;">
        <li><strong>Nom :</strong> {{ $commande->nom }}</li>
        <li><strong>Ville :</strong> {{ $commande->ville }}</li>
        <li><strong>Établissement :</strong> {{ $commande->nom_etablissement }}</li>
        <li><strong>Nombre d'apprenants :</strong> {{ $commande->nombre_apprenants }}</li>
        <li><strong>Téléphone :</strong> {{ $commande->telephone }}</li>
        <li><strong>E-mail :</strong> {{ $commande->email }}</li>
    </ul>
    <p style="font-size:15px; line-height:1.6;">Rendez-vous sur votre tableau de bord pour la valider.</p>
</x-emails.layout>
