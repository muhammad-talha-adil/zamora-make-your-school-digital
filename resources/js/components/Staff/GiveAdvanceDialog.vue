<script setup lang="ts">
import axios from 'axios';
import { reactive } from 'vue';
import { route } from 'ziggy-js';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { alert } from '@/utils';

/**
 * Give-an-advance form, shared by the Payroll screen and a staff member's
 * own profile page so neither has to keep its own copy of this markup.
 */
interface Props {
    open: boolean;
    staffProfileId: number | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'given'): void;
}>();

const form = reactive({
    amount: '',
    disbursed_date: new Date().toISOString().slice(0, 10),
    monthly_deduction_amount: '',
    notes: '',
});

const close = () => emit('update:open', false);

const submit = async () => {
    if (!props.staffProfileId) return;

    try {
        await axios.post(route('staff.advances.store'), {
            staff_profile_id: props.staffProfileId,
            amount: form.amount,
            disbursed_date: form.disbursed_date,
            monthly_deduction_amount: form.monthly_deduction_amount || null,
            notes: form.notes || null,
        });
        alert.success('Advance disbursed successfully.');
        form.amount = '';
        form.monthly_deduction_amount = '';
        form.notes = '';
        emit('given');
        close();
    } catch (error: any) {
        alert.error(error?.response?.data?.message || 'Failed to disburse advance.');
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Give Advance</DialogTitle>
            </DialogHeader>
            <div class="space-y-4 py-2">
                <div class="space-y-2">
                    <Label for="advance-amount">Amount</Label>
                    <Input id="advance-amount" v-model="form.amount" type="number" min="0.01" step="0.01" placeholder="Advance amount" />
                </div>
                <div class="space-y-2">
                    <Label for="advance-date">Disbursed Date</Label>
                    <Input id="advance-date" v-model="form.disbursed_date" type="date" />
                </div>
                <div class="space-y-2">
                    <Label for="advance-deduction">Monthly Deduction <span class="text-muted-foreground font-normal">(optional)</span></Label>
                    <Input id="advance-deduction" v-model="form.monthly_deduction_amount" type="number" min="0.01" step="0.01" placeholder="Leave blank to deduct in full next payroll" />
                </div>
                <div class="space-y-2">
                    <Label for="advance-notes">Notes <span class="text-muted-foreground font-normal">(optional)</span></Label>
                    <Input id="advance-notes" v-model="form.notes" placeholder="Reason for the advance" />
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="close">Cancel</Button>
                <Button @click="submit">Give Advance</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
