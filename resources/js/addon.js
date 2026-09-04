import Index from './pages/Index.vue';

Statamic.booting(() => {
    Statamic.$inertia.register('backup-monitor::Index', Index);
});
