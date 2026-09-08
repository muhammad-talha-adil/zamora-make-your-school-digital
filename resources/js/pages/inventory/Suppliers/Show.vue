<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-wrap gap-2 items-center justify-between">
      <div class="flex items-center space-x-4">
        <Link
          :href="route('inventory.suppliers.index')"
          class="text-muted-foreground hover:text-foreground"
        >
          ← Back
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-foreground">{{ supplier.name }}</h1>
          <p class="text-muted-foreground">Supplier Details</p>
        </div>
      </div>
      <div class="flex space-x-3">
        <Link
          :href="route('inventory.suppliers.edit', supplier.id)"
          class="px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 transition-colors"
        >
          Edit
        </Link>
      </div>
    </div>

    <!-- Supplier Details -->
    <div class="bg-card rounded-lg shadow-sm p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-lg font-medium text-foreground mb-4">Basic Information</h3>
          <dl class="space-y-3">
            <div>
              <dt class="text-sm text-muted-foreground">Supplier Name</dt>
              <dd class="text-sm font-medium text-foreground">{{ supplier.name }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Contact Person</dt>
              <dd class="text-sm font-medium text-foreground">{{ supplier.contact_person || '-' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Phone</dt>
              <dd class="text-sm font-medium text-foreground">{{ supplier.phone || '-' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Email</dt>
              <dd class="text-sm font-medium text-foreground">{{ supplier.email || '-' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Address</dt>
              <dd class="text-sm font-medium text-foreground">{{ supplier.address || '-' }}</dd>
            </div>
          </dl>
        </div>
        <div>
          <h3 class="text-lg font-medium text-foreground mb-4">Financial Details</h3>
          <dl class="space-y-3">
            <div>
              <dt class="text-sm text-muted-foreground">Tax Number</dt>
              <dd class="text-sm font-medium text-foreground">{{ supplier.tax_number || '-' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Opening Balance</dt>
              <dd class="text-sm font-medium" :class="supplier.opening_balance >= 0 ? 'text-success' : 'text-destructive'">
                {{ formatCurrency(supplier.opening_balance) }}
              </dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Campus</dt>
              <dd class="text-sm font-medium text-foreground">{{ supplier.campus?.name }}</dd>
            </div>
            <div>
              <dt class="text-sm text-muted-foreground">Status</dt>
              <dd>
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    supplier.is_active ? 'bg-success/10 text-success' : 'bg-destructive/10 text-destructive'
                  ]"
                >
                  {{ supplier.is_active ? 'Active' : 'Inactive' }}
                </span>
              </dd>
            </div>
          </dl>
        </div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-card rounded-lg shadow-sm p-6">
        <div class="text-sm text-muted-foreground">Total Purchases</div>
        <div class="text-2xl font-bold text-foreground">{{ props.summary.total_purchases }}</div>
      </div>
      <div class="bg-card rounded-lg shadow-sm p-6">
        <div class="text-sm text-muted-foreground">Total Amount</div>
        <div class="text-2xl font-bold text-foreground">{{ formatCurrency(props.summary.total_amount) }}</div>
      </div>
      <div class="bg-card rounded-lg shadow-sm p-6">
        <div class="text-sm text-muted-foreground">Created</div>
        <div class="text-2xl font-bold text-foreground">{{ formatDate(supplier.created_at) }}</div>
      </div>
    </div>

    <!-- Recent Purchases -->
    <div class="bg-card rounded-lg shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-border">
        <h3 class="text-lg font-medium text-foreground">Recent Purchases</h3>
      </div>
      <div class="table-scroll">
        <table class="min-w-full divide-y divide-border">
          <thead class="bg-muted">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Purchase Date</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase">Amount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr v-for="purchase in supplier.purchases" :key="purchase.id" class="hover:bg-accent">
              <td class="px-6 py-4 whitespace-nowrap text-sm text-foreground">
                {{ new Date(purchase.purchase_date).toLocaleDateString() }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-foreground">
                {{ formatCurrency(purchase.total_amount) }}
              </td>
            </tr>
            <tr v-if="!supplier.purchases || supplier.purchases.length === 0">
              <td colspan="2" class="px-6 py-8 text-center text-muted-foreground">
                No purchases found.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps({
  supplier: {
    type: Object,
    required: true,
  },
  summary: {
    type: Object,
    default: () => ({
      total_purchases: 0,
      total_amount: 0,
    }),
  },
})

const formatCurrency = (amount: number) => {
  return new Intl.NumberFormat('en-PK', {
    style: 'currency',
    currency: 'PKR',
  }).format(amount)
}

const formatDate = (date: string) => {
  return new Date(date).toLocaleDateString('en-PK', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}
</script>
