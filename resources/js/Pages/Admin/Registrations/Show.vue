<script setup>
import RegistrationDetails from "@/Components/Admin/RegistrationDetails.vue";

defineProps({
    registration: { type: Object, required: true },
});
</script>

<template>
    <Head :title="`Bib ${registration.bib}`" />
    <Link :href="route('admin.registrations')" class="mb-4 inline-block text-sm font-medium text-teal-700">&larr; All registrations</Link>
    <div class="mx-auto max-w-2xl">
        <RegistrationDetails :registration="registration" />
    </div>
</template>
