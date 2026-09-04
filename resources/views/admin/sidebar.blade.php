@php
use App\Services\MenuBuilder;
$menu = MenuBuilder::getMenuStructure();
@endphp

<div class="kt-sidebar bg-background border-e border-e-border fixed top-0 bottom-0 z-20 hidden lg:flex flex-col items-stretch shrink-0 [--kt-drawer-enable:true] lg:[--kt-drawer-enable:false]" data-kt-drawer="true" data-kt-drawer-class="kt-drawer kt-drawer-start top-0 bottom-0" id="sidebar">
  <div class="kt-sidebar-header hidden lg:flex items-center relative justify-between px-3 lg:px-6 shrink-0" id="sidebar_header">
    <img class="default-logo min-h-[22px] max-w-none" src="{{ asset('images/logo-mini.png') }}" alt="Logo" />
  </div>

  <div class="kt-sidebar-content flex grow shrink-0 py-5 pe-2" id="sidebar_content">
    <div class="kt-scrollable-y-hover grow shrink-0 flex ps-2 lg:ps-5 pe-1 lg:pe-3" data-kt-scrollable="true" data-kt-scrollable-dependencies="#sidebar_header" data-kt-scrollable-height="auto" data-kt-scrollable-offset="0px" data-kt-scrollable-wrappers="#sidebar_content" id="sidebar_scrollable">
      <!-- Sidebar Menu -->
      <div class="kt-menu flex flex-col grow gap-1" data-kt-menu="true" data-kt-menu-accordion-expand-all="false" id="sidebar_menu">
        <x-menu-item :items="$menu" />
      </div>
      <!-- End of Sidebar Menu -->
    </div>
  </div>
</div>
