@if (session('success'))
    <x-messages.alert :message="session('success')" type="success" />
@endif

@if (session('error'))
    <x-messages.alert :message="session('error')" type="destructive" />
@endif

@if ($errors->any())
    <x-messages.alert :message="$errors->first()" type="destructive" />
@endif
