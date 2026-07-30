<x-emails.layout :subject="'Votre mot de passe DJAART SCHOOL a été réinitialisé'">
    <p style="font-size:15px; line-height:1.6;">Bonjour {{ $user->name }},</p>
    <p style="font-size:15px; line-height:1.6;">
        Votre mot de passe DJAART SCHOOL vient d'être réinitialisé par votre administrateur. Voici votre nouveau mot de passe provisoire :
    </p>
    <table role="presentation" style="font-size:14px; margin:16px 0;">
        <tr><td style="padding:4px 12px 4px 0; color:#64748b;">Identifiant</td><td><strong>{{ $user->email }}</strong></td></tr>
        <tr><td style="padding:4px 12px 4px 0; color:#64748b;">Nouveau mot de passe</td><td><strong>{{ $motDePasse }}</strong></td></tr>
    </table>
    <p style="font-size:15px; line-height:1.6; color:#dc2626;">
        Pour votre sécurité, un changement de mot de passe vous sera demandé dès votre prochaine connexion.
    </p>
    <p style="margin:24px 0;">
        <a href="{{ $loginUrl }}" style="background-color:#1d4ed8; color:#ffffff; padding:10px 20px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:bold;">
            Se connecter
        </a>
    </p>
</x-emails.layout>
