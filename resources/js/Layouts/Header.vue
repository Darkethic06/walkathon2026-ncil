<script setup>
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
</script>

<template>
    <header class="flex items-center justify-between gap-3 border-b-4 border-teal-600 bg-white px-4 py-3 sm:px-6">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="-ml-1 rounded p-1 text-gray-600 focus:outline-none focus:ring-2 focus:ring-teal-500 lg:hidden"
                aria-label="Open menu"
                @click="$page.props.showingMobileMenu = !$page.props.showingMobileMenu"
            >
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 6H20M4 12H20M4 18H11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
            <span class="truncate font-semibold text-gray-800 lg:hidden">Walkathon 2026</span>
        </div>

        <dropdown>
            <template #trigger>
                <button type="button" class="flex max-w-[12rem] items-center gap-2 text-right sm:max-w-none">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-medium text-gray-800">{{ $page.props.auth.user.full_name }}</span>
                        <span class="block text-xs text-gray-500">{{ $page.props.auth.role_name }}</span>
                    </span>
                    <svg class="h-4 w-4 flex-none text-gray-500" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.17l3.71-3.94a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </button>
            </template>

            <template #content>
                <dropdown-link class="w-full text-left" :href="route('admin.logout')" method="post" as="button">
                    Log out
                </dropdown-link>
            </template>
        </dropdown>
    </header>
</template>
