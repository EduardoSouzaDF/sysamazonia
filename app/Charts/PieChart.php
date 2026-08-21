<?php

namespace App\Charts;

use ArielMejiaDev\LarapexCharts\LarapexChart;


class PieChart 
{
    private $chart ;
    public function build(String $title, String $subtitle, array $data = [], $type = 'pie')
    {
        $this->chart =(new LarapexChart);
        $this->setType($type);
        $this->chart->setTitle($title)
        ->setSubtitle($subtitle);
        $this->setData($data);
        return $this->chart;
            
    }

    private function setType(String $type){
        switch ($type) {
            case 'pie':
                $this->chart = $this->chart->pieChart();
                break;
            case 'donut':
                $this->chart = $this->chart->donutChart();
                break;
            case 'polarArea':
                $this->chart = $this->chart->polarAreaChart();
                break;
            case 'radialChart':
                $this->chart = $this->chart->radialChart();
                break;
            default:
              $this->chart = $this->chart->pieChart();
        }
    }

    private function setData($data){
        $this->chart->addData(collect($data)->pluck('total')->toArray());
        $this->chart->setLabels(collect($data)->pluck('title')->toArray());
    }
}
