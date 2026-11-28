<script setup>
import { computed, ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import AttendanceBadge from "@/Components/Admin/AttendanceBadge.vue";
import { fmtDate, fmtDateTime } from "@/utils/format";

// Shared by the scan result and the registration detail page.
// Only renders the fields the server sent: volunteers get fewer.
const props = defineProps({
    registration: { type: Object, required: true },
});

const page = usePage();
const isAdmin = computed(() => page.props.auth.is_admin);

const rows = computed(() => {
    const r = props.registration;
    return [
        ["Gender", r.gender],
        ["Age", r.age != null ? `${r.age} years` : null],
        ["Blood group", r.blood_group],
        ["Emergency contact", r.emergency_contact],
        ["Employee", "is_employee" in r ? (r.is_employee ? "Yes" : "No") : null],
        ["WhatsApp", r.whatsapp],
        ["Email", r.email],
        ["Date of birth", r.dob ? fmtDate(r.dob) : null],
        ["Address", r.address],
        ["Medical history", r.medical_history],
        ["Registered", fmtDateTime(r.registered_at)],
        ["Added by", "created_by" in r ? r.created_by || "Website" : null],
        ["Confirmation email", "mail_sent_at" in r ? (r.mail_sent_at ? `Sent ${fmtDateTime(r.mail_sent_at)}` : "Not sent") : null],
        ["WhatsApp message", "whatsapp_sent_at" in r ? (r.whatsapp_sent_at ? `Sent ${fmtDateTime(r.whatsapp_sent_at)}` : "Not sent") : null],
    ].filter(([, v]) => v != null && v !== "");
});

// Blocks double taps while the request is in flight.
const processing = ref(false);
const post = (name) =>
    router.post(route(name, props.registration.id), {}, {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onFinish: () => (processing.value = false),
    });

const markAttended = () => post("admin.registrations.attend");

function undo() {
    if (!confirm(`Remove attendance for bib ${props.registration.bib}?`)) return;
    post("admin.registrations.unattend");
}
</script>

<template>
    <div class="overflow-hidden rounded-xl bg-white shadow">
        <div class="flex flex-wrap items-center justify-between gap-3 bg-gradient-to-r from-teal-800 to-teal-600 px-5 py-4 text-white">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase tracking-widest text-amber-300">Bib number</p>
                <p class="text-4xl font-extrabold leading-tight">{{ registration.bib }}</p>
                <p class="truncate text-lg font-semibold">{{ registration.name }}</p>
            </div>
            <AttendanceBadge :attended="!!registration.attended_at" class="bg-white" />
        </div>

        <dl class="divide-y divide-gray-100">
            <div v-for="[label, value] in rows" :key="label" class="grid grid-cols-3 gap-3 px-5 py-3 text-sm">
                <dt class="text-gray-500">{{ label }}</dt>
                <dd class="col-span-2 whitespace-pre-line break-words font-medium text-gray-900">{{ value }}</dd>
            </div>
        </dl>

        <div class="border-t border-gray-100 bg-gray-50 px-5 py-4">
            <template v-if="registration.attended_at">
                <p class="text-sm text-green-800">
                    <strong>Checked in</strong> {{ fmtDateTime(registration.attended_at) }}
                    <span v-if="registration.attended_by">by {{ registration.attended_by }}</span>
                </p>
                <button
                    v-if="isAdmin"
                    type="button"
                    class="mt-3 text-sm font-medium text-red-700 underline disabled:opacity-50"
                    :disabled="processing"
                    @click="undo"
                >
                    Undo attendance
                </button>
            </template>
            <button
                v-else
                type="button"
                class="w-full rounded-lg bg-green-600 px-4 py-3 text-base font-semibold text-white shadow hover:bg-green-700 focus:outline-none focus:ring-4 focus:ring-green-300 disabled:opacity-60"
                :disabled="processing"
                @click="markAttended"
            >
                {{ processing ? "Saving..." : "✓ Mark as attended" }}
            </button>
        </div>
    </div>
</template>
