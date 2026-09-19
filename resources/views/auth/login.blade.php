@extends('layout.app')
@section('title', ' - Autenticação')

@push('styles')
    <style>
        .page-bg {
            background-image: url('{{ asset('images/bg-10.png') }}');

        }
    </style>
@endpush
@section('body_class', 'antialiased flex h-full text-base text-foreground bg-background ')

@section('content')

    <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
        <div class="kt-card max-w-[370px] w-full">
            <form action="{{ route('login') }}" class="kt-card-content flex flex-col gap-5 p-10" method="post">
                @csrf
                <div class="text-center mb-2.5">
                    <img alt="image" class="dark:hidden max-h-[130px]" src="{{ asset('images/logo.png') }}" />
                </div>

                <div class="flex flex-col gap-1">
                    <label class="kt-form-label font-normal text-mono">
                        Email
                    </label>


                    <input class="kt-input " aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" name="email"
                        placeholder="Informe seu email" type="text" value="{{ old('email') }}" />
                </div>
                <x-messages.error :iterator="'email'" />
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between gap-1">
                        <label class="kt-form-label font-normal text-mono">
                            Senha
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm kt-link shrink-0">
                            Esqueceu a senha ?
                        </a>
                    </div>
                    <div class="kt-input" data-kt-toggle-password="true">
                        <input name="password" placeholder="Senha" type="password" value="" />
                        <button class="kt-btn kt-btn-sm kt-btn-ghost kt-btn-icon bg-transparent! -me-1.5"
                            data-kt-toggle-password-trigger="true" type="button">
                            <span class="kt-toggle-password-active:hidden">
                                <i class="ki-filled ki-eye text-muted-foreground">
                                </i>
                            </span>
                            <span class="hidden kt-toggle-password-active:block">
                                <i class="ki-filled ki-eye-slash text-muted-foreground">
                                </i>
                            </span>
                        </button>


                    </div>
                    <x-messages.error :iterator="'password'" />
                </div>
                <button class="kt-btn kt-btn-primary flex justify-center grow">
                    Entrar
                </button>
                <x-messages.error :iterator="'login'" />

                <x-messages.error :iterator="'xxx'" :message="session('status')" />

                @if (session('error'))
                    <div class="kt-alert" role="alert" aria-labelledby="alert_heading" aria-describedby="alert_message"
                        id="alert">
                        <div class="kt-alert-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-triangle-alert size-6 text-destructive"
                                aria-hidden="true">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"></path>
                                <path d="M12 9v4"></path>
                                <path d="M12 17h.01"></path>
                            </svg>
                        </div>
                        <div class="kt-alert-title flex items-center gap-1.5" id="alert_heading">
                            <span class="font-semibold">{{ session('error') }}</span>
                        </div>
                        <button class="kt-alert-close" data-kt-dismiss="#alert" aria-label="Close alert">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="lucide lucide-x" aria-hidden="true">
                                <path d="M18 6 6 18"></path>
                                <path d="m6 6 12 12"></path>
                            </svg>
                        </button>
                    </div>
                @endif

            </form>
        </div>
    </div>
@endsection
@push('scripts')
    <script></script>
@endpush
