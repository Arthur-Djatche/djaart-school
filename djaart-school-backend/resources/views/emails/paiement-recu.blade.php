<x-emails.layout :subject="'Reçu de paiement'">
    <p style="font-size:15px; line-height:1.6;">Bonjour,</p>
    <p style="font-size:15px; line-height:1.6;">
        Nous accusons réception d'un paiement de <strong>{{ number_format($paiement->montant, 0, ',', ' ') }}</strong>
        pour l'inscription de <strong>{{ $apprenant->prenom }} {{ $apprenant->nom }}</strong> (matricule {{ $apprenant->matricule }}),
        le {{ \Illuminate\Support\Carbon::parse($paiement->date_paiement)->translatedFormat('d/m/Y') }}.
    </p>
    <p style="font-size:15px; line-height:1.6;">
        Le reçu n°{{ $numeroRecu }} est joint à cet e-mail au format PDF.
    </p>
</x-emails.layout>
