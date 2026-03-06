<template>
  <div class="space-y-6">
    <!-- Welcome Section -->
    <div class="bg-linear-to-r from-primary to-primary/80 rounded-lg p-6 text-primary-foreground">
      <h1 class="text-3xl font-bold mb-2">
        Welcome back, {{ $page.props.auth.user?.name || 'User' }}!
      </h1>
      <p class="text-primary-foreground/80">
        Here's what's happening with your school management system today.
      </p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
      <StatsCard title="Total Students" value="150" :icon="UsersIcon" />
      <StatsCard title="Fees Collected" value="Rs 12,500" :icon="CurrencyDollarIcon" />
      <StatsCard title="Active Courses" value="25" :icon="BookOpenIcon" />
      <StatsCard title="Staff Members" value="12" :icon="UserGroupIcon" />
    </div>

    <!-- Recent Activity -->
    <RecentActivity :activities="activities" />
  </div>
</template>

<script setup lang="ts">
import AppLayout from '../components/layout/AppShell.vue'
import StatsCard from '../components/dashboard/StatsCard.vue'
import RecentActivity from '../components/dashboard/RecentActivity.vue'
import { UsersIcon, CurrencyDollarIcon, BookOpenIcon, UserGroupIcon } from '@heroicons/vue/24/outline'

defineOptions({ layout: AppLayout })

const activities = [
  { id: 1, description: 'New student enrolled: John Doe', time: '2 hours ago' },
  { id: 2, description: 'Fee payment received: Rs 500', time: '4 hours ago' },
  { id: 3, description: 'New course added: Mathematics 101', time: '1 day ago' },
]
</script>

