<script setup>
import { computed } from "vue";

const props = defineProps({
    stats: { type: Object, required: true },
});

const cards = computed(() => [
    { label: "Registrations", value: props.stats.registrations, cls: "text-gray-900" },
    { label: "Attended", value: props.stats.attended, cls: "text-green-700" },
    { label: "Not yet attended", value: props.stats.pending, cls: "text-amber-700" },
    ...("emailsNotSent" in props.stats ? [{ label: "Emails not sent", value: props.stats.emailsNotSent, cls: "text-red-700" }] : []),
    ...("openTickets" in props.stats ? [{ label: "Open support tickets", value: props.stats.openTickets, cls: "text-teal-700" }] : []),
]);

const percent = computed(() =>
    props.stats.registrations ? Math.round((props.stats.attended / props.stats.registrations) * 100) : 0
);
</script>

<template>
    <Head title="Dashboard" />
    <h1 class="mb-4 text-2xl font-semibold text-gray-800 sm:text-3xl">Dashboard</h1>

    <Link
        :href="route('admin.scan')"
        class="mb-6 flex items-center justify-center gap-3 rounded-xl bg-teal-700 px-5 py-5 text-lg font-semibold text-white shadow hover:bg-teal-800"
    >
        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7V5a1 1 0 011-1h2M17 4h2a1 1 0 011 1v2M20 17v2a1 1 0 01-1 1h-2M7 20H5a1 1 0 01-1-1v-2M7 12h10" />
        </svg>
        Scan a QR code
    </Link>

    <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-3">
        <div v-for="c in cards" :key="c.label" class="rounded-xl bg-white p-4 shadow sm:p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 sm:text-sm">{{ c.label }}</p>
            <p class="mt-1 text-3xl font-bold" :class="c.cls">{{ c.value }}</p>
        </div>
    </div>

    <div class="mt-6 rounded-xl bg-white p-5 shadow">
        <div class="mb-2 flex justify-between text-sm font-medium text-gray-700">
            <span>Check-in progress</span>
            <span>{{ percent }}%</span>
        </div>
        <div class="h-3 overflow-hidden rounded-full bg-gray-200">
            <div class="h-full rounded-full bg-green-600 transition-all" :style="{ width: percent + '%' }"></div>
        </div>
    </div>
</template>
