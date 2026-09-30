@extends('admin.content')

@section('maincontent')
<div class="kt-container-fixed py-8">
    <section class="kt-card welcome-card" aria-labelledby="welcome-title">
        <img class="welcome-logo" src="{{ asset('images/lg_Premios_alt.webp') }}" alt="Premiação Amazônia" />
        <p class="welcome-greeting">Olá, {{ auth()->user()->name }}!</p>
        <h1 id="welcome-title">Boas-vindas ao Sistema de Inscrições, Avaliação e Julgamento do Prêmios.</h1>
        <p class="welcome-description">
            Um espaço para reconhecer iniciativas e pessoas que contribuem para o desenvolvimento da Amazônia.
            Sua participação faz parte dessa história.
        </p>
        <p class="welcome-guidance">Utilize o menu para acessar as funcionalidades disponíveis para o seu perfil.</p>
    </section>
</div>
@endsection

@push('styles')
<style>
    .welcome-card {
        max-width: 960px;
        margin: clamp(1rem, 5vh, 4rem) auto;
        padding: clamp(1.5rem, 5vw, 4.5rem);
        text-align: center;
        align-items: center;
        border-top: 4px solid #28734a;
    }
    .welcome-logo { display: block; width: min(100%, 320px); height: auto; margin: 0 auto 2rem; }
    .welcome-greeting { color: #28734a; font-weight: 600; margin-bottom: .75rem; overflow-wrap: anywhere; }
    .welcome-card h1 { font-size: clamp(1.5rem, 3vw, 2.25rem); font-weight: 700; line-height: 1.3; }
    .welcome-description { max-width: 620px; margin: 1.5rem auto; line-height: 1.8; }
    .welcome-guidance { color: var(--secondary-foreground); line-height: 1.6; }
</style>
@endpush
