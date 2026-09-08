<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Artisan Commands" />

        <div class="space-y-6 p-4 md:p-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-foreground">
                        Artisan Commands
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Run Laravel artisan commands from the UI
                    </p>
                </div>
                <div class="text-sm text-warning bg-warning/10 px-3 py-1 rounded">
                    ⚠️ Development Mode Only
                </div>
            </div>

            <!-- Alert Messages -->
            <div v-if="successMessage" class="bg-success/10 border border-success/40 rounded-lg p-4">
                <div class="flex items-center gap-2">
                    <Icon icon="check-circle" class="h-5 w-5 text-success" />
                    <span class="text-success">{{ successMessage }}</span>
                </div>
            </div>

            <div v-if="errorMessage" class="bg-destructive/10 border border-destructive/40 rounded-lg p-4">
                <div class="flex items-center gap-2">
                    <Icon icon="alert-circle" class="h-5 w-5 text-destructive" />
                    <span class="text-destructive">{{ errorMessage }}</span>
                </div>
            </div>

            <!-- Command Output -->
            <div v-if="commandOutput" class="bg-card rounded-lg p-4">
                <div class="flex flex-wrap gap-2 items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-muted-foreground">Command Output</h3>
                    <button @click="commandOutput = ''" class="text-muted-foreground hover:text-foreground">
                        <Icon icon="x" class="h-4 w-4" />
                    </button>
                </div>
                <pre class="text-success text-sm overflow-x-auto whitespace-pre-wrap">{{ commandOutput }}</pre>
            </div>

            <!-- Main Grid -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Column 1: Database Commands -->
                <div class="space-y-6">
                    <!-- Migrations -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <Icon icon="database" class="h-5 w-5 text-primary" />
                            </div>
                            <h2 class="text-lg font-semibold text-foreground">Migrations</h2>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('migrate')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingMigrate" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Migrate
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('migrateForce')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingMigrateForce" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Migrate (Force)
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('migrateFresh')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingMigrateFresh" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Migrate Fresh
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('migrateFreshSeed')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingMigrateFreshSeed" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Migrate Fresh + Seed
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('migrateRollback')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingMigrateRollback" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Rollback
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('migrateReset')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingMigrateReset" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Reset
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('migrateStatus')"
                                :disabled="processing"
                                class="col-span-2"
                            >
                                <Icon v-if="processingMigrateStatus" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Status
                            </Button>
                        </div>
                    </div>

                    <!-- Seeders -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-success/10 rounded-lg">
                                <Icon icon="plant" class="h-5 w-5 text-success" />
                            </div>
                            <h2 class="text-lg font-semibold text-foreground">Seeders</h2>
                        </div>
                        
                        <div class="space-y-3">
                            <!-- Run All Seeders -->
                            <Button 
                                variant="secondary" 
                                class="w-full" 
                                @click="runCommand('dbSeed')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingDbSeed" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Run All Seeders
                            </Button>
                            
                            <!-- Divider -->
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-px bg-muted"></div>
                                <span class="text-xs text-muted-foreground">OR</span>
                                <div class="flex-1 h-px bg-muted"></div>
                            </div>
                            
                            <!-- Run Specific Seeder -->
                            <div class="space-y-2">
                                <Label>Run Specific Seeder</Label>
                                <select 
                                    v-model="selectedSeeder"
                                    class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                                >
                                    <option value="">Select a seeder...</option>
                                    <option v-for="seeder in seeders" :key="seeder" :value="seeder">
                                        {{ seeder }}
                                    </option>
                                </select>
                                <Button 
                                    variant="outline" 
                                    class="w-full" 
                                    @click="runSpecificSeeder"
                                    :disabled="!selectedSeeder || processing"
                                >
                                    <Icon v-if="processingRunSeeder" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                    Run Selected Seeder
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Migrations -->
                    <div v-if="pendingMigrations.length > 0" class="bg-card rounded-lg border border-warning/40 p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-warning/10 rounded-lg">
                                <Icon icon="alert-triangle" class="h-5 w-5 text-warning" />
                            </div>
                            <h2 class="text-lg font-semibold text-foreground">Pending Migrations</h2>
                            <span class="bg-warning/10 text-warning text-xs px-2 py-1 rounded-full">
                                {{ pendingMigrations.length }} pending
                            </span>
                        </div>
                        
                        <div class="space-y-2">
                            <div class="max-h-40 overflow-y-auto space-y-1 bg-muted p-2 rounded">
                                <div v-for="migration in pendingMigrations" :key="migration" class="text-xs text-muted-foreground font-mono">
                                    {{ migration }}
                                </div>
                            </div>
                            
                            <!-- Run Specific Migration -->
                            <div class="space-y-2 pt-2">
                                <Label>Run Specific Migration</Label>
                                <select 
                                    v-model="selectedMigration"
                                    class="w-full rounded-md border border-border bg-card px-3 py-2 text-sm text-foreground"
                                >
                                    <option value="">Select a migration...</option>
                                    <option v-for="migration in pendingMigrations" :key="migration" :value="migration">
                                        {{ migration }}
                                    </option>
                                </select>
                                <Button 
                                    variant="warning" 
                                    class="w-full" 
                                    @click="runSpecificMigration"
                                    :disabled="!selectedMigration || processing"
                                >
                                    <Icon v-if="processingRunMigration" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                    Migrate Selected
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Cache & Other Commands -->
                <div class="space-y-6">
                    <!-- Cache -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-primary/10 rounded-lg">
                                <Icon icon="zap" class="h-5 w-5 text-primary" />
                            </div>
                            <h2 class="text-lg font-semibold text-foreground">Cache</h2>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('clearCache')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingClearCache" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Clear Cache
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('rebuildCache')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingRebuildCache" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Rebuild Cache
                            </Button>
                        </div>
                    </div>

                    <!-- Queue -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-warning/10 rounded-lg">
                                <Icon icon="layers" class="h-5 w-5 text-warning" />
                            </div>
                            <h2 class="text-lg font-semibold text-foreground">Queue</h2>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('queueWork')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingQueueWork" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Work
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('queueClear')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingQueueClear" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Clear
                            </Button>
                            <Button 
                                variant="outline" 
                                size="sm" 
                                @click="runCommand('queueRestart')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingQueueRestart" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Restart
                            </Button>
                        </div>
                    </div>

                    <!-- Other Commands -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-info/10 rounded-lg">
                                <Icon icon="terminal" class="h-5 w-5 text-info" />
                            </div>
                            <h2 class="text-lg font-semibold text-foreground">Other Commands</h2>
                        </div>
                        <div class="space-y-3">
                            <Button 
                                variant="outline" 
                                class="w-full" 
                                @click="runCommand('routeList')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingRouteList" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                List Routes
                            </Button>
                            <Button 
                                variant="outline" 
                                class="w-full" 
                                @click="runCommand('vendorPublish')"
                                :disabled="processing"
                            >
                                <Icon v-if="processingVendorPublish" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Vendor Publish
                            </Button>
                        </div>
                    </div>

                    <!-- Custom Command -->
                    <div class="bg-card rounded-lg border border-border p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="p-2 bg-destructive/10 rounded-lg">
                                <Icon icon="command" class="h-5 w-5 text-destructive" />
                            </div>
                            <h2 class="text-lg font-semibold text-foreground">Custom Command</h2>
                        </div>
                        <div class="space-y-2">
                            <Input 
                                v-model="customCommand" 
                                placeholder="e.g. make:model User"
                                class="w-full"
                            />
                            <Button 
                                variant="destructive" 
                                class="w-full" 
                                @click="runCustomCommand"
                                :disabled="!customCommand || processing"
                            >
                                <Icon v-if="processingCustom" icon="loader" class="mr-2 h-4 w-4 animate-spin" />
                                Run Command
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import { route } from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

