<template>
    <div class="h-screen flex flex-col items-center justify-center bg-gray-100">
        <h1 class="text-2xl font-bold mb-4">Portal Login</h1>
        <button
            class="bg-blue-600 text-white px-4 py-2 rounded"
            @click="$router.push('/dashboard')"
        >
            Mock Login →
        </button>
    </div>
</template>
