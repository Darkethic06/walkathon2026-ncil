<script setup>
import { router } from "@inertiajs/vue3";

defineProps({
    subUsers: { type: Array, required: true },
});

function remove(u) {
    if (!confirm(`Delete sub-user ${u.name}? Their past check-ins are kept.`)) return;
    router.delete(route("admin.subusers.destroy", u.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Sub-users" />
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 sm:text-3xl">Sub-users</h1>
            <p class="text-sm text-gray-500">Volunteers who can see registrations, scan QR codes and mark walkers as attended.</p>
        </div>
        <Link
            :href="route('admin.subusers.create')"
            class="w-full rounded-lg bg-teal-700 px-4 py-2.5 text-center font-semibold text-white shadow hover:bg-teal-800 sm:w-auto"
        >
            + Add sub-user
        </Link>
    </div>

    <div class="space-y-3">
        <div
            v-for="u in subUsers"
            :key="u.id"
            class="flex flex-col gap-3 rounded-xl bg-white p-4 shadow sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="min-w-0">
                <p class="flex flex-wrap items-center gap-2 font-semibold text-gray-900">
                    {{ u.name }}
                    <span
                        class="rounded-full px-2 py-0.5 text-xs font-semibold"
                        :class="u.active ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700'"
                    >{{ u.active ? "Active" : "Inactive" }}</span>
                </p>
                <p class="truncate text-sm text-gray-600">{{ u.email }}<span v-if="u.phone"> · {{ u.phone }}</span></p>
                <p class="text-sm text-gray-500">{{ u.checkIns }} check-ins</p>
            </div>
            <div class="flex gap-2">
                <Link
                    :href="route('admin.subusers.edit', u.id)"
                    class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-center text-sm font-medium text-gray-700 hover:bg-gray-50 sm:flex-none"
                >Edit</Link>
                <button
                    type="button"
                    class="flex-1 rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-50 sm:flex-none"
                    @click="remove(u)"
                >Delete</button>
            </div>
        </div>

        <p v-if="!subUsers.length" class="rounded-xl bg-white p-6 text-center text-gray-500 shadow">
            No sub-users yet. Add one for each volunteer who will check walkers in.
        </p>
    </div>
</template>
