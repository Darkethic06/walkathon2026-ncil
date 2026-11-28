<script setup>
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    bloodGroups: { type: Array, required: true },
    minAge: { type: Number, required: true },
});

// Same field names as the public form, validated by the same server rules.
const form = useForm({
    firstName: "",
    lastName: "",
    gender: "",
    email: "",
    whatsapp: "",
    dob: "",
    bloodGroup: "",
    address: "",
    emergency: "",
    medical: "",
    privacy: false,
    employee: false,
    sendEmail: true,
    sendWhatsApp: true,
});

// Digits only, max 10, in phone fields.
for (const key of ["whatsapp", "emergency"]) {
    watch(() => form[key], (v) => {
        const clean = String(v ?? "").replace(/\D/g, "").slice(0, 10);
        if (clean !== v) form[key] = clean;
    });
}

// Youngest allowed birth date (local time).
const maxDob = computed(() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() - props.minAge);
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
});

function submit() {
    form.post(route("admin.registrations.store"), {
        onError: () => document.querySelector("[data-error]")?.scrollIntoView({ behavior: "smooth", block: "center" }),
    });
}

const input = "mt-1 block w-full rounded-lg border-gray-300 focus:border-teal-600 focus:ring-teal-600";
const label = "block text-sm font-medium text-gray-700";
const err = "mt-1 text-sm text-red-600";
</script>

<template>
    <Head title="Add registration" />
    <Link :href="route('admin.registrations')" class="mb-4 inline-block text-sm font-medium text-teal-700">&larr; All registrations</Link>
    <h1 class="mb-1 text-2xl font-semibold text-gray-800 sm:text-3xl">Add registration</h1>
    <p class="mb-4 text-sm text-gray-500">For walkers who register by phone or at the venue. A bib number is given automatically.</p>

    <form class="max-w-3xl space-y-4 rounded-xl bg-white p-5 shadow sm:p-6" novalidate @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="firstName" :class="label">First name *</label>
                <input id="firstName" v-model="form.firstName" type="text" :class="input" autocomplete="off" />
                <p v-if="form.errors.firstName" data-error :class="err">{{ form.errors.firstName }}</p>
            </div>
            <div>
                <label for="lastName" :class="label">Last name *</label>
                <input id="lastName" v-model="form.lastName" type="text" :class="input" autocomplete="off" />
                <p v-if="form.errors.lastName" data-error :class="err">{{ form.errors.lastName }}</p>
            </div>
        </div>

        <fieldset>
            <legend :class="label">Gender *</legend>
            <div class="mt-2 flex gap-6">
                <label v-for="g in ['Male', 'Female']" :key="g" class="flex items-center gap-2 text-sm">
                    <input v-model="form.gender" type="radio" :value="g" class="h-4 w-4 border-gray-300 text-teal-700 focus:ring-teal-600" />
                    {{ g }}
                </label>
            </div>
            <p v-if="form.errors.gender" data-error :class="err">{{ form.errors.gender }}</p>
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" :class="label">Email</label>
                <input id="email" v-model="form.email" type="email" :class="input" autocomplete="off" />
                <p v-if="form.errors.email" data-error :class="err">{{ form.errors.email }}</p>
            </div>
            <div>
                <label for="whatsapp" :class="label">WhatsApp number *</label>
                <input id="whatsapp" v-model="form.whatsapp" type="tel" inputmode="numeric" maxlength="10" placeholder="10 digit number" :class="input" />
                <p v-if="form.errors.whatsapp" data-error :class="err">{{ form.errors.whatsapp }}</p>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="dob" :class="label">Date of birth *</label>
                <input id="dob" v-model="form.dob" type="date" :max="maxDob" :class="input" />
                <p v-if="form.errors.dob" data-error :class="err">{{ form.errors.dob }}</p>
            </div>
            <div>
                <label for="bloodGroup" :class="label">Blood group *</label>
                <select id="bloodGroup" v-model="form.bloodGroup" :class="input">
                    <option value="">Select blood group</option>
                    <option v-for="g in bloodGroups" :key="g" :value="g">{{ g }}</option>
                </select>
                <p v-if="form.errors.bloodGroup" data-error :class="err">{{ form.errors.bloodGroup }}</p>
            </div>
        </div>

        <div>
            <label for="address" :class="label">Address *</label>
            <textarea id="address" v-model="form.address" rows="2" :class="input"></textarea>
            <p v-if="form.errors.address" data-error :class="err">{{ form.errors.address }}</p>
        </div>

        <div>
            <label for="emergency" :class="label">Emergency contact number</label>
            <input id="emergency" v-model="form.emergency" type="tel" inputmode="numeric" maxlength="10" placeholder="Must differ from WhatsApp number" :class="input" />
            <p v-if="form.errors.emergency" data-error :class="err">{{ form.errors.emergency }}</p>
        </div>

        <div>
            <label for="medical" :class="label">Medical history</label>
            <textarea id="medical" v-model="form.medical" rows="3" placeholder="Allergies, surgeries, medicines, conditions. Leave blank if nothing." :class="input"></textarea>
            <p v-if="form.errors.medical" data-error :class="err">{{ form.errors.medical }}</p>
        </div>

        <div class="space-y-3 rounded-lg bg-gray-50 p-4">
            <label class="flex items-start gap-3 text-sm text-gray-700">
                <input v-model="form.privacy" type="checkbox" class="mt-0.5 h-5 w-5 flex-none rounded border-gray-300 text-teal-700 focus:ring-teal-600" />
                <span>The walker has agreed to the Privacy Policy and confirmed they are fit to walk 4 km. *</span>
            </label>
            <p v-if="form.errors.privacy" data-error :class="err">{{ form.errors.privacy }}</p>

            <label class="flex items-start gap-3 text-sm text-gray-700">
                <input v-model="form.employee" type="checkbox" class="mt-0.5 h-5 w-5 flex-none rounded border-gray-300 text-teal-700 focus:ring-teal-600" />
                <span>The walker is an employee.</span>
            </label>

            <label class="flex items-start gap-3 text-sm text-gray-700">
                <input v-model="form.sendEmail" :disabled="!form.email.trim()" type="checkbox" class="mt-0.5 h-5 w-5 flex-none rounded border-gray-300 text-teal-700 focus:ring-teal-600" />
                <span>Email the bib number and QR code to the walker<span v-if="!form.email.trim()" class="text-gray-400"> (no email entered)</span></span>
            </label>

            <label class="flex items-start gap-3 text-sm text-gray-700">
                <input v-model="form.sendWhatsApp" type="checkbox" class="mt-0.5 h-5 w-5 flex-none rounded border-gray-300 text-teal-700 focus:ring-teal-600" />
                <span>WhatsApp the bib number and bib page link to the walker</span>
            </label>
        </div>

        <button
            type="submit"
            class="w-full rounded-lg bg-teal-700 px-4 py-3 font-semibold text-white shadow hover:bg-teal-800 disabled:opacity-60 sm:w-auto"
            :disabled="form.processing"
        >
            {{ form.processing ? "Saving..." : "Add registration" }}
        </button>
    </form>
</template>
