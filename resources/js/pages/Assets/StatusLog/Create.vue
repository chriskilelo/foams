<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ArrowLeft, Camera, MapPin, Paperclip, Wifi } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import StatusLogController from '@/actions/App/Http/Controllers/Assets/StatusLogController';
import AssetController from '@/actions/App/Http/Controllers/Assets/AssetController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import type { BreadcrumbItem } from '@/types';

type County = { id: number; name: string; code: string; region: { id: number; name: string } };
type Asset = {
    id: number;
    asset_code: string;
    name: string;
    type: string;
    status: string;
    location_name: string;
    county: County;
};
type StatusOption = { value: string; label: string; color: string };

const props = defineProps<{
    asset: Asset;
    is_amendment: boolean;
    statuses: StatusOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Status Logs', href: StatusLogController.index.url() },
    { title: props.asset.asset_code, href: AssetController.show.url(props.asset.id) },
    {
        title: props.is_amendment ? 'Amend Log' : 'Log Status',
        href: StatusLogController.create.url(props.asset.id),
    },
];

const form = useForm({
    status: '',
    remarks: '',
    observed_at: '',
    throughput_mbps: '',
    latitude: '',
    longitude: '',
    amendment_reason: '',
});

function submit() {
    form
        .transform((data) => ({
            ...data,
            observed_at: data.observed_at || undefined,
            throughput_mbps: data.throughput_mbps || undefined,
            latitude: data.latitude || undefined,
            longitude: data.longitude || undefined,
            amendment_reason: data.amendment_reason || undefined,
        }))
        .post(StatusLogController.store.url(props.asset.id));
}

// ─── GPS Capture ──────────────────────────────────────────────────────────────

const gpsLoading = ref(false);
const gpsError = ref<string | null>(null);

function captureGps() {
    if (!navigator.geolocation) {
        gpsError.value = 'Geolocation is not supported by your browser.';
        return;
    }

    gpsLoading.value = true;
    gpsError.value = null;

    navigator.geolocation.getCurrentPosition(
        (position) => {
            form.latitude = position.coords.latitude.toFixed(7);
            form.longitude = position.coords.longitude.toFixed(7);
            gpsLoading.value = false;
        },
        (error) => {
            gpsError.value = `GPS error: ${error.message}`;
            gpsLoading.value = false;
        },
        { enableHighAccuracy: true, timeout: 10000 },
    );
}

// ─── Status button colours ────────────────────────────────────────────────────

function statusButtonClass(status: StatusOption, selected: boolean): string {
    const baseMap: Record<string, string> = {
        green: 'border-green-300 text-green-800',
        amber: 'border-amber-300 text-amber-800',
        red: 'border-red-300 text-red-800',
        blue: 'border-blue-300 text-blue-800',
    };
    const selectedMap: Record<string, string> = {
        green: 'bg-green-100 border-green-400 ring-2 ring-green-300',
        amber: 'bg-amber-100 border-amber-400 ring-2 ring-amber-300',
        red: 'bg-red-100 border-red-400 ring-2 ring-red-300',
        blue: 'bg-blue-100 border-blue-400 ring-2 ring-blue-300',
    };
    const base = baseMap[status.color] ?? 'border-gray-300 text-gray-800';
    const sel = selectedMap[status.color] ?? 'bg-gray-100 border-gray-400';
    return `rounded-xl border-2 px-4 py-3 text-sm font-medium transition-all cursor-pointer ${base} ${selected ? sel : 'bg-card hover:bg-gray-50'}`;
}

function statusDotClass(color: string): string {
    const map: Record<string, string> = {
        green: 'bg-green-500',
        amber: 'bg-amber-500',
        red: 'bg-red-500',
        blue: 'bg-blue-500',
    };
    return `size-2.5 rounded-full shrink-0 ${map[color] ?? 'bg-gray-400'}`;
}

const remarksLength = computed(() => form.remarks.length);
const typeLabels: Record<string, string> = {
    wifi_hotspot: 'Public WiFi Hotspot',
    nofbi_node: 'NOFBI Node',
    ogn_equipment: 'OGN Equipment',
};
</script>

<template>
    <Head :title="`${is_amendment ? 'Amend Log' : 'Log Status'} — ${asset.asset_code}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <!-- Page Header -->
            <div class="mb-6 flex items-start gap-3">
                <Button as-child variant="ghost" size="icon-sm" class="mt-0.5">
                    <a :href="StatusLogController.index.url()">
                        <ArrowLeft class="size-4" />
                        <span class="sr-only">Back</span>
                    </a>
                </Button>
                <div>
                    <h1 class="text-xl font-semibold text-foreground">
                        {{ is_amendment ? 'Amend Status Log' : 'Log Asset Status' }}
                    </h1>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ is_amendment ? 'A log already exists for today. Your entry will be recorded as an amendment.' : "Record today's operational status for this asset." }}
                    </p>
                </div>
            </div>

            <!-- Amendment Notice -->
            <div
                v-if="is_amendment"
                class="mb-6 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4"
            >
                <Wifi class="mt-0.5 size-5 shrink-0 text-amber-600" />
                <div class="text-sm text-amber-800">
                    <p class="font-medium">Amendment Required</p>
                    <p class="mt-0.5">
                        You have already submitted a log for this asset today. This submission will
                        create an amendment row and requires an explanation.
                    </p>
                </div>
            </div>

            <!-- Asset Info Card -->
            <div class="mb-6 rounded-xl border border-border bg-[#EEF4FB] p-4">
                <div class="flex items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-[#D6E4F7]">
                        <Wifi class="size-5 text-[#1F3864]" />
                    </div>
                    <div>
                        <p class="font-mono text-sm font-bold text-[#1F3864]">{{ asset.asset_code }}</p>
                        <p class="text-sm font-medium text-foreground">{{ asset.name }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ typeLabels[asset.type] ?? asset.type }} ·
                            {{ asset.location_name }} · {{ asset.county.name }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="flex flex-col gap-6">
                <!-- Status Selector -->
                <div>
                    <Label class="mb-2 block">
                        Observed Status <span class="text-destructive">*</span>
                    </Label>
                    <div class="grid grid-cols-2 gap-3">
                        <button
                            v-for="s in statuses"
                            :key="s.value"
                            type="button"
                            :class="statusButtonClass(s, form.status === s.value)"
                            @click="form.status = s.value"
                        >
                            <span class="flex items-center gap-2">
                                <span :class="statusDotClass(s.color)" />
                                {{ s.label }}
                            </span>
                        </button>
                    </div>
                    <InputError :message="form.errors.status" class="mt-1" />
                </div>

                <!-- Observed At -->
                <div>
                    <Label for="observed_at">Observed At <span class="text-xs text-muted-foreground">(optional)</span></Label>
                    <Input
                        id="observed_at"
                        v-model="form.observed_at"
                        type="time"
                        class="mt-1"
                    />
                    <InputError :message="form.errors.observed_at" class="mt-1" />
                </div>

                <!-- Throughput -->
                <div>
                    <Label for="throughput_mbps">Throughput (Mbps) <span class="text-xs text-muted-foreground">(optional)</span></Label>
                    <Input
                        id="throughput_mbps"
                        v-model="form.throughput_mbps"
                        type="number"
                        min="0"
                        max="99999.99"
                        step="0.01"
                        placeholder="e.g. 45.50"
                        class="mt-1"
                    />
                    <InputError :message="form.errors.throughput_mbps" class="mt-1" />
                </div>

                <!-- Remarks -->
                <div>
                    <Label for="remarks">
                        Remarks
                        <span class="text-xs text-muted-foreground">(optional, max 500 chars)</span>
                    </Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        maxlength="500"
                        rows="3"
                        placeholder="Describe what you observed..."
                        class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <div class="mt-1 flex items-center justify-between">
                        <InputError :message="form.errors.remarks" />
                        <span class="text-xs text-muted-foreground">{{ remarksLength }}/500</span>
                    </div>
                </div>

                <!-- GPS Capture -->
                <div>
                    <Label>GPS Coordinates <span class="text-xs text-muted-foreground">(optional)</span></Label>
                    <div class="mt-1 flex items-center gap-2">
                        <Input
                            v-model="form.latitude"
                            type="number"
                            step="0.0000001"
                            min="-90"
                            max="90"
                            placeholder="Latitude"
                            class="flex-1"
                        />
                        <Input
                            v-model="form.longitude"
                            type="number"
                            step="0.0000001"
                            min="-180"
                            max="180"
                            placeholder="Longitude"
                            class="flex-1"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            :disabled="gpsLoading"
                            @click="captureGps"
                        >
                            <MapPin class="size-4" />
                            {{ gpsLoading ? 'Getting…' : 'Capture GPS' }}
                        </Button>
                    </div>
                    <p v-if="gpsError" class="mt-1 text-xs text-destructive">{{ gpsError }}</p>
                    <div class="mt-1 flex gap-3">
                        <InputError :message="form.errors.latitude" />
                        <InputError :message="form.errors.longitude" />
                    </div>
                </div>

                <!-- Amendment Reason -->
                <div v-if="is_amendment">
                    <Label for="amendment_reason">
                        Amendment Reason <span class="text-destructive">*</span>
                    </Label>
                    <textarea
                        id="amendment_reason"
                        v-model="form.amendment_reason"
                        maxlength="500"
                        rows="2"
                        placeholder="Explain why you are amending today's log..."
                        class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                    />
                    <InputError :message="form.errors.amendment_reason" class="mt-1" />
                </div>

                <!-- Photo Upload Placeholder -->
                <div class="rounded-xl border border-dashed border-border bg-card p-5 text-center">
                    <Camera class="mx-auto mb-2 size-8 text-muted-foreground/50" />
                    <p class="text-sm font-medium text-muted-foreground">Photo Upload</p>
                    <p class="mt-1 text-xs text-muted-foreground/70">
                        Photo attachments will be available in Phase 2.
                    </p>
                    <Button type="button" variant="outline" size="sm" class="mt-3" disabled>
                        <Paperclip class="size-4" />
                        Attach Photo
                    </Button>
                </div>

                <!-- Submit -->
                <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
                    <Button as-child variant="outline">
                        <a :href="StatusLogController.index.url()">Cancel</a>
                    </Button>
                    <Button type="submit" :disabled="form.processing || !form.status">
                        {{ form.processing ? 'Saving…' : (is_amendment ? 'Submit Amendment' : 'Submit Log') }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
