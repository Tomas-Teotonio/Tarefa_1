<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';

defineProps({
    confirmsTwoFactorAuthentication: Boolean,
    sessions: Array,
});
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <div class="badge badge-primary badge-outline">Conta</div>
                        <div class="badge badge-secondary badge-outline">Segurança</div>
                        <div class="badge badge-accent badge-outline">Jetstream</div>
                    </div>

                    <h2 class="text-3xl font-bold text-base-content">
                        Profile
                    </h2>

                    <p class="mt-2 text-sm opacity-70">
                        Manage your account settings, security and active sessions.
                    </p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <div v-if="$page.props.jetstream.canUpdateProfileInformation">
                <UpdateProfileInformationForm
                    :user="$page.props.auth.user"
                    class="rounded-3xl border border-base-300 bg-base-100 shadow-sm overflow-hidden"
                />
            </div>

            <div v-if="$page.props.jetstream.canUpdatePassword">
                <UpdatePasswordForm
                    class="rounded-3xl border border-base-300 bg-base-100 shadow-sm overflow-hidden"
                />
            </div>

            <div v-if="$page.props.jetstream.canManageTwoFactorAuthentication">
                <TwoFactorAuthenticationForm
                    :requires-confirmation="confirmsTwoFactorAuthentication"
                    class="rounded-3xl border border-base-300 bg-base-100 shadow-sm overflow-hidden"
                />
            </div>

            <LogoutOtherBrowserSessionsForm
                :sessions="sessions"
                class="rounded-3xl border border-base-300 bg-base-100 shadow-sm overflow-hidden"
            />

            <template v-if="$page.props.jetstream.hasAccountDeletionFeatures">
                <DeleteUserForm
                    class="rounded-3xl border border-error/20 bg-base-100 shadow-sm overflow-hidden"
                />
            </template>
        </div>
    </AppLayout>
</template>