@extends('layout.app')

@push('styles')
   <style>
        .page-bg {
            background-image: none;
        }
    </style>
@endpush
@vite([
    'resources/comp_themes/apexcharts/apexcharts.css',
    'resources/comp_themes/keenicons/styles.bundle.css',
    'resources/css/styles.css',
     ])
@section('body_class', 'antialiased flex h-full text-base text-foreground bg-background demo1 kt-sidebar-fixed kt-header-fixed')
@section('content')
<div class="flex grow">
    @include('admin.sidebar')
   <div class="kt-wrapper flex grow flex-col">
    <header class="kt-header fixed top-0 z-10 start-0 end-0 flex items-stretch shrink-0 bg-background" data-kt-sticky="true" data-kt-sticky-class="border-b border-border" data-kt-sticky-name="header" id="header">
     <div class="kt-container-fixed flex justify-between items-stretch lg:gap-4" id="headerContainer">
     @include('admin.menu.mobile-logo')
      <div class="flex items-stretch" id="megaMenuContainer">
      </div>
      <div class="flex items-center gap-2.5">

      @include('admin.menu.chat-menu')
      @include('admin.menu.user')


      </div>
      <!-- End of Topbar -->
     </div>
     <!-- End of Container -->
    </header>
    <!-- End of Header -->
    <!-- Content -->
    <main class="grow pt-1" id="content" role="content">
    @yield('maincontent')

    </main>
    <!-- End of Content -->
    <!-- Footer -->
   @include('admin.footer')
    <!-- End of Footer -->
   </div>
   <!-- End of Wrapper -->
  </div>
  <!-- End of Main -->


@endsection

