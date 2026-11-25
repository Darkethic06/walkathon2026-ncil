<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
	links: Array,
	required: true,
});
</script>

<template>
	<div v-if="links.length > 3">
		<div class="flex flex-wrap -mb-1">
			<template v-for="(link, key) in links">
				<div v-if="link.url === null" :key="key" class="px-3 py-2 mr-1 mb-1 text-sm leading-4 text-gray-400 rounded border bg-white" v-html="link.label" />
				<Link v-else :key="key + 1" class="px-3 py-2 mr-1 mb-1 text-sm leading-4 rounded border bg-white hover:bg-teal-700 hover:text-white focus:border-teal-700" :class="{ '!bg-teal-700 text-white': link.active }" :href="link.url" v-html="link.label" />
			</template>
		</div>
	</div>
</template>
