<script setup lang="ts">
/**
 * Method 1 of QR attendance: the device camera reads the code printed on a
 * student's or staff member's ID card. The code itself is just the signed
 * URL `QrAttendanceController` already serves without a login (Method 2) —
 * this page's only job is to decode it and open it, so scanning here and
 * a phone's camera app opening the same code do exactly the same thing.
 *
 * The scanned URL is same-origin (it is a route on this app), so it is
 * opened as a normal navigation rather than fetched — that way the
 * existing `attendance.qr-confirmation` page renders as-is with no
 * duplicate marking logic here.
 */
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Head, router } from '@inertiajs/vue3';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { route } from 'ziggy-js';
import QrScanner from 'qr-scanner';

const breadcrumbItems = [
    { title: 'Attendance', href: route('attendance.hub') },
    { title: 'Scan', href: route('attendance.scan') },
];

const videoEl = ref<HTMLVideoElement | null>(null);
const hasCamera = ref(true);
const cameraError = ref<string | null>(null);
const lastResult = ref<string | null>(null);
let scanner: QrScanner | null = null;

function handleDecode(text: string): void {
    // Only ever follow a link this app itself could have generated — never
    // let a scanned code navigate the browser somewhere else.
    if (!text.startsWith(window.location.origin)) {
        lastResult.value = 'That code is not an attendance code for this school.';
        return;
    }

    lastResult.value = 'Marking attendance…';
    window.location.href = text;
}

const manualCode = ref('');

function submitManual(): void {
    const code = manualCode.value.trim();

    if (code === '') {
        lastResult.value = 'Enter the code from the ID card.';

        return;
    }

    handleDecode(code);
}

onMounted(async () => {
    hasCamera.value = await QrScanner.hasCamera();

    if (!hasCamera.value || !videoEl.value) {
        return;
    }

    scanner = new QrScanner(videoEl.value, (result) => handleDecode(result.data), {
        preferredCamera: 'environment',
        highlightScanRegion: true,
        highlightCodeOutline: true,
    });

    try {
        await scanner.start();
    } catch (error) {
        hasCamera.value = false;
        cameraError.value = error instanceof Error ? error.message : 'Could not start the camera.';
    }
});

onBeforeUnmount(() => {
    scanner?.stop();
    scanner?.destroy();
    scanner = null;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Scan Attendance" />

        <div class="mx-auto max-w-lg space-y-4 p-4 md:space-y-6 md:p-6">
            <div>
                <h1 class="text-lg font-bold text-foreground md:text-2xl">Scan Attendance</h1>
                <p class="mt-1 text-xs text-muted-foreground md:text-sm">
                    Point the camera at a student's or staff member's ID card to mark them present for today.
                </p>
            </div>

            <div v-show="hasCamera" class="overflow-hidden rounded-lg border border-border bg-black">
                <video ref="videoEl" class="aspect-square w-full object-cover" muted playsinline></video>
            </div>

            <div v-if="!hasCamera" class="rounded-lg border border-border bg-card p-4 text-sm text-muted-foreground">
                <p class="mb-2 flex items-center gap-2 font-medium text-foreground">
                    <Icon icon="camera-off" class="h-4 w-4" />
                    Camera not available. Allow camera access in your browser, or type the code below.
                </p>
            </div>

            <form class="space-y-2 rounded-lg border border-border bg-card p-4" @submit.prevent="submitManual">
                <label for="manual-code" class="text-sm font-medium text-foreground">Type or scan the code</label>
                <p class="text-xs text-muted-foreground">
                    Paste the link from the ID card, or use a USB barcode scanner on this box.
                </p>
                <div class="flex gap-2">
                    <input
                        id="manual-code"
                        v-model="manualCode"
                        type="text"
                        autocomplete="off"
                        placeholder="Paste the card link or code"
                        class="min-w-0 flex-1 rounded-md border border-border bg-background px-3 py-2 text-sm"
                    />
                    <Button type="submit">Mark</Button>
                </div>
            </form>

            <p v-if="lastResult" class="rounded-lg border border-border bg-card p-3 text-sm text-foreground">
                {{ lastResult }}
            </p>

            <Button variant="outline" class="w-full" @click="router.visit(route('attendance.hub'))">
                Back to Attendance
            </Button>
        </div>
    </AppLayout>
</template>
