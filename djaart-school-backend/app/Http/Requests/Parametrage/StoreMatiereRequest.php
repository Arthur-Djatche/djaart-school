<?php

namespace App\Http\Requests\Parametrage;

use App\Models\Matiere;
use App\Models\Niveau;
use App\Models\Semestre;
use App\Models\UniteEnseignement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMatiereRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Matiere::class);
    }

    public function rules(): array
    {
        $etablissementId = $this->user()->hasRole('super_admin')
            ? $this->input('etablissement_id')
            : $this->user()->etablissement_id;

        return [
            'niveau_id' => [
                'required',
                Rule::exists('niveaux', 'id')->where('etablissement_id', $etablissementId),
            ],
            'code' => ['nullable', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:255'],
            'groupe' => ['nullable', 'string', 'max:100'],
            'coefficient' => ['sometimes', 'numeric', 'min:0.5', 'max:20'],
            'credits_ects' => ['nullable', 'integer', 'min:1', 'max:60'],
            'ponderation_cc' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'ponderation_session_normale' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'semestre_id' => [
                'nullable',
                Rule::exists('semestres', 'id')->where('etablissement_id', $etablissementId),
            ],
            'unite_enseignement_id' => [
                'nullable',
                Rule::exists('unites_enseignement', 'id')->where('etablissement_id', $etablissementId),
            ],
            'etablissement_id' => $this->user()->hasRole('super_admin')
                ? ['required', 'exists:etablissements,id']
                : ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $cc = $this->input('ponderation_cc');
            $sn = $this->input('ponderation_session_normale');

            if ($cc !== null && $sn !== null && (int) $cc + (int) $sn !== 100) {
                $validator->errors()->add(
                    'ponderation_session_normale',
                    'La pondération CC + Session Normale doit être égale à 100.',
                );
            }

            $niveau = Niveau::find($this->input('niveau_id'));
            $semestreId = $this->input('semestre_id');
            $uniteEnseignementId = $this->input('unite_enseignement_id');

            if (! $niveau) {
                return;
            }

            if ($niveau->type_systeme === 'lmd') {
                if (! $semestreId) {
                    $validator->errors()->add('semestre_id', 'Le semestre est obligatoire pour un niveau LMD.');
                }
                if (! $uniteEnseignementId) {
                    $validator->errors()->add('unite_enseignement_id', "L'unité d'enseignement est obligatoire pour un niveau LMD.");
                }
                if ($semestreId && $uniteEnseignementId) {
                    $uniteAppartientAuSemestre = UniteEnseignement::where('id', $uniteEnseignementId)
                        ->where('semestre_id', $semestreId)
                        ->exists();
                    if (! $uniteAppartientAuSemestre) {
                        $validator->errors()->add('unite_enseignement_id', "L'unité d'enseignement sélectionnée n'appartient pas au semestre choisi.");
                    }
                }
                if ($semestreId) {
                    $semestreAppartientAuNiveau = Semestre::where('id', $semestreId)
                        ->where('niveau_id', $niveau->id)
                        ->exists();
                    if (! $semestreAppartientAuNiveau) {
                        $validator->errors()->add('semestre_id', "Le semestre sélectionné n'appartient pas au niveau choisi.");
                    }
                }
            } else {
                if ($semestreId || $uniteEnseignementId) {
                    $validator->errors()->add('semestre_id', "Le semestre et l'unité d'enseignement ne s'appliquent qu'aux niveaux LMD.");
                }
            }
        });
    }
}
