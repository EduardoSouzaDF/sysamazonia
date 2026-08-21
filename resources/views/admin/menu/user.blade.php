  <!-- User -->

  <div class="shrink-0" data-kt-dropdown="true" data-kt-dropdown-offset="10px, 10px"
      data-kt-dropdown-offset-rtl="-20px, 10px" data-kt-dropdown-placement="bottom-end"
      data-kt-dropdown-placement-rtl="bottom-start" data-kt-dropdown-trigger="click">
      <div class="cursor-pointer shrink-0" data-kt-dropdown-toggle="true">
          <img alt="" class="size-9 rounded-full border-2 border-green-500 shrink-0"
              src="{{ asset('images/blank.png') }}" />
      </div>
      <div class="kt-dropdown-menu w-[250px]" data-kt-dropdown-menu="true">
          <div class="flex items-center justify-between px-2.5 py-1.5 gap-1.5">
              <div class="flex items-center gap-2">
                  <img alt="" class="size-9 shrink-0 rounded-full border-2 border-green-500"
                      src="{{ asset('images/blank.png') }}" />
                  <div class="flex flex-col gap-1.5">
                      <span class="text-sm text-foreground font-semibold leading-none">
                          {{ Auth::user()->name }}
                      </span>
                      {{ Auth::user()->email }}
                  </div>
              </div>

          </div>
          <div class="container flex flex-row justify-start gap-2">
              @foreach (Auth::user()->roles as $role)
                  <span class="kt-badge kt-badge-primary kt-badge-outline"> {{ $role->name }}</span>
              @endforeach
          </div>


          <ul class="kt-dropdown-menu-sub">
              <li>
                  <div class="kt-dropdown-menu-separator">
                  </div>
              </li>

               

              @if (session()->has('admin_user_id'))
                  <a class="kt-dropdown-menu-link" href="{{ route('admin.users.return-to-admin') }}">
                      <i class="ki-filled ki-profile-circle">
                      </i>
                      Voltar ao perfil
                  </a>
              @endif

          </ul>
          <div class="px-2.5 pt-1.5 mb-2.5 flex flex-col gap-3.5">

              <a class="kt-btn kt-btn-outline justify-center w-full"
                 href="{{ route('logout') }}">
                 Sair
              </a>
          </div>
      </div>
  </div>
  <!-- End of User -->
