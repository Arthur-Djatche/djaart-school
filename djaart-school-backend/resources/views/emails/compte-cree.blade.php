<x-emails.layout :subject="'Votre compte DJAART SCHOOL a été créé'">
    <p style="font-size:15px; line-height:1.6;">Bonjour {{ $user->name }},</p>
    <p style="font-size:15px; line-height:1.6;">
        Un compte vient d'être créé pour vous sur DJAART SCHOOL{{ $etablissementNom ? " pour l'établissement « {$etablissementNom} »" : '' }},
        avec le rôle <strong>{{ $roleLabel }}</strong>.
    </p>
    <p style="font-size:15px; line-height:1.6;">
        Identifiant de connexion : <strong>{{ $user->email }}</strong><br>
        Votre mot de passe vous a été communiqué directement par votre administrateur.
    </p>
    <p style="margin:24px 0;">
        <a href="{{ $loginUrl }}" style="background-color:#1d4ed8; color:#ffffff; padding:10px 20px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:bold;">
            Se connecter
        </a>
    </p>
</x-emails.layout>
