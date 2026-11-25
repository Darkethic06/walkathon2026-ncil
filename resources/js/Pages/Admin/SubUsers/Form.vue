<script setup>
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";

const props = defineProps({
    subUser: { type: Object, default: null },
});

const editing = computed(() => !!props.subUser);

const form = useForm({
    first_name: props.subUser?.first_name ?? "",
    last_name: props.subUser?.last_name ?? "",
    email: props.subUser?.email ?? "",
    phone: props.subUser?.phone ?? "",
    password: "",
    password_confirmation: "",
    active: props.subUser?.active ?? true,
});

function submit() {
    const opts = { onFinish: () => form.reset("password", "password_confirmation") };
    if (editing.value) {
        form.put(route("admin.subusers.update", props.subUser.id), opts);
    } else {
        form.post(route("admin.subusers.store"), opts);
    }
}

const input = "mt-1 block w-full rounded-lg border-gray-300 focus:border-teal-600 focus:ring-teal-600";
const label = "block text-sm font-medium text-gray-700";
const err = "mt-1 text-sm text-red-600";
</script>

<template>
    <Head :title="editing ? 'Edit sub-user' : 'Add sub-user'" />
    <Link :href="route('admin.subusers.index')" class="mb-4 inline-block text-sm font-medium text-teal-700">&larr; Sub-users</Link>
    <h1 class="mb-4 text-2xl font-semibold text-gray-800 sm:text-3xl">{{ editing ? "Edit sub-user" : "Add sub-user" }}</h1>

    <form class="max-w-2xl space-y-4 rounded-xl bg-white p-5 shadow sm:p-6" @submit.prevent="submit">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="first_name" :class="label">First name *</label>
                <input id="first_name" v-model="form.first_name" type="text" :class="input" required autocomplete="off" />
                <p v-if="form.errors.first_name" :class="err">{{ form.errors.first_name }}</p>
            </div>
            <div>
                <label for="last_name" :class="label">Last name</label>
                <input id="last_name" v-model="form.last_name" type="text" :class="input" autocomplete="off" />
                <p v-if="form.errors.last_name" :class="err">{{ form.errors.last_name }}</p>
            </div>
        </div>

        <div>
            <label for="email" :class="label">Email (used to log in) *</label>
            <input id="email" v-model="form.email" type="email" :class="input" required autocomplete="off" />
            <p v-if="form.errors.email" :class="err">{{ form.errors.email }}</p>
        </div>

        <div>
            <label for="phone" :class="label">Mobile number</label>
            <input id="phone" v-model="form.phone" type="tel" inputmode="numeric" maxlength="10" :class="input" autocomplete="off" />
            <p v-if="form.errors.phone" :class="err">{{ form.errors.phone }}</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" :class="label">{{ editing ? "New password" : "Password *" }}</label>
                <input id="password" v-model="form.password" type="password" :class="input" :required="!editing" autocomplete="new-password" />
                <p v-if="editing" class="mt-1 text-xs text-gray-500">Leave blank to keep the current password.</p>
                <p v-if="form.errors.password" :class="err">{{ form.errors.password }}</p>
            </div>
            <div>
                <label for="password_confirmation" :class="label">Confirm password</label>
                <input id="password_confirmation" v-model="form.password_confirmation" type="password" :class="input" autocomplete="new-password" />
            </div>
        </div>

        <label class="flex items-center gap-3">
            <input v-model="form.active" type="checkbox" class="h-5 w-5 rounded border-gray-300 text-teal-700 focus:ring-teal-600" />
            <span class="text-sm text-gray-700">Active (can log in)</span>
        </label>

        <button
            type="submit"
            class="w-full rounded-lg bg-teal-700 px-4 py-3 font-semibold text-white shadow hover:bg-teal-800 disabled:opacity-60 sm:w-auto"
            :disabled="form.processing"
        >
            {{ form.processing ? "Saving..." : editing ? "Save changes" : "Create sub-user" }}
        </button>
    </form>
</template>
