<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import NavLink from "@/Components/NavLink.vue";

const page = usePage();
const auth = computed(() => page.props.auth);

// `show` mirrors the route middleware in routes/admin.php.
const items = computed(() => [
    { label: "Dashboard", route: "admin.dashboard", match: "admin.dashboard", show: true,
      icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" },
    { label: "Scan QR", route: "admin.scan", match: "admin.scan", show: true,
      icon: "M4 7V5a1 1 0 011-1h2M17 4h2a1 1 0 011 1v2M20 17v2a1 1 0 01-1 1h-2M7 20H5a1 1 0 01-1-1v-2M7 12h10" },
    { label: "Registrations", route: "admin.registrations", match: "admin.registrations*", show: true,
      icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" },
    { label: "Support Tickets", route: "admin.supporttickets", match: "admin.supporttickets", show: auth.value.is_admin,
      icon: "M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z" },
    { label: "Sub-users", route: "admin.subusers.index", match: "admin.subusers.*", show: auth.value.can_manage_sub_users,
      icon: "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" },
].filter((i) => i.show));

const closeMenu = () => (page.props.showingMobileMenu = false);
</script>

<template>
    <div
        :class="$page.props.showingMobileMenu ? 'block' : 'hidden'"
        @click="closeMenu"
        class="fixed inset-0 z-20 bg-black opacity-50 transition-opacity lg:hidden"
    ></div>

    <div
        :class="$page.props.showingMobileMenu ? 'translate-x-0 ease-out' : '-translate-x-full ease-in'"
        class="fixed inset-y-0 left-0 z-30 w-64 transform overflow-y-auto bg-gray-900 transition duration-300 lg:static lg:inset-0 lg:translate-x-0"
    >
        <div class="mt-8 flex items-center justify-center px-4">
            <span class="text-center text-xl font-semibold text-white">Walkathon 2026</span>
        </div>

        <nav class="mt-8" @click="(e) => e.target.closest('a') && closeMenu()">
            <nav-link
                v-for="item in items"
                :key="item.route"
                :href="route(item.route)"
                :active="route().current(item.match)"
            >
                <template #icon>
                    <svg class="h-6 w-6 flex-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" :d="item.icon" />
                    </svg>
                </template>
                {{ item.label }}
            </nav-link>
        </nav>
    </div>
</template>
