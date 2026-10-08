<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReprocessAiExecutionsRequest;
use App\Services\Ai\AiManualReprocessing;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class AiReprocessingController extends Controller
{
    public function store(ReprocessAiExecutionsRequest $request, AiManualReprocessing $service): RedirectResponse
    {
        $data = $request->validated();
        $created = [];
        $rejected = [];
        // Each item is independent: one stale selection does not lose successful requests.
        foreach ($data['execution_ids'] as $id) {
            try {
                $created[] = $service->request((int) $id, $request->user(), $data['configuration'], $data['reason'])->id;
            } catch (ValidationException $error) {
                $rejected[] = '#'.$id.': '.collect($error->errors())->flatten()->first();
            } catch (\Throwable $error) {
                report($error);
                $rejected[] = '#'.$id.': falha ao registrar/enfileirar; consulte o log.';
            }
        }
        $redirect = to_route('admin.ai-settings.index', ['tab' => 'executions']);
        if ($created) {
            $redirect->with('success', 'Novas execuções encaminhadas à fila: #'.implode(', #', $created).'.');
        }
        if ($rejected) {
            $redirect->with('error', implode(' | ', $rejected));
        }

        return $redirect;
    }
}
