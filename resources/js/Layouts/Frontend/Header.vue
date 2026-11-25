<script setup>
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const open = ref(false);

// On the home page the menu scrolls to a section; on any other page it
// navigates to the home page and then to that section.
const isHome = computed(() => page.component === "Frontend/Index");
const to = (hash) => (isHome.value ? `#${hash}` : `/#${hash}`);

const links = [
    { label: "About", hash: "about" },
    { label: "Event details", hash: "details" },
    { label: "FAQ", hash: "faq" },
];

const close = () => (open.value = false);
</script>

<template>
    <!-- navbar section start -->
    <header class="nav" id="top">
        <a v-if="isHome" class="brand" href="#top" aria-label="Nephrocare - home">
            <img src="/images/nephrocare-logo.png" alt="Nephrocare" width="834" height="109" />
        </a>
        <Link v-else class="brand" href="/" aria-label="Nephrocare - home">
            <img src="/images/nephrocare-logo.png" alt="Nephrocare" width="834" height="109" />
        </Link>

        <button
            class="burger"
            type="button"
            :aria-label="open ? 'Close menu' : 'Open menu'"
            :aria-expanded="open ? 'true' : 'false'"
            @click="open = !open"
        >
            <i></i><i></i>
        </button>

        <nav class="menu" :class="{ open }" @click="(e) => e.target.tagName === 'A' && close()">
            <a v-for="link in links" :key="link.hash" :href="to(link.hash)">{{ link.label }}</a>
            <a class="btn small" :href="to('register')">Register</a>
        </nav>
    </header>
    <!-- navbar section end -->
</template>
