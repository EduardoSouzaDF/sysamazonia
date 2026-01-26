@extends('layout.app')
@section('title', ' - Recuperação de senha')
@push('styles')
    <style>
        .page-bg {
            background-image: url('{{ asset('images/bg-10.png') }}');
        }
    </style>
@endpush
@section('body_class', 'antialiased flex h-full text-base text-foreground bg-background ')

@section('content')

    @if (!session('status'))
        <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
            <div class="kt-card max-w-[370px] w-full">
                <form action="{{ route('password.email') }}" method="POST" class="kt-card-content flex flex-col gap-5 p-10"
                    method="post">
                    @csrf
                    <div class="text-center">
                        <h3 class="text-lg font-medium text-mono">
                            Seu Email
                        </h3>
                        <span class="text-sm text-secondary-foreground">
                            Digite seu email para receber um link de recuperação
                        </span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="kt-form-label font-normal text-mono">
                            Email
                        </label>
                        <input class="kt-input" name="email" type="email" autocomplete="email" required
                            placeholder="email@email.com" type="text" value="{{ old('email') }}" />
                    </div>

                    <x-messages.error :iterator="'email'" />

                    <div class="flex row  w-full  justify-between gap-5">
                        <a class="kt-btn kt-btn-destructive flex   w-3/10 " href="{{ route('login') }}">
                            Voltar
                            <i class="ki-filled ki-black-left"></i>
                        </a>
                        <button type="submit" class="kt-btn kt-btn-primary flex  w-6/10 ">
                            Recuperar
                            <i class="ki-filled ki-black-right"></i>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @else
        <div class="flex items-center justify-center grow bg-center bg-no-repeat page-bg">
            <div class="kt-card max-w-[440px] w-full">
                <div action="#" class="kt-card-content p-10" id="check_email_form" method="post">
                    <div class="flex justify-center py-10">
                        <img alt="image" class="dark:hidden max-h-[130px]"
                            src="{{ asset('images/illustrations/30.svg') }}" />
                    </div>
                    <h3 class="text-lg font-medium text-mono text-center mb-3">
                        Verifique seu email
                    </h3>
                    <div class="text-sm text-center text-secondary-foreground mb-7.5">
                        Por favor clique no link enviado para o email informado e verifique sua conta. Obrigado !
                    </div>
                    <div class="flex justify-center mb-5">
                        <a class="kt-btn kt-btn-primary flex justify-center" href="{{ route('login') }}">
                            Voltar
                        </a>
                    </div>
                    <div class="flex items-center justify-center gap-1 text-2sm">
                        <span class="text-secondary-foreground">
                            Não recebeu email?
                        </span>
                        <a class="font-medium kt-link" href="{{ route('password.request') }}">
                            Reenviar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif


@endsection
@push('scripts')
    <script></script>
@endpush