interface Props {
    seeders?: string[];
    pendingMigrations?: string[];
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Artisan Commands', href: '/artisan' },
];

// State
const processing = ref(false);
const processingMigrate = ref(false);
const processingMigrateForce = ref(false);
const processingMigrateFresh = ref(false);
const processingMigrateFreshSeed = ref(false);
const processingMigrateRollback = ref(false);
const processingMigrateReset = ref(false);
const processingMigrateStatus = ref(false);
const processingDbSeed = ref(false);
const processingRunSeeder = ref(false);
const processingClearCache = ref(false);
const processingRebuildCache = ref(false);
const processingQueueWork = ref(false);
const processingQueueClear = ref(false);
const processingQueueRestart = ref(false);
const processingRouteList = ref(false);
const processingVendorPublish = ref(false);
const processingCustom = ref(false);
const processingRunMigration = ref(false);

const seeders = ref<string[]>(props.seeders || []);
const selectedSeeder = ref('');
const customCommand = ref('');
const successMessage = ref('');
const errorMessage = ref('');
const commandOutput = ref('');

// Pending migrations from backend
const pendingMigrations = ref<string[]>(props.pendingMigrations || []);
const selectedMigration = ref('');

// Load seeders on mount
onMounted(async () => {
    // Seeders are passed via props from controller
});

const runCommand = async (command: string) => {
    // Set processing state
    setProcessing(command, true);
    successMessage.value = '';
    errorMessage.value = '';
    commandOutput.value = '';

    // Map camelCase command names to dot notation route names
    const commandToRouteMap: Record<string, string> = {
        'migrate': 'artisan.migrate',
        'migrateForce': 'artisan.migrate.force',
        'migrateFresh': 'artisan.migrate.fresh',
        'migrateFreshSeed': 'artisan.migrate.fresh.seed',
        'migrateRollback': 'artisan.migrate.rollback',
        'migrateReset': 'artisan.migrate.reset',
        'migrateStatus': 'artisan.migrate.status',
        'dbSeed': 'artisan.db.seed',
        'clearCache': 'artisan.cache.clear',
        'rebuildCache': 'artisan.cache.rebuild',
        'queueWork': 'artisan.queue.work',
        'queueClear': 'artisan.queue.clear',
        'queueRestart': 'artisan.queue.restart',
        'routeList': 'artisan.routes.list',
        'vendorPublish': 'artisan.vendor.publish',
    };

    const routeName = commandToRouteMap[command] || `artisan.${command}`;
    
    try {
        await router.post(route(routeName), {}, {
            onSuccess: (page) => {
                // Get flash data
                const props = page.props as any;
                if (props.success) {
                    successMessage.value = props.success;
                }
                if (props.error) {
                    errorMessage.value = props.error;
                }
                if (props.output) {
                    commandOutput.value = props.output;
                }
            },
            onError: (errors) => {
                errorMessage.value = Object.values(errors).join(', ');
            },
            onFinish: () => {
                setProcessing(command, false);
            }
        });
    } catch (e: any) {
        errorMessage.value = e.message || 'Command failed';
        setProcessing(command, false);
    }
};

const runSpecificSeeder = async () => {
    if (!selectedSeeder.value) return;
    
    processingRunSeeder.value = true;
    successMessage.value = '';
    errorMessage.value = '';
    commandOutput.value = '';

    try {
        await router.post(route('artisan.seed.run', selectedSeeder.value), {}, {
            onSuccess: (page) => {
                const props = page.props as any;
                if (props.success) {
                    successMessage.value = props.success;
                }
                if (props.error) {
                    errorMessage.value = props.error;
                }
                if (props.output) {
                    commandOutput.value = props.output;
                }
            },
            onError: (errors) => {
                errorMessage.value = Object.values(errors).join(', ');
            },
            onFinish: () => {
                processingRunSeeder.value = false;
            }
        });
    } catch (e: any) {
        errorMessage.value = e.message || 'Seeder failed';
        processingRunSeeder.value = false;
    }
};

// Run specific migration
const runSpecificMigration = async () => {
    if (!selectedMigration.value) return;
    
    processingRunMigration.value = true;
    successMessage.value = '';
    errorMessage.value = '';
    commandOutput.value = '';

    try {
        await router.post(route('artisan.migrate.single'), { 
            migration: selectedMigration.value 
        }, {
            onSuccess: (page) => {
                const props = page.props as any;
                if (props.success) {
                    successMessage.value = props.success;
                }
                if (props.error) {
                    errorMessage.value = props.error;
                }
                if (props.output) {
                    commandOutput.value = props.output;
                }
            },
            onError: (errors) => {
                errorMessage.value = Object.values(errors).join(', ');
            },
            onFinish: () => {
                processingRunMigration.value = false;
            }
        });
    } catch (e: any) {
        errorMessage.value = e.message || 'Migration failed';
        processingRunMigration.value = false;
    }
};

const runCustomCommand = async () => {
    if (!customCommand.value) return;
    
    processingCustom.value = true;
    successMessage.value = '';
    errorMessage.value = '';
    commandOutput.value = '';

    try {
        await router.post(route('artisan.run.command', customCommand.value), {}, {
            onSuccess: (page) => {
                const props = page.props as any;
                if (props.success) {
                    successMessage.value = props.success;
                }
                if (props.error) {
                    errorMessage.value = props.error;
                }
                if (props.output) {
                    commandOutput.value = props.output;
                }
            },
            onError: (errors) => {
                errorMessage.value = Object.values(errors).join(', ');
            },
            onFinish: () => {
                processingCustom.value = false;
            }
        });
    } catch (e: any) {
        errorMessage.value = e.message || 'Command failed';
        processingCustom.value = false;
    }
};

const setProcessing = (command: string, value: boolean) => {
    switch (command) {
        case 'migrate':
            processingMigrate.value = value;
            break;
        case 'migrateForce':
            processingMigrateForce.value = value;
            break;
        case 'migrateFresh':
            processingMigrateFresh.value = value;
            break;
        case 'migrateFreshSeed':
            processingMigrateFreshSeed.value = value;
            break;
        case 'migrateRollback':
            processingMigrateRollback.value = value;
            break;
        case 'migrateReset':
            processingMigrateReset.value = value;
            break;
        case 'migrateStatus':
            processingMigrateStatus.value = value;
            break;
        case 'dbSeed':
            processingDbSeed.value = value;
            break;
        case 'clearCache':
            processingClearCache.value = value;
            break;
        case 'rebuildCache':
            processingRebuildCache.value = value;
            break;
        case 'queueWork':
            processingQueueWork.value = value;
            break;
        case 'queueClear':
            processingQueueClear.value = value;
            break;
        case 'queueRestart':
            processingQueueRestart.value = value;
            break;
        case 'routeList':
            processingRouteList.value = value;
            break;
        case 'vendorPublish':
            processingVendorPublish.value = value;
            break;
    }
    processing.value = value;
};
</script>