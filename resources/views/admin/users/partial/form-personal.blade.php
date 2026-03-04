@props([
    'roles' => [],
    'errors' => [],
])
<?php
$roles = $roles
    ->mapWithKeys(function ($role) {
        return [$role->id => $role->name];
    })
    ->toArray();

?>

<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Nome Completo" placeholder="Nome completo" name="name"
            required="false" description="Informe o nome completo" />
    </div>

    <div class="w-1/2">
        <x-form.input type="text" class="mb-4" label="Telefone" placeholder="Telefone" name="telefone"
            required="false" description="Informe o telefone" />
    </div>

</div>

<div class="flex w-full gap-4">
    <div class="w-1/2">
        <x-form.input required="false" class="mb-4" label="Email" name="email" type="email" placeholder="Email"
            description="Por favor, informe um email válido." />

    </div>
    <div class="w-1/2">
        <x-form-select label="Tipo de Usuário" name="roles[]" id="roles" multiple="{{ true }}"
            required="true" maxSelections="99" :options="$roles" />
    </div>
</div>
