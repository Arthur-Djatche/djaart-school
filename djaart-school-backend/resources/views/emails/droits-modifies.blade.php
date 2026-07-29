<x-emails.layout :subject="'Vos droits d\'accès ont été mis à jour'">
    <p style="font-size:15px; line-height:1.6;">Bonjour {{ $user->name }},</p>
    <p style="font-size:15px; line-height:1.6;">
        Vos droits d'accès supplémentaires sur DJAART SCHOOL viennent d'être mis à jour par votre administrateur.
    </p>
    @if (count($labels) > 0)
        <p style="font-size:15px; line-height:1.6;">Vous avez désormais accès, en plus de votre rôle habituel, à :</p>
        <ul style="font-size:14px; line-height:1.8; padding-left:20px;">
            @foreach ($labels as $label)
                <li>{{ $label }}</li>
            @endforeach
        </ul>
    @else
        <p style="font-size:15px; line-height:1.6;">
            Vous n'avez plus aucun droit d'accès supplémentaire (au-delà de ceux de votre rôle habituel).
        </p>
    @endif
    <p style="margin:24px 0;">
        <a href="{{ $loginUrl }}" style="background-color:#1d4ed8; color:#ffffff; padding:10px 20px; border-radius:8px; text-decoration:none; font-size:14px; font-weight:bold;">
            Se connecter
        </a>
    </p>
</x-emails.layout>
