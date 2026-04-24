<template>
    <div class="px-1 md:px-0">
        <div class="rounded-3xl bg-base-100 p-6">
            <h3 class="text-lg font-semibold text-base-content">
                <slot name="title" />
            </h3>

            <p class="mt-2 text-sm leading-relaxed text-base-content/70">
                <slot name="description" />
            </p>
        </div>
    </div>
</template>