<script setup>
defineProps({ attended: { type: Boolean, default: false } });
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold"
        :class="attended ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'"
    >
        <span class="h-1.5 w-1.5 rounded-full" :class="attended ? 'bg-green-600' : 'bg-amber-500'"></span>
        {{ attended ? "Attended" : "Not yet" }}
    </span>
</template>
