<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import Pagination from "@/Components/Pagination.vue";
import AttendanceBadge from "@/Components/Admin/AttendanceBadge.vue";
import { fmtDateTime } from "@/utils/format";

const props = defineProps({
    registrations: { type: Object, required: true },
    filters: { type: Object, required: true },
});

const q = ref(props.filters.q || "");
const status = ref(props.filters.status || "");

function apply() {
    router.get(
        route("admin.registrations"),
        { q: q.value || undefined, status: status.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

// Search as you type (debounced); status tabs apply immediately.
let t;
watch(q, () => {
    clearTimeout(t);
    t = setTimeout(apply, 350);
});
watch(status, apply);

// The Excel download uses the same search / status filter as the list.
const exportUrl = computed(() =>
    route("admin.registrations.export", { q: q.value || undefined, status: status.value || undefined })
);

const tabs = [
    { value: "", label: "All" },
    { value: "pending", label: "Not yet" },
    { value: "attended", label: "Attended" },
];

const th = "px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600";
const td = "px-4 py-3 text-sm text-gray-900 align-top";
</script>

<template>
    <Head title="Registrations" />
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-baseline gap-3">
            <h1 class="text-2xl font-semibold text-gray-800 sm:text-3xl">Registrations</h1>
            <span class="text-sm text-gray-500">{{ registrations.total }} found</span>
        </div>

        <div v-if="$page.props.auth.is_admin" class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto">
            <!-- plain <a>: a file download, not an Inertia page visit -->
            <a
                :href="exportUrl"
                class="flex items-center justify-center gap-2 rounded-lg border border-green-700 bg-white px-4 py-2.5 text-sm font-semibold text-green-800 shadow-sm hover:bg-green-50"
            >
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Download Excel
            </a>
            <Link
                :href="route('admin.registrations.create')"
                class="flex items-center justify-center gap-2 rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-teal-800"
            >
                + Add registration
            </Link>
        </div>
    </div>

    <div class="mb-4 space-y-3 sm:flex sm:items-center sm:gap-3 sm:space-y-0">
        <input
            v-model="q"
            type="search"
            class="w-full rounded-lg border-gray-300 focus:border-teal-600 focus:ring-teal-600 sm:max-w-sm"
            :placeholder="$page.props.auth.is_admin ? 'Search bib, name, email or phone' : 'Search bib or name'"
        />
        <div class="flex rounded-lg bg-white p-1 shadow-sm">
            <button
                v-for="tab in tabs"
                :key="tab.value"
                type="button"
                class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium sm:flex-none"
                :class="status === tab.value ? 'bg-teal-700 text-white' : 'text-gray-600 hover:bg-gray-100'"
                @click="status = tab.value"
            >
                {{ tab.label }}
            </button>
        </div>
    </div>

    <!-- phones: cards -->
    <div class="space-y-3 md:hidden">
        <Link
            v-for="r in registrations.data"
            :key="r.id"
            :href="route('admin.registrations.show', r.id)"
            class="block rounded-xl bg-white p-4 shadow active:bg-gray-50"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xl font-bold text-teal-800">{{ r.bib }}</p>
                    <p class="truncate font-medium text-gray-900">{{ r.name }}</p>
                    <p class="text-sm text-gray-500">
                        {{ r.gender }}<span v-if="r.age != null"> · {{ r.age }} yrs</span> · {{ r.blood_group }}
                    </p>
                    <p v-if="r.whatsapp" class="text-sm text-gray-500">{{ r.whatsapp }}</p>
                </div>
                <AttendanceBadge :attended="!!r.attended_at" />
            </div>
        </Link>
        <p v-if="!registrations.data.length" class="rounded-xl bg-white p-6 text-center text-gray-500 shadow">No registrations found.</p>
    </div>

    <!-- tablets and up: table -->
    <div class="hidden overflow-x-auto rounded-xl bg-white shadow md:block">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th :class="th">Bib</th>
                    <th :class="th">Name</th>
                    <th :class="th">Gender / Age</th>
                    <th :class="th">Blood</th>
                    <th v-if="$page.props.auth.is_admin" :class="th">Contact</th>
                    <th :class="th">Registered</th>
                    <th :class="th">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr
                    v-for="r in registrations.data"
                    :key="r.id"
                    class="cursor-pointer hover:bg-teal-50"
                    @click="router.visit(route('admin.registrations.show', r.id))"
                >
                    <td :class="td" class="font-bold text-teal-800">{{ r.bib }}</td>
                    <td :class="td" class="font-medium">{{ r.name }}</td>
                    <td :class="td">{{ r.gender }}<span v-if="r.age != null"> / {{ r.age }}</span></td>
                    <td :class="td">{{ r.blood_group }}</td>
                    <td v-if="$page.props.auth.is_admin" :class="td">
                        <div>{{ r.whatsapp }}</div>
                        <div class="text-gray-500">{{ r.email }}</div>
                    </td>
                    <td :class="td" class="whitespace-nowrap text-gray-600">{{ fmtDateTime(r.registered_at) }}</td>
                    <td :class="td"><AttendanceBadge :attended="!!r.attended_at" /></td>
                </tr>
                <tr v-if="!registrations.data.length">
                    <td :class="td" colspan="7" class="py-8 text-center text-gray-500">No registrations found.</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="mt-4 overflow-x-auto">
        <Pagination :links="registrations.links" />
    </div>
</template>
