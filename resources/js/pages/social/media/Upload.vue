<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Upload } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { Team } from '@/types';

const page = usePage<{ currentTeam: Team }>();
const teamSlug = computed(() => page.props.currentTeam.slug);

const form = useForm({
    files: [] as File[],
});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const uploadProgress = ref(0);

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        form.files = Array.from(target.files);
    }
};

const handleDrop = (event: DragEvent) => {
    isDragging.value = false;
    if (event.dataTransfer?.files) {
        form.files = Array.from(event.dataTransfer.files);
    }
};

const handleDragOver = () => {
    isDragging.value = true;
};

const handleDragLeave = () => {
    isDragging.value = false;
};

const submit = () => {
    form.post(`/${teamSlug.value}/social/media`, {
        onStart: () => {
            uploadProgress.value = 0;
        },
        onProgress: (progress) => {
            uploadProgress.value = progress.percentage ?? 0;
        },
        onFinish: () => {
            uploadProgress.value = 100;
        },
    });
};

defineOptions({
    layout: (props: { currentTeam: Team }) => ({
        breadcrumbs: [
            { title: 'Social Media', href: `/social` },
            { title: 'Media Library', href: `/${props.currentTeam.slug}/social/media` },
            { title: 'Upload', href: `/${props.currentTeam.slug}/social/media/create` },
        ],
    }),
});
</script>

<template>
    <Head title="Upload Media" />

    <div class="flex flex-col space-y-6">
        <div>
            <h2 class="text-xl font-semibold tracking-tight">Upload Media</h2>
            <p class="text-sm text-muted-foreground">
                Upload images and videos for your social media posts
            </p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Select Files</CardTitle>
                <CardDescription>
                    Supports JPEG, PNG, GIF, WebP images and MP4, MPEG, QuickTime videos. Max 100MB per file.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit">
                    <!-- Drop Zone -->
                    <div
                        :class="[
                            'flex flex-col items-center justify-center rounded-lg border-2 border-dashed p-12 text-center transition-colors',
                            isDragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/25',
                        ]"
                        @drop.prevent="handleDrop"
                        @dragover.prevent="handleDragOver"
                        @dragleave="handleDragLeave"
                        @click="fileInput?.click()"
                    >
                        <Upload class="mb-4 h-8 w-8 text-muted-foreground" />
                        <p class="mb-2 text-sm font-medium">
                            Drag & drop files here, or click to browse
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Max 10 files, 100MB each
                        </p>
                        <input
                            ref="fileInput"
                            type="file"
                            multiple
                            accept="image/*,video/*"
                            class="hidden"
                            @change="handleFileSelect"
                        />
                    </div>

                    <!-- Selected Files -->
                    <div v-if="form.files.length > 0" class="mt-4 space-y-2">
                        <p class="text-sm font-medium">{{ form.files.length }} file(s) selected</p>
                        <div v-for="(file, index) in form.files" :key="index" class="flex items-center justify-between rounded border p-2">
                            <span class="truncate text-sm">{{ file.name }}</span>
                            <span class="text-xs text-muted-foreground">{{ (file.size / 1024 / 1024).toFixed(2) }}MB</span>
                        </div>
                    </div>

                    <!-- Progress -->
                    <div v-if="form.processing" class="mt-4">
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full bg-primary transition-all"
                                :style="{ width: `${uploadProgress}%` }"
                            />
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">Uploading... {{ uploadProgress }}%</p>
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex justify-end gap-2">
                        <Button type="submit" :disabled="form.files.length === 0 || form.processing">
                            <Upload class="mr-2 h-4 w-4" />
                            Upload
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
