<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SysAmazonia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="flex h-16 justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-gray-900">SysAmazonia</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">{{ $user->name }}</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit"
                            class="px-4 py-2 rounded-md border border-transparent bg-red-600 text-white text-sm font-medium hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Sair
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="mx-auto max-w-7xl py-6 sm:px-6 lg:px-8">
        @if (session('success'))
            <div class="mb-4 rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">
                            {{ session('success') }}
                        </h3>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow rounded-lg p-6">
            <h2 class="text-3xl font-bold tracking-tight text-gray-900 mb-6">
                Bem-vindo, {{ $user->name }}!
            </h2>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Card 1 -->
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">Email</dt>
                            <dd class="mt-1 text-lg font-medium text-gray-900">{{ $user->email }}</dd>
                        </div>
                    </div>
                </div>

                <!-- Card 2 -->
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">Membro desde</dt>
                            <dd class="mt-1 text-lg font-medium text-gray-900">
                                {{ $user->created_at->format('d/m/Y') }}
                            </dd>
                        </div>
                    </div>
                </div>

                <!-- Card 3 -->
                <div class="overflow-hidden rounded-lg bg-white px-4 py-5 shadow sm:px-6">
                    <div class="flex items-center">
                        <div class="flex-1">
                            <dt class="text-sm font-medium text-gray-500 truncate">Status</dt>
                            <dd class="mt-1 text-lg font-medium text-green-600">Ativo</dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações do usuário -->
            <div class="mt-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informações da Conta</h3>
                <div class="overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200">
                        <tbody class="divide-y divide-gray-200 bg-white">
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    Nome
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $user->name }}
                                </td>
                            </tr>
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    Email
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $user->email }}
                                </td>
                            </tr>
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    Criado em
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $user->created_at->format('d/m/Y H:i:s') }}
                                </td>
                            </tr>
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    Último acesso
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                    {{ $user->updated_at->format('d/m/Y H:i:s') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
