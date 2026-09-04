<?php

namespace App\Http\Controllers;

use App\Charts\PieChart;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {

        $dataEditions = $this->getDataRegistrationsByEditions();
        $dataModalities = $this->getDataRegistrationsByModalities();
        $dataStateUser = $this->getDataRegistrationsByEstateUser();
        $dataStateUserBySex = $this->getDataStateUserBySex();

        $chart = new PieChart;

        return view('dashboard', [
            'user' => Auth::user(),
            'chartEditions' => $chart->build('Registros por Edição', '', $dataEditions),
            'chartModalities' => $chart->build('Registros por Modalidade', '', $dataModalities, 'polarArea'),
            'chartStateUser' => $chart->build('Registros por Estado', '', $dataStateUser, 'donut'),
            'chartRegistrationBySex' => $chart->build('Registros por Sexo', '', $dataStateUserBySex),
        ]);
    }

    public function getDataRegistrationsByEditions()
    {

        $data = DB::select('SELECT
                                e.title AS title,
                                COALESCE(r.registrations_count, 0) + COALESCE(n.nominees_count, 0) AS total
                                FROM editions e
                                LEFT JOIN (
                                SELECT
                                    m.edition_id,
                                    COUNT(*) AS registrations_count
                                FROM registrations reg
                                JOIN categories cat ON cat.id = reg.category_id
                                JOIN modalities m ON m.id = cat.modality_id
                                GROUP BY m.edition_id
                                ) r ON r.edition_id = e.id
                                LEFT JOIN (
                                SELECT
                                    m.edition_id,
                                    COUNT(*) AS nominees_count
                                FROM nominees nom
                                JOIN categories cat ON cat.id = nom.category_id
                                JOIN modalities m ON m.id = cat.modality_id
                                GROUP BY m.edition_id
                                ) n ON n.edition_id = e.id
                                ORDER BY e.title;');

        return $data;
    }

    private function getDataRegistrationsByModalities()
    {

        $data = DB::select('    SELECT
                                m.title AS title,
                                COALESCE(r.registrations_count, 0) + COALESCE(n.nominees_count, 0) AS total
                                FROM modalities m
                                LEFT JOIN (
                                SELECT
                                    cat.modality_id,
                                    COUNT(*) AS registrations_count
                                FROM registrations reg
                                JOIN categories cat ON cat.id = reg.category_id
                                GROUP BY cat.modality_id
                                ) r ON r.modality_id = m.id
                                LEFT JOIN (
                                SELECT
                                    cat.modality_id,
                                    COUNT(*) AS nominees_count
                                FROM nominees nom
                                JOIN categories cat ON cat.id = nom.category_id
                                GROUP BY cat.modality_id
                                ) n ON n.modality_id = m.id
                                ORDER BY m.title;
                            ');

        return $data;
    }

    private function getDataRegistrationsByEstateUser()
    {

        $data = DB::select('SELECT 
                        candidates.rg_uf as title,
                        count(candidates.rg_uf) as total
                        from candidates
                        group by candidates.rg_uf');

        return $data;

    }

    private function getDataStateUserBySex()
    {

        $data = DB::select('SELECT 
                        candidates.sexo as title,
                        count(candidates.sexo) as total
                        from candidates


                        group by candidates.sexo;');

        return $data;
    }
}
