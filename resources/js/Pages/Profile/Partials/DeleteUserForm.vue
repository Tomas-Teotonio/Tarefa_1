<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route('current-user.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <ActionSection>
        <template #title>
            <span class="text-base-content">Delete Account</span>
        </template>

        <template #description>
            <span class="opacity-70">
                Permanently delete your account.
            </span>
        </template>

        <template #content>
            <div class="space-y-5">
                <div class="rounded-2xl border border-error/20 bg-error/5 p-5 text-sm leading-relaxed opacity-80">
                    Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
                </div>

                <div>
                    <button class="btn btn-error" @click="confirmUserDeletion">
                        Delete Account
                    </button>
                </div>
            </div>

            <DialogModal :show="confirmingUserDeletion" @close="closeModal">
                <template #title>
                    <span class="text-lg font-semibold">Delete Account</span>
                </template>

                <template #content>
                    <div class="space-y-4">
                        <p class="text-sm leading-relaxed opacity-80">
                            Are you sure you want to delete your account? Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.
                        </p>

                        <TextInput
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full rounded-2xl border-base-300 bg-base-100"
                            placeholder="Password"
                            autocomplete="current-password"
                            @keyup.enter="deleteUser"
                        />

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                </template>

                <template #footer>
                    <div class="flex flex-wrap justify-end gap-3">
                        <button class="btn btn-ghost" @click="closeModal">
                            Cancel
                        </button>

                        <button
                            class="btn btn-error"
                            :class="{ 'opacity-50': form.processing }"
                            :disabled="form.processing"
                            @click="deleteUser"
                        >
                            Delete Account
                        </button>
                    </div>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>