<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CandidateRequest;
use App\Http\Requests\RegistrationRequest;
use App\Models\Candidate;
use App\Models\Edition;
use App\Models\Registration;
use App\Models\RegistrationFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RegistrationProtocol;
class EditionController extends Controller
{
    public function registration(Request $request)
    {
        try {
            $data = $this->checkJsonDecode();
            $candidate = $this->candidate($data);
            $data['edition'] = Crypt::decryptString($data['edition']);
            $data['candidate_id'] = Crypt::decryptString($candidate['id']);
            $data['category_id'] = Crypt::decryptString($data['category_id']);

            $request->merge(['data' => json_encode($data)]);
            $value1 = $this->checkRulesforRegistration($candidate);
            $registration = $this->registrationSave($candidate);
            if (isset($_FILES['files']) && ! empty($_FILES['files'])) {
                if ($request->hasFile('files')) {
                    foreach ($request->file('files') as $file) {
                        try {
                            // Verifica se o arquivo é válido
                            if (! $file->isValid()) {
                                throw new \InvalidArgumentException( $file->getError());
                            }

                            // Valida tamanho do arquivo
                            if ($file->getSize() > 5 * 1024 * 1024) {
                                throw new \InvalidArgumentException('Arquivo muito grande. Máximo permitido: 5MB.');
                            }

                            // Bloqueia extensões perigosas
                            $blockedExtensions = ['php', 'exe', 'bat', 'sh', 'pl', 'py', 'jsp', 'asp', 'aspx', 'js'];
                            $extension = strtolower($file->getClientOriginalExtension());

                            if (in_array($extension, $blockedExtensions)) {
                                throw new \InvalidArgumentException("Tipo de arquivo não permitido: .{$extension}");
                            }

                            // Salva o arquivo
                            $path = $file->store('registrations/files/'.Crypt::decryptString($registration['id']).'/', 'private');

                            // Registra no banco
                            $registrationFile = new RegistrationFile;
                            $registrationFile->registration_id = Crypt::decryptString($registration['id']);
                            $registrationFile->file_name = $file->getClientOriginalName();
                            $registrationFile->file_path = $path;
                            $registrationFile->file_type = $file->getMimeType();
                            $registrationFile->file_size = $file->getSize();
                            $registrationFile->document_type = 'anexo';
                            $registrationFile->save();

                        } catch (\InvalidArgumentException $e) {
                            return response()->json([
                                'status' => 'error',
                                'message' => $e->getMessage(),
                            ], 400)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
                        }
                    }
                }
            }

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400)->setEncodingOptions(JSON_UNESCAPED_UNICODE);
        }

        return response()->json([
            'status' => 'success',
            'data' => ['candidate' => $candidate, 'registration' => $registration],
        ], 200)->setEncodingOptions(JSON_UNESCAPED_UNICODE);

    }

    public function registrationSave($candidate)
    {
        $registrationRequest = app(RegistrationRequest::class);
        $registration = new Registration($registrationRequest->validated());
        $registration->status = 1;

        $registration->protocol = Carbon::now()->year.'-'.$registration->candidate_id.'-'.$registration->category_id.'-'.$candidate['cpf'];
        $registration->save();
        $email = $candidate['email'];
        $protocolToken = $registration->protocol;

        Notification::route('mail', $email)->notify(new RegistrationProtocol($protocolToken,$candidate['nome']));

        $response = $registration->toArray();
        $response['id'] = $this->encrypt($response['id']);
        $response['candidate_id'] = $this->encrypt($response['candidate_id']);
        $response['category_id'] = $this->encrypt($response['category_id']);
        $response['candidate_id'] = $this->encrypt($response['candidate_id']);
        unset($response['updated_at']);
        unset($response['created_at']);

        return $response;
    }

    public function checkRulesforRegistration(array $candidate)
    {
        $jsonData = $this->checkJsonDecode();
        $registersEditionCount = Registration::where('candidate_id', $this->decript($candidate['id']))
            ->whereHas('category.modality.edition', function ($query) use ($jsonData) {
                $query->where('id', $jsonData['edition']);
            })->get()->count();

        $maxByEdition = Edition::find($jsonData['edition'])->applications_per_candidate;

        if ($registersEditionCount >= $maxByEdition) {
            $this->throwError('Atingiu máximo de inscrição por Edição :'.$maxByEdition);
        }

    }

    public function candidate(array $data)
    {
        $candidate = Candidate::where('cpf', $data['cpf'])->first();
        $response = [];
        if (! $candidate) {
            $candidateRequest = app(CandidateRequest::class);
            $candidate = new Candidate($candidateRequest->validated());
            $candidate->save();
            $response = $candidate->toArray();
        } else {
            $a = $candidate->get();
            $response = $a[0]->toArray();

        }

        $response['id'] = $this->encrypt($response['id']);
        unset($response['created_at']);
        unset($response['updated_at']);

        return $response;
    }

    private function throwError(string $error)
    {
        throw new \InvalidArgumentException($error);
    }

    private function checkJsonDecode()
    {
        $data = json_decode(request()->input('data'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->throwError('JSON inválido no campo data.');
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

    public function findByCpf(string $cpf)
    {
        $candidate = Candidate::where('cpf', $cpf)->first();
        if ($candidate) {
            $reponse = $candidate->toArray();
            $reponse['id'] = $this->encrypt($reponse['id']);

            return response()->json([
                'status' => 'success',
                'candidate' => $reponse,
            ]);
        }

        return response()->json([
            'status' => 'error',
        ], 404);
    }

    public function getEdition()
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

    private function decript(string $value)
    {
        return Crypt::decryptString($value);
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
