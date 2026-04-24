<script setup>
import { ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';

const props = defineProps({
    user: Object,
});

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null,
});

const verificationLinkSent = ref(null);
const photoPreview = ref(null);
const photoInput = ref(null);

const updateProfileInformation = () => {
    if (photoInput.value) {
        form.photo = photoInput.value.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value.click();
};

const updatePhotoPreview = () => {
    const photo = photoInput.value.files[0];

    if (!photo) return;

    const reader = new FileReader();

    reader.onload = (e) => {
        photoPreview.value = e.target.result;
    };

    reader.readAsDataURL(photo);
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = null;
    }
};
</script>

<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title>
            <span class="text-base-content">Profile Information</span>
        </template>

        <template #description>
            <span class="opacity-70">
                Update your account's profile information and email address.
            </span>
        </template>

        <template #form>
            <div
                v-if="$page.props.jetstream.managesProfilePhotos"
                class="col-span-6 sm:col-span-4 rounded-2xl border border-dashed border-base-300 bg-base-200 p-5"
            >
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview"
                >

                <InputLabel for="photo" value="Photo" />

                <div v-show="!photoPreview" class="mt-4">
                    <img
                        :src="user.profile_photo_url"
                        :alt="user.name"
                        class="size-24 rounded-full object-cover ring ring-base-300 ring-offset-2 ring-offset-base-200"
                    >
                </div>

                <div v-show="photoPreview" class="mt-4">
                    <span
                        class="block size-24 rounded-full bg-cover bg-center bg-no-repeat ring ring-base-300 ring-offset-2 ring-offset-base-200"
                        :style="'background-image: url(\'' + photoPreview + '\');'"
                    />
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-outline"
                        @click.prevent="selectNewPhoto"
                    >
                        Select A New Photo
                    </button>

                    <button
                        v-if="user.profile_photo_path"
                        type="button"
                        class="btn btn-sm btn-ghost"
                        @click.prevent="deletePhoto"
                    >
                        Remove Photo
                    </button>
                </div>

                <InputError :message="form.errors.photo" class="mt-3" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-2 block w-full rounded-2xl border-base-300 bg-base-100"
                    required
                    autocomplete="name"
                />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-2 block w-full rounded-2xl border-base-300 bg-base-100"
                    required
                    autocomplete="username"
                />
                <InputError :message="form.errors.email" class="mt-2" />

                <div v-if="$page.props.jetstream.hasEmailVerification && user.email_verified_at === null" class="mt-4 rounded-2xl bg-base-200 p-4">
                    <p class="text-sm">
                        Your email address is unverified.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="ms-1 font-medium underline underline-offset-4 hover:opacity-80"
                            @click.prevent="sendEmailVerification"
                        >
                            Click here to re-send the verification email.
                        </Link>
                    </p>

                    <div v-show="verificationLinkSent" class="mt-3 text-sm font-medium text-success">
                        A new verification link has been sent to your email address.
                    </div>
                </div>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3 text-success">
                Saved.
            </ActionMessage>

            <button
                type="submit"
                class="btn btn-primary"
                :class="{ 'opacity-50': form.processing }"
                :disabled="form.processing"
            >
                Save
            </button>
        </template>
    </FormSection>
</template>