<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <Link
          :href="route('inventory.suppliers.index')"
          class="text-gray-500 hover:text-gray-700"
        >
          ← Back
        </Link>
        <div>
          <h1 class="text-2xl font-bold text-gray-900">{{ supplier.name }}</h1>
          <p class="text-gray-500">Supplier Details</p>
        </div>
      </div>
      <div class="flex space-x-3">
        <Link
          :href="route('inventory.suppliers.edit', supplier.id)"
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          Edit
        </Link>
      </div>
    </div>

    <!-- Supplier Details -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
          <dl class="space-y-3">
            <div>
              <dt class="text-sm text-gray-500">Supplier Name</dt>
              <dd class="text-sm font-medium text-gray-900">{{ supplier.name }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Contact Person</dt>
              <dd class="text-sm font-medium text-gray-900">{{ supplier.contact_person || '-' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Phone</dt>
              <dd class="text-sm font-medium text-gray-900">{{ supplier.phone || '-' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Email</dt>
              <dd class="text-sm font-medium text-gray-900">{{ supplier.email || '-' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Address</dt>
              <dd class="text-sm font-medium text-gray-900">{{ supplier.address || '-' }}</dd>
            </div>
          </dl>
        </div>
        <div>
          <h3 class="text-lg font-medium text-gray-900 mb-4">Financial Details</h3>
          <dl class="space-y-3">
            <div>
              <dt class="text-sm text-gray-500">Tax Number</dt>
              <dd class="text-sm font-medium text-gray-900">{{ supplier.tax_number || '-' }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Opening Balance</dt>
              <dd class="text-sm font-medium" :class="supplier.opening_balance >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ formatCurrency(supplier.opening_balance) }}
              </dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Campus</dt>
              <dd class="text-sm font-medium text-gray-900">{{ supplier.campus?.name }}</dd>
            </div>
            <div>
              <dt class="text-sm text-gray-500">Status</dt>
              <dd>
                <span
                  :class="[
                    'px-2 py-1 text-xs font-medium rounded-full',
                    supplier.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
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
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm text-gray-500">Total Purchases</div>
        <div class="text-2xl font-bold text-gray-900">{{ props.summary.total_purchases }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm text-gray-500">Total Amount</div>
        <div class="text-2xl font-bold text-gray-900">{{ formatCurrency(props.summary.total_amount) }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="text-sm text-gray-500">Created</div>
        <div class="text-2xl font-bold text-gray-900">{{ formatDate(supplier.created_at) }}</div>
      </div>
    </div>

    <!-- Recent Purchases -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Recent Purchases</h3>
      </div>
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Purchase Date</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="purchase in supplier.purchases" :key="purchase.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ new Date(purchase.purchase_date).toLocaleDateString() }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ formatCurrency(purchase.total_amount) }}
            </td>
          </tr>
          <tr v-if="!supplier.purchases || supplier.purchases.length === 0">
            <td colspan="2" class="px-6 py-8 text-center text-gray-500">
              No purchases found.
            </td>
          </tr>
        </tbody>
      </table>
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
