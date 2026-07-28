<?php

namespace App\Support;

/**
 * Catalogue des droits qu'un admin_etablissement peut accorder
 * individuellement a un acteur de son etablissement, en complement de son
 * role — chaque cle correspond a un ecran/menu precis (routes/api.php et
 * navigation.js cote frontend). "Comptes utilisateurs" et "Demandes de demo"
 * sont volontairement absents : jamais delegables (risque d'auto-elevation
 * de privileges pour le premier, reserve super_admin pour le second).
 */
class GrantablePermissions
{
    public const CATALOGUE = [
        'acces.inscriptions' => 'Inscriptions',
        'acces.apprenants' => 'Apprenants',
        'acces.seance_photo' => 'Séance photo',
        'acces.documents_masse' => 'Documents en masse',
        'acces.frais_scolarite' => 'Finance — Frais de scolarité',
        'acces.caisse' => 'Finance — Caisse',
        'acces.affectations' => 'Pédagogie — Affectations',
        'acces.sequences' => 'Pédagogie — Séquences',
        'acces.semestres' => 'Pédagogie — Semestres',
        'acces.notes' => 'Pédagogie — Saisie des notes',
        'acces.conduite' => 'Pédagogie — Saisie de la conduite',
        'acces.bulletins' => 'Pédagogie — Bulletins',
        'acces.releves' => 'Pédagogie — Relevés de notes',
        'acces.parametrage_etablissement' => 'Paramétrage — Établissement(s)',
        'acces.parametrage_academique' => 'Paramétrage — Années, départements, filières, classes, matières, UE',
    ];

    public static function cles(): array
    {
        return array_keys(self::CATALOGUE);
    }
}
