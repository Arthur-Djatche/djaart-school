<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Regroupe plusieurs documents deja generes (attestations, cartes scolaires)
 * dans un seul ZIP telecharge en une fois — l'interface doit favoriser la
 * generation ET le telechargement multiples plutot que document par document.
 */
trait BuildsZipArchive
{
    protected function zipperDocuments(iterable $documents, callable $nomFichier, string $nomZip): BinaryFileResponse
    {
        $cheminZip = tempnam(sys_get_temp_dir(), 'djaart').'.zip';

        $zip = new ZipArchive();
        $zip->open($cheminZip, ZipArchive::CREATE);

        foreach ($documents as $document) {
            $zip->addFromString($nomFichier($document), Storage::disk('local')->get($document->fichier_pdf));
        }

        $zip->close();

        return response()->download($cheminZip, $nomZip)->deleteFileAfterSend(true);
    }
}
