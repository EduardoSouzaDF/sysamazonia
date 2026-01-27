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
            <form action="{{ route('password.reset', $token) }}" class="kt-card-content flex flex-col gap-5 p-10"
                method="post">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">
                <div class="text-center   ">
                    <div class="text-center  ">
                        <img alt="image" class="dark:hidden max-h-[130px]" src="{{ asset('images/logo.png') }}" />
                    </div>

                </div>

                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between gap-1">
                        <label class="kt-form-label font-normal text-mono">
                            Senha
                        </label>
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


                    <div class="flex items-center justify-between gap-1">
                        <label class="kt-form-label font-normal text-mono">
                            Confirmar Senha
                        </label>
                    </div>
                    <div class="kt-input" data-kt-toggle-password="true">
                        <input name="password_confirmation" placeholder="Confirme sua senha" type="password"
                            value="" />
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
                    <x-messages.error :iterator="'email'" />

                </div>
                <button class="kt-btn kt-btn-primary flex justify-center grow">
                    Resetar
                </button>
            </form>
        </div>
    </div>
@endsection
@push('scripts')
    <script></script>
@endpush
