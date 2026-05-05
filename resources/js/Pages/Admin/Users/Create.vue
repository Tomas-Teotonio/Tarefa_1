<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm } from '@inertiajs/vue3'

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'citizen'
})

const submit = () => {
    form.post(route('admin.users.store'))
}
</script>

<template>
    <AppLayout title="Criar Utilizador">

        <div class="max-w-xl mx-auto bg-base-100 p-6 rounded-2xl shadow">

            <h1 class="text-xl font-bold mb-4">Novo Utilizador</h1>

            <form @submit.prevent="submit" class="space-y-4">

                <input v-model="form.name" placeholder="Nome" class="input input-bordered w-full" />

                <input v-model="form.email" placeholder="Email" class="input input-bordered w-full" />

                <input v-model="form.password" type="password" placeholder="Password" class="input input-bordered w-full" />

                <select v-model="form.role" class="select select-bordered w-full">
                    <option value="citizen">Citizen</option>
                    <option value="admin">Admin</option>
                </select>

                <button class="btn btn-primary w-full" :disabled="form.processing">
                    Criar
                </button>

            </form>

        </div>

    </AppLayout>
</template>