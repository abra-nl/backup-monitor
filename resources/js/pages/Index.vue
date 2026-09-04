<script setup>
import { ref } from 'vue';
import { Badge, Button, Card, Header } from '@statamic/cms/ui';
import { router, usePoll } from '@statamic/cms/inertia';
import { toast } from '@statamic/cms/api';

const props = defineProps({
    disks: Array,
    triggerUrl: String,
});

const triggering = ref({});

function ucfirst(value) {
    return value.charAt(0).toUpperCase() + value.slice(1);
}

function healthBadge(disk) {
    if (!disk.monitored) {
        return { color: 'default', text: __('Not monitored') };
    }

    return disk.isHealthy
        ? { color: 'green', text: __('Healthy') }
        : { color: 'red', text: __('Unhealthy') };
}

function triggerBackup(disk) {
    triggering.value[disk.disk] = true;

    router.post(props.triggerUrl, { disk: disk.disk }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => toast.success(__('Backup started for :disk.', { disk: disk.disk })),
        onError: () => toast.error(__('Unable to start backup for :disk.', { disk: disk.disk })),
        onFinish: () => { triggering.value[disk.disk] = false; },
    });
}

usePoll(15000);
</script>

<template>
    <div>
        <Header :title="__('Backup Monitor')" />

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <Card v-for="disk in disks" :key="disk.disk">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <h2 class="text-lg font-medium">{{ ucfirst(disk.disk) }}</h2>
                        <Badge v-bind="healthBadge(disk)" />
                        <Badge v-if="!disk.isReachable" color="red" :text="__('Unreachable')" />
                    </div>
                    <Button
                        :text="__('Run backup')"
                        variant="primary"
                        size="sm"
                        :loading="triggering[disk.disk]"
                        :disabled="triggering[disk.disk]"
                        @click="triggerBackup(disk)"
                    />
                </div>

                <p v-if="disk.connectionError" class="text-sm text-red-600 mb-4">
                    {{ disk.connectionError }}
                </p>

                <ul v-if="disk.monitored && !disk.isHealthy" class="mb-4 space-y-1">
                    <li
                        v-for="failure in disk.failureMessages"
                        :key="failure.check"
                        class="text-sm text-red-600"
                    >
                        [{{ failure.check }}] {{ failure.message }}
                    </li>
                </ul>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-gray-500">
                                <th class="py-1 pr-4 font-medium">{{ __('Date') }}</th>
                                <th class="py-1 font-medium">{{ __('Size') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="disk.backups.length === 0">
                                <td colspan="2" class="py-2 text-gray-500">{{ __('No backups found.') }}</td>
                            </tr>
                            <tr v-for="backup in disk.backups" :key="backup.path" class="border-t">
                                <td class="py-1 pr-4">{{ new Date(backup.date).toLocaleString() }}</td>
                                <td class="py-1">{{ backup.sizeHuman }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </div>
</template>
