<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref } from "vue";
import { router } from "@inertiajs/vue3";
import RegistrationDetails from "@/Components/Admin/RegistrationDetails.vue";

const props = defineProps({
    code: { type: String, default: "" },
    registration: { type: Object, default: null },
});

const showScanner = !props.code; // a result is on screen when a code was looked up

const video = ref(null);
const cameraError = ref("");
const starting = ref(true);
const manualBib = ref("");
let scanner = null;
let handled = false;

function lookup(code) {
    router.get(route("admin.scan"), { code }, { replace: false });
}

function scanNext() {
    router.get(route("admin.scan"));
}

function submitManual() {
    const bib = manualBib.value.replace(/\D/g, "");
    if (bib) lookup(bib);
}

async function startScanner() {
    if (!window.isSecureContext) {
        cameraError.value = "The camera only works over HTTPS (or on localhost). Type the bib number below instead.";
        starting.value = false;
        return;
    }

    try {
        // Loaded only in the browser: the library needs window and a Web Worker.
        const { default: QrScanner } = await import("qr-scanner");
        await nextTick();
        scanner = new QrScanner(
            video.value,
            (result) => {
                if (handled) return;
                handled = true;
                scanner.stop();
                navigator.vibrate?.(80);
                lookup(result.data);
            },
            {
                preferredCamera: "environment",
                highlightScanRegion: true,
                highlightCodeOutline: true,
                maxScansPerSecond: 8,
            },
        );
        await scanner.start();
    } catch (e) {
        cameraError.value =
            "Couldn't open the camera. Allow camera access for this site in your browser settings, or type the bib number below.";
    } finally {
        starting.value = false;
    }
}

onMounted(() => {
    if (showScanner) startScanner();
});
onBeforeUnmount(() => {
    scanner?.destroy();
    scanner = null;
});
</script>

<template>
    <Head title="Scan QR" />
    <h1 class="mb-4 text-2xl font-semibold text-gray-800 sm:text-3xl">Scan QR</h1>

    <div class="mx-auto max-w-xl">
        <!-- scanner -->
        <template v-if="showScanner">
            <div class="relative overflow-hidden rounded-xl bg-black shadow">
                <video ref="video" class="aspect-square w-full object-cover" playsinline muted></video>
                <p v-if="starting" class="absolute inset-0 flex items-center justify-center text-sm text-white">Starting camera…</p>
            </div>
            <p v-if="cameraError" class="mt-3 rounded-lg bg-amber-100 px-4 py-3 text-sm text-amber-900">{{ cameraError }}</p>
            <p v-else class="mt-3 text-center text-sm text-gray-600">Point the camera at the walker's QR code.</p>
        </template>

        <!-- result -->
        <template v-else>
            <RegistrationDetails v-if="registration" :registration="registration" />
            <div v-else class="rounded-xl bg-red-50 p-5 text-red-800 shadow">
                <p class="font-semibold">No registration found</p>
                <p class="mt-1 break-words text-sm">This QR code or bib number doesn't match any walker: <code>{{ code }}</code></p>
            </div>

            <button
                type="button"
                class="mt-4 w-full rounded-lg bg-teal-700 px-4 py-3 text-base font-semibold text-white shadow hover:bg-teal-800"
                @click="scanNext"
            >
                Scan next
            </button>
        </template>

        <!-- manual fallback -->
        <form class="mt-6 rounded-xl bg-white p-4 shadow" @submit.prevent="submitManual">
            <label for="manualBib" class="block text-sm font-medium text-gray-700">QR not scanning? Enter the bib number</label>
            <div class="mt-2 flex gap-2">
                <input
                    id="manualBib"
                    v-model="manualBib"
                    type="text"
                    inputmode="numeric"
                    placeholder="e.g. 4001"
                    class="min-w-0 flex-1 rounded-lg border-gray-300 focus:border-teal-600 focus:ring-teal-600"
                />
                <button type="submit" class="rounded-lg bg-gray-800 px-4 py-2 font-semibold text-white hover:bg-gray-900">Find</button>
            </div>
        </form>
    </div>
</template>
