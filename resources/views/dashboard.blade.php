
@extends('admin.content')

@vite([
    'resources/comp_themes/apexcharts/apexcharts.min.js',
    'resources/comp_themes/apexcharts/apexcharts.css',
     ])
@section('maincontent')

     <!-- Container -->
     <div class="kt-container-fixed" id="contentContainer">
     </div>
     <!-- End of Container -->
     <!-- Container -->
     <div class="kt-container-fixed">
      <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
       <div class="flex flex-col justify-center gap-2">
        <h1 class="text-xl font-medium leading-none text-mono">
         Dashboard
        </h1>
        <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
         Central Hub for Personal Customization
        </div>
       </div>
        
      </div>
     </div>
    
     </div>

     
@endsection

