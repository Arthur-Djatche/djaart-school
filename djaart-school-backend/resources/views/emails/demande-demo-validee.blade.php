<x-emails.layout :subject="'Votre accès de démonstration DJAART SCHOOL est prêt'">
    <p style="font-size:15px; line-height:1.6;">Bonjour {{ $user->name }},</p>
    <p style="font-size:15px; line-height:1.6;">
        Votre espace de démonstration DJAART SCHOOL pour <strong>{{ $etablissementNom }}</strong> est prêt. Voici vos identifiants de connexion :
    </p>
    <table role="presentation" style="font-size:14px; margin:16px 0;">
        <tr><td style="padding:4px 12px 4px 0; color:#64748b;">Identifiant</td><td><strong>{{ $user->email }}</strong></td></tr>
        <tr><td style="padding:4px 12px 4px 0; color:#64748b;">Mot de passe provisoire</td><td><strong>{{ $motDePasse }}</strong></td></tr>
    </table>
    <p style="font-size:15px; line-height:1.6; color:#dc2626;">
        Cet accès de démonstration est valable 48 heures, jusqu'au {{ $expireLe?->translatedFormat('d/m/Y à H:i') }}.
        Passé ce délai, vous ne pourrez plus vous connecter — contactez-nous si vous souhaitez poursuivre avec un accès complet.
    </p>
    <p style="font-size:15px; line-height:1.6; color:#dc2626;">
        Pour votre sécurité, un changement de mot de passe vous sera demandé dès votre première connexion.
    </p>
    <p style="margin:24px 0;">
        <a href="{{ $loginUrl }}" style="background-color:#1d4ed8; color:#ffffff; padding:10px 20px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:bold;">
            Se connecter
        </a>
    </p>
</x-emails.layout>
