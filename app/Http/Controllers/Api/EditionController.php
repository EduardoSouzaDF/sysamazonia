<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CandidateRequest;
use App\Http\Requests\RegistrationRequest;
use App\Models\ActionToken;
use App\Models\Candidate;
use App\Models\Category;
use App\Models\Edition;
use App\Models\Nominee;
use App\Models\Registration;
use App\Models\RegistrationFile;
use App\Notifications\RegistrationProtocol;
use App\Notifications\RequestProtocol;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;


class EditionController extends Controller
{
    public function registration(Request $request)
    {

        try {

            $data = $this->checkJsonDecode();

            $candidateRequest = app(CandidateRequest::class);

            $registrationRequest = app(RegistrationRequest::class);



            $candidate = $this->candidate($data);

            //valida forms

            $data['edition'] = Crypt::decryptString($data['edition']);
            $data['candidate_id'] = Crypt::decryptString($candidate['id']);
            $data['category_id'] = Crypt::decryptString($data['category_id']);

            $request->merge(['data' => json_encode($data)]);
            $value1 = $this->checkRulesforRegistration($candidate);
            $registration = $this->registrationSave($candidate);
            $this->saveFiles($registration,$request);


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

    public function saveFiles($registration, Request $request){
        if (isset($_FILES['files']) && ! empty($_FILES['files'])) {
            if ($request->hasFile('files')) {
               foreach (Arr::wrap($request->file('files')) as $file) {

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
                        if(isset($registration['name'])){
                            $registrationFile->nominee_id = Crypt::decryptString($registration['id']);
                        }else{
                            $registrationFile->registration_id = Crypt::decryptString($registration['id']);
                        }

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
    }

    public function registrationSave($candidate)
    {
        $registrationRequest = app(RegistrationRequest::class);


        $categoryId = $this->decript(request()->input('category'));

        $category = Category::with(['modality.edition'])->find($categoryId);

        if($category && !$category->is_honorific){
             $registration = new Registration($registrationRequest->validated());
        }else{
             $registration = new Nominee($registrationRequest->validated());
        }

        $registration->status = 1;
        $registration->candidate_id = $this->decript($candidate['id']);
        $registration->category_id = $categoryId;
        $registration->save();
        $registration->protocol = Carbon::now()->year.'-'.$registration->candidate_id.'-'.$registration->category_id.'-'.$registration->id.'-'.$candidate['cpf'];
        $registration->save();
        $email = $candidate['email'];
        $protocolToken = $registration->protocol;

         Notification::route('mail', $email)->notify(new RegistrationProtocol($protocolToken,$candidate['nome']));

        $response = $registration->toArray();
        $response['id'] = $this->encrypt($response['id']);
        $response['candidate_id'] = $this->encrypt($response['candidate_id']);
        $response['category_id'] = $this->encrypt($response['category_id']);
        $response['candidate_id'] = $this->encrypt($response['candidate_id']);
        $response['category'] = $category->toArray();
        unset($response['category']['id']);
        unset($response['category']['updated_at']);
        unset($response['updated_at']);
        unset($response['category']['modality_id']);
        unset($response['category']['modality']['edition_id']);
        unset($response['category']['modality']['edition']['id']);

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
            $candidateRequest = app(CandidateRequest::class);
            $candidate->fill($candidateRequest->validated());
            $candidate->save();
            $response = $candidate->toArray();

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
            $edition['regulation_file_path'] = config('app.url').'/'.$edition['regulation_file_path'];

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

            $candidatures = [];
            $registrations = Registration::where('candidate_id',$candidate['id'])->get();
            $nominees = Nominee::where('candidate_id',$candidate['id'])->get();

            if($registrations->count()){
               $dados = $registrations->toArray();

                $dados = array_map(function ($item) {
                    $item['id'] = $this->encrypt($item['id']);
                    return $item;
                }, $dados);
                $candidatures = array_merge($candidatures, $dados);
            }
            if($nominees->count()){
                $dados = $nominees->toArray();

                $dados = array_map(function ($item) {
                    $item['id'] = $this->encrypt($item['id']);
                    return $item;
                }, $dados);
                $candidatures = array_merge($candidatures, $dados);
            }
            return response()->json([
                'status' => 'success',
                'candidate' => $reponse,
                'candidatures' => $candidatures
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

    public function requestTokenAction( $protocol,$actionType){
        $token = bin2hex(random_bytes(32));
        $expiresAt =  now()->addHours(2);

        try {
            $model = $this->getRegistrationModelByPrtocol($protocol);
            if($model){
                $candidate = Candidate::findOrFail($model->candidate_id);
                $newToken =    ActionToken::create([
                                'token' => $token,
                                'action' => $actionType,
                                'protocol' => $protocol,
                                'expires_at' => $expiresAt
                                ]);

                Notification::route('mail', $candidate->email)->notify(new RequestProtocol($newToken->token,$candidate->nome,$model->protocol,$newToken->expires_at));
                return response()->json(['message' => 'Verifique seu e-mail para confirmar a ação.'])->setEncodingOptions(JSON_UNESCAPED_UNICODE);
            }else{
                return response()->json(['status' => 'error',], 404);
            }

        } catch (\Throwable $th) {
                return response()->json(['status' => $th->getMessage(),], 204);
        }

    }


    private function getRegistrationModelByPrtocol($protocol){
        $model = Registration::where('protocol',$protocol)->get()->first() ?? Nominee::where('protocol',$protocol)->get()->first();
        return $model;
    }

    public function consumeTokenPost($token,Request $request){

    try {
         $action = ActionToken::where('token',$token)->first();
        if($action->isValid()){

            $model = $this->getRegistrationModelByPrtocol($action->protocol);
            $data = $this->checkJsonDecode();
            $data['candidate_id'] = $model->candidate_id;
            $data['category_id'] = $model->category_id;
            $request->merge(['data' => json_encode($data)]);
            $registrationRequest = app(RegistrationRequest::class);
            $model->fill($data);
            $model->save();

            $this->deleteRegistrationFiles($model);
            $dataSaveFiles = $model->toArray();
            $dataSaveFiles['id'] = $this->encrypt($dataSaveFiles['id']);
            $this->saveFiles($dataSaveFiles, $request);

             $action->activate();
             $action->consume();
            return response()->json(['status' => 'success','message' => 'Registro Alterado.'], 200);
        }
    } catch (\Throwable $th) {
         return response()->json(['status' => 'error','message' => $th->getMessage()], 204);
    }

    }

    public function consumeToken($token){
        try {
            $action = ActionToken::where('token',$token)->first();
            if($action->isValid()){
                if($action->action == 'delete'){
                    $action->activate();
                    $action->consume();
                    $model = $this->getRegistrationModelByPrtocol($action->protocol);
                    if ($model instanceof Registration) {
                        $this->deleteRegistrationFiles($model);
                    }
                    $model->delete();
                    return response()->json(['status' => 'success','message' => 'Registro deletado.'], 200);
                }else if($action->action == "edit"){
                    $model = $this->getRegistrationModelByPrtocol($action->protocol);
                    $model->candidate_id = $this->encrypt($model->candidate_id);
                    $model->category_id = $this->encrypt($model->category_id);
                    $model->id = $this->encrypt($model->id);
                    $model->candidate_id = $this->encrypt($model->candidate_id);
                    return response()->json(['status' => 'edit','message' => 'Atualização de Inscrição', 'candidature' => $model], 200);
                }
            }else{
                return response()->json(['status' => 'error','message' => 'Token Expirado !'], 204);
            }
        } catch (\Throwable $th) {
        return response()->json(['status' => 'error','message' => $th->getMessage()], 204);
        }

    }

    private function deleteRegistrationFiles(Registration $registration)
    {
        $files = $registration->files; // Supondo que exista um relacionamento "files"

        foreach ($files as $file) {
            // Remover arquivo do storage
            if (Storage::disk('private')->exists($file->file_path)) {
                Storage::disk('private')->delete($file->file_path);
            }

            // Deletar registro do banco
            $file->delete();
        }
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
