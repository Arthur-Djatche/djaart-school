<x-emails.layout :subject="'Un nouvel établissement a été ajouté à votre compte'">
    <p style="font-size:15px; line-height:1.6;">Bonjour {{ $user->name }},</p>
    <p style="font-size:15px; line-height:1.6;">
        L'établissement <strong>{{ $etablissement->nom }}</strong> vient d'être ajouté à votre compte DJAART SCHOOL.
        Vous pouvez désormais y basculer depuis votre tableau de bord, avec le même identifiant et mot de passe.
    </p>
    <p style="margin:24px 0;">
        <a href="{{ $loginUrl }}" style="background-color:#1d4ed8; color:#ffffff; padding:10px 20px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:bold;">
            Se connecter
        </a>
    </p>
</x-emails.layout>
