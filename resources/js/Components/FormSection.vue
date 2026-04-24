<script setup>
import { computed, useSlots } from 'vue';
import SectionTitle from './SectionTitle.vue';

defineEmits(['submitted']);

const hasActions = computed(() => !!useSlots().actions);
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <div class="mt-5 md:mt-0 md:col-span-2">
            <form @submit.prevent="$emit('submitted')">
                <div
                    class="border border-base-300 bg-base-100 px-4 py-5 shadow-sm sm:p-6"
                    :class="hasActions ? 'sm:rounded-b-none' : 'sm:rounded-3xl'"
                >
                    <div class="grid grid-cols-6 gap-6">
                        <slot name="form" />
                    </div>
                </div>

                <div
                    v-if="hasActions"
                    class="flex items-center justify-end border-x border-b border-base-300 bg-base-200 px-4 py-4 text-end shadow-sm  sm:px-6"
                >
                    <slot name="actions" />
                </div>
            </form>
        </div>
    </div>
</template>