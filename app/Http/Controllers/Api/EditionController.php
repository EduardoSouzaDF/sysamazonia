<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CandidateRequest;
use App\Models\Candidate;
use App\Models\Edition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class EditionController extends Controller
{
    public function registration(Request $request)
    {
        $data = $this->checkJsonDecode();
        $candidate = $this->candidate($data);

        return response()->json([
            'status' => 'success',
            'data' => ['candidate'=>$candidate],
        ], 200);

        // $this->checkJsonDecode();

    }

    public function candidate(Array $data){
        $candidate = Candidate::where('cpf',$data['cpf'])->first()->get();
        if(!$candidate){
            $candidateRequest = app(CandidateRequest::class);
            $candidate = new Candidate($candidateRequest->validated());
            // $candidate->save();
        }

        return $candidate;
    }






    private function checkJsonDecode(){
        $data = json_decode(request()->input('data'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'status' => 'error',
                'message' => 'JSON inválido no campo data.',
            ], 400);
        }
        return $data;
    }

    public function hasRegistrations()
    {
        // Busca edições onde is_registration_active é true
        $edition = $this->getEdition();
        if ($edition) {
            $edition = $this->unsetPreventData($edition);
            $edition['regulation_file_path'] = url(Storage::url($edition['regulation_file_path']));

            return response()->json([
                'status' => 'success',
                'data' => $edition,
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Nenhuma edição com registro ativo encontrada',
        ], 404);
    }

    public function findByCpf(string $cpf){
        $candidate = Candidate::where('cpf',$cpf)->first();
        if($candidate){
            $reponse = $candidate->toArray();
            $reponse['id'] = $this->encrypt($reponse['id']);
            return response()->json([
                'status' => 'success',
                'candidate' => $reponse,
            ]);
        }

         return response()->json([
                'status' => 'error',
            ],404);
    }

    private function getEdition()
    {
        $edition = Edition::where('is_registration_active', true)
            ->where('registration_start', '<=', now())
            ->where('registration_end', '>=', now())
            ->with([
                'modalities' => function ($query) {
                    $query->where('is_active', true)
                        ->with([
                            'categories' => function ($q) {
                                $q->where('is_open_for_submissions', true);
                            },
                        ]);
                },
            ])
            ->orderBy('id', 'desc')
            ->first();

        if ($edition) {
            return $edition->toArray();
        }

        return false;
    }

    private function encrypt(string $value)
    {
        return Crypt::encryptString($value);

    }

    private function unsetPreventData($edition)
    {
        $edition['id'] = $this->encrypt($edition['id']);
        unset($edition['applications_per_candidate']);
        unset($edition['created_at']);
        unset($edition['updated_at']);
        // Remove os IDs das modalities
        if (isset($edition['modalities']) && is_array($edition['modalities'])) {
            foreach ($edition['modalities'] as &$modality) {
                $modality['id'] = $this->encrypt($modality['id']);
                unset($modality['candidacy_limit_per_modality']);
                unset($modality['is_active']);
                unset($modality['edition_id']);
                unset($modality['created_at']);
                unset($modality['updated_at']);
                unset($modality['updated_at']);

                // Remove os IDs das categories
                if (isset($modality['categories']) && is_array($modality['categories'])) {
                    foreach ($modality['categories'] as &$category) {
                        $category['id'] = $this->encrypt($category['id']);
                        unset($category['modality_id']);
                        unset($category['created_at']);
                        unset($category['updated_at']);
                        unset($category['nominations_count']);
                        unset($category['evaluations_count']);
                        unset($category['recipients_count']);
                        unset($category['submissions_per_candidate']);
                        unset($category['judging_start']);
                        unset($category['judging_end']);
                        unset($category['is_open_for_submissions']);
                    }
                }
            }
        }

        return $edition;
    }
}
