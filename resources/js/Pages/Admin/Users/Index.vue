<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    users: Array
})
</script>

<template>
    <AppLayout title="Utilizadores">

        <div class="max-w-5xl mx-auto bg-base-100 p-6 rounded-2xl shadow">

            <div class="flex justify-between mb-6">
                <h1 class="text-2xl font-bold">Utilizadores</h1>

                <a :href="route('admin.users.create')" class="btn btn-primary">
                    Criar Utilizador
                </a>
            </div>

            <table class="table w-full">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-for="u in users" :key="u.id">
                        <td>{{ u.name }}</td>
                        <td>{{ u.email }}</td>
                        <td>
                            <span
                                class="badge"
                                :class="u.role === 'admin' ? 'badge-error' : 'badge-success'"
                            >
                                {{ u.role }}
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>

        </div>

    </AppLayout>
</template>