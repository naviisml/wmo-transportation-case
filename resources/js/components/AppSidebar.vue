<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { BookOpen, Building2, CarFront, LayoutGrid, Users } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from './AppLogo.vue';

const page = usePage();
const role = computed(() => page.props.auth.user?.role);
const mainNavItems = computed<NavItem[]>(() => [
    { title: 'Overview', href: '/dashboard', icon: LayoutGrid },
    ...(role.value === 'municipality' ? [{ title: 'Municipal overview', href: '/municipality', icon: Building2 }] : []),
    ...(role.value === 'callcenter'
        ? [
              { title: 'Residents', href: '/residents', icon: Users },
              { title: 'Book a ride', href: '/book-ride', icon: CarFront },
          ]
        : []),
    ...(role.value === 'taxi_company' ? [{ title: 'Assigned rides', href: '/rides', icon: CarFront }] : []),
]);
const footerNavItems: NavItem[] = [{ title: 'API documentation', href: '/docs/api', icon: BookOpen }];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
