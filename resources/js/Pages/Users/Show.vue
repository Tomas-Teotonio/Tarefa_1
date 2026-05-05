<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
    user: Object
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('pt-PT')
}
</script>

<template>
    <AppLayout :title="user.name">

        <!-- 👤 INFO USER -->
        <div class="bg-base-100 p-6 rounded-2xl shadow mb-6">

            <h1 class="text-2xl font-bold mb-2">
                {{ user.name }}
            </h1>

            <p><b>Email:</b> {{ user.email }}</p>

            <p>
                <b>Role:</b>
                <span
                    class="badge ml-2"
                    :class="user.role === 'admin'
                        ? 'badge-primary'
                        : 'badge-secondary'"
                >
                    {{ user.role }}
                </span>
            </p>

        </div>

        <!-- 📚 HISTÓRICO -->
        <div class="bg-base-100 p-6 rounded-2xl shadow">

            <h2 class="text-xl font-bold mb-4">
                Histórico de Requisições
            </h2>

            <div class="overflow-x-auto">
                <table class="table w-full">

                    <thead>
                        <tr>
                            <th>Nº</th>
                            <th>Livro</th>
                            <th>Data</th>
                            <th>Entrega</th>
                            <th>Devolução</th>
                            <th>Dias</th>
                            <th>Estado</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr
                            v-for="req in user.requests"
                            :key="req.id"
                        >
                            <td>{{ req.number }}</td>

                            <td>
                                {{ req.book?.name }}
                            </td>

                            <td>{{ formatDate(req.request_date) }}</td>

                            <td>{{ formatDate(req.expected_return_date) }}</td>

                            <td>{{ formatDate(req.actual_return_date) }}</td>

                            <td>
                                <span v-if="req.days_used !== null">
                                    {{ Math.floor(req.days_used) }} dias
                                </span>
                                <span v-else>-</span>
                            </td>

                            <td>
                                <span
                                    class="badge"
                                    :class="req.status === 'active'
                                        ? 'badge-warning'
                                        : 'badge-success'"
                                >
                                    {{ req.status === 'active'
                                        ? 'Ativo'
                                        : 'Devolvido' }}
                                </span>
                            </td>
                        </tr>

                        <tr v-if="!user.requests.length">
                            <td colspan="7" class="text-center opacity-60">
                                Sem requisições
                            </td>
                        </tr>

                    </tbody>

                </table>
            </div>

        </div>

    </AppLayout>
</template>