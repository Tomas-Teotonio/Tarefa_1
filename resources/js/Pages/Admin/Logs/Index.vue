<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineProps({
    logs: Object,
})

const formatDate = (date) => {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('pt-PT')
}
</script>

<template>
    <AppLayout title="Logs">
        <div class="space-y-6">

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <h1 class="text-3xl font-bold">
                    Logs da aplicação
                </h1>

                <p class="mt-2 text-sm opacity-70">
                    Registo das ações realizadas pelos utilizadores.
                </p>
            </div>

            <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Hora</th>
                                <th>User</th>
                                <th>Módulo</th>
                                <th>ID Objeto</th>
                                <th>Alteração</th>
                                <th>IP</th>
                                <th>Browser</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="log in logs.data"
                                :key="log.id"
                            >
                                <td>{{ formatDate(log.date) }}</td>
                                <td>{{ log.time }}</td>

                                <td>
                                    <div v-if="log.user">
                                        <p class="font-medium">
                                            {{ log.user.name }}
                                        </p>
                                        <p class="text-xs opacity-60">
                                            {{ log.user.email }}
                                        </p>
                                    </div>

                                    <span v-else>-</span>
                                </td>

                                <td>
                                    <span class="badge badge-outline">
                                        {{ log.module }}
                                    </span>
                                </td>

                                <td>{{ log.object_id ?? '-' }}</td>

                                <td class="max-w-xs">
                                    <span class="text-sm">
                                        {{ log.alteration }}
                                    </span>
                                </td>

                                <td>{{ log.ip }}</td>

                                <td class="max-w-xs truncate">
                                    {{ log.browser }}
                                </td>
                            </tr>

                            <tr v-if="!logs.data.length">
                                <td colspan="8" class="text-center opacity-60">
                                    Ainda não existem logs.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-center" v-if="logs.links?.length > 3">
                    <div class="join">
                        <Link
                            v-for="(link, index) in logs.links"
                            :key="index"
                            :href="link.url || '#'"
                            class="join-item btn btn-sm"
                            :class="{
                                'btn-primary': link.active,
                                'btn-disabled': !link.url,
                            }"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>