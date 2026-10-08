<style>
    .follow-up-matrix .follow-up-sticky {
        position: sticky;
        left: 0;
        z-index: 1;
        background-color: var(--background);
        box-shadow: inset -1px 0 0 var(--border);
    }

    .follow-up-dialog {
        width: min(32rem, calc(100vw - 2rem));
        margin: auto;
        border-radius: 0.75rem;
        padding: 1.5rem;
        background-color: var(--background);
        color: var(--foreground);
        text-align: left;
    }

    .follow-up-dialog::backdrop {
        background: rgb(0 0 0 / 0.4);
    }
</style>
<script src="{{ asset('js/acompanhamento.js') }}" defer></script>
