<script setup>
import '../../css/app.css';
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Header from '@/Layouts/Header.vue';
import Navigation from '@/Layouts/Navigation.vue';

const page = usePage();

// Flash messages from the server (e.g. "Bib 4001 marked as attended.").
const flash = ref(null);
let timer;
watch(
    () => page.props.flash,
    (f) => {
        const msg = f?.success || f?.error;
        if (!msg) return;
        flash.value = { type: f.success ? 'success' : 'error', msg };
        clearTimeout(timer);
        timer = setTimeout(() => (flash.value = null), 5000);
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <Head>
        <meta head-key="robots" name="robots" content="noindex,nofollow" />
    </Head>
    <div class="flex h-screen bg-gray-100">
        <Navigation />

        <div class="flex min-w-0 flex-1 flex-col overflow-hidden">
            <Header />

            <main class="flex-1 overflow-y-auto overflow-x-hidden">
                <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-8">
                    <div
                        v-if="flash"
                        class="mb-4 flex items-start justify-between gap-3 rounded-lg px-4 py-3 text-sm font-medium"
                        :class="flash.type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                        role="status"
                    >
                        <span>{{ flash.msg }}</span>
                        <button type="button" class="text-lg leading-none" aria-label="Dismiss" @click="flash = null">&times;</button>
                    </div>

                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
