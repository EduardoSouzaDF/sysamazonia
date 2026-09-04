
@extends('admin.content')

<!-- @vite([
    'resources/comp_themes/apexcharts/apexcharts.min.js',
    'resources/comp_themes/apexcharts/apexcharts.css',
     ]) -->
@section('maincontent')
     <div class="flex flex-row w-full gap-2 px-4 justify-center " style="max-height: 200px;">

               <div class="  p-6 m-20 bg-white min-h-max">
                    {!! $chartEditions->container() !!}
               </div>
               <div class="  p-6 m-20 bg-white min-h-max">
                    {!! $chartModalities->container() !!}
               </div>
 

     </div>

     <div class="flex flex-row w-full gap-2 px-4 mt-4 justify-center" style="max-height: 200px;">

               <div class="  p-6 m-20 bg-white min-h-max">
                    {!! $chartStateUser->container() !!}
               </div>
 
               <div class="  p-6 m-20 bg-white min-h-max">
                    {!! $chartRegistrationBySex->container() !!}
               </div>

     </div>

      

     
@endsection

@push('scripts')
<script src="{{ $chartEditions->cdn() }}"></script>
<script src="{{ $chartModalities->cdn() }}"></script>
<script src="{{ $chartStateUser->cdn() }}"></script>
<script src="{{ $chartRegistrationBySex->cdn() }}"></script>
{{ $chartEditions->script() }}
{{ $chartModalities->script() }}
{{ $chartStateUser->script() }}
{{ $chartRegistrationBySex->script() }}



@endpush