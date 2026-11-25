<script setup>
import Pagination from "@/Components/Pagination.vue";
import { fmtDateTime } from "@/utils/format";

defineProps({
    tickets: { type: Object, required: true },
});

const th = "px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600";
const td = "px-4 py-3 text-sm text-gray-900";
</script>

<template>
    <Head title="Support Tickets" />
    <div class="mb-4 flex items-baseline justify-between gap-3">
        <h1 class="text-2xl font-semibold text-gray-800 sm:text-3xl">Support Tickets</h1>
        <span class="text-sm text-gray-500">{{ tickets.total }} total</span>
    </div>

    <!-- phones: cards -->
    <div class="space-y-3 md:hidden">
        <div v-for="t in tickets.data" :key="t.id" class="rounded-xl bg-white p-4 shadow">
            <div class="flex items-start justify-between gap-3">
                <p class="font-semibold text-gray-900">#{{ t.id }} {{ t.name }}</p>
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ t.status }}</span>
            </div>
            <a :href="`mailto:${t.email}`" class="block truncate text-sm text-teal-700">{{ t.email }}</a>
            <a :href="`tel:+91${t.phone}`" class="block text-sm text-teal-700">{{ t.phone }}</a>
            <p v-if="t.message" class="mt-2 whitespace-pre-line break-words text-sm text-gray-700">{{ t.message }}</p>
            <p class="mt-1 text-xs text-gray-500">{{ fmtDateTime(t.created_at) }}</p>
        </div>
        <p v-if="!tickets.data.length" class="rounded-xl bg-white p-6 text-center text-gray-500 shadow">No support tickets yet.</p>
    </div>

    <!-- tablets and up: table -->
    <div class="hidden overflow-x-auto rounded-xl bg-white shadow md:block">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th :class="th">#</th>
                    <th :class="th">Name</th>
                    <th :class="th">Email</th>
                    <th :class="th">Phone</th>
                    <th :class="th">Message</th>
                    <th :class="th">Status</th>
                    <th :class="th">Raised</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="t in tickets.data" :key="t.id">
                    <td :class="td">{{ t.id }}</td>
                    <td :class="td">{{ t.name }}</td>
                    <td :class="td"><a :href="`mailto:${t.email}`" class="text-teal-700">{{ t.email }}</a></td>
                    <td :class="td"><a :href="`tel:+91${t.phone}`" class="text-teal-700">{{ t.phone }}</a></td>
                    <td :class="td" class="min-w-[16rem] max-w-md whitespace-pre-line break-words">{{ t.message || "—" }}</td>
                    <td :class="td">{{ t.status }}</td>
                    <td :class="td" class="whitespace-nowrap">{{ fmtDateTime(t.created_at) }}</td>
                </tr>
                <tr v-if="!tickets.data.length">
                    <td :class="td" colspan="7" class="py-8 text-center text-gray-500">No support tickets yet.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-4 overflow-x-auto">
        <Pagination :links="tickets.links" />
    </div>
</template>
