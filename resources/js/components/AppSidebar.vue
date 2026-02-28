<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    BarChart3,
    ClipboardList,
    FileText,
    LayoutGrid,
    MapPin,
    ScrollText,
    Settings,
    Shield,
    Users,
    Wifi,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import RegionController from '@/actions/App/Http/Controllers/Admin/RegionController';
import CountyController from '@/actions/App/Http/Controllers/Admin/CountyController';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user);
const roles = computed(() => user.value?.roles ?? []);

const hasRole = (role: string) => roles.value.includes(role);
const hasAnyRole = (...roleList: string[]) => roleList.some((r) => hasRole(r));

const navItems = computed((): NavItem[] => {
    if (hasRole('admin')) {
        return [
            { title: 'Dashboard', href: dashboard.url(), icon: LayoutGrid },
            { title: 'Assets', href: '#', icon: Wifi },
            { title: 'Regions', href: RegionController.index.url(), icon: MapPin },
            { title: 'Counties', href: CountyController.index.url(), icon: MapPin },
            { title: 'Users', href: '#', icon: Users },
            { title: 'SLA Config', href: '#', icon: Settings },
            { title: 'Audit Log', href: '#', icon: ScrollText },
        ];
    }

    if (hasAnyRole('director', 'noc')) {
        return [
            { title: 'Dashboard', href: dashboard.url(), icon: LayoutGrid },
            { title: 'Issues', href: '#', icon: AlertCircle },
            { title: 'Reports', href: '#', icon: BarChart3 },
        ];
    }

    if (hasRole('ricto')) {
        return [
            { title: 'Dashboard', href: dashboard.url(), icon: LayoutGrid },
            { title: 'Issues', href: '#', icon: AlertCircle },
            { title: 'Assets', href: '#', icon: Wifi },
            { title: 'Officers', href: '#', icon: Users },
            { title: 'Reports', href: '#', icon: BarChart3 },
        ];
    }

    if (hasAnyRole('icto', 'aicto')) {
        return [
            { title: 'My Assets', href: '#', icon: Wifi },
            { title: 'Log Status', href: '#', icon: ClipboardList },
            { title: 'Issues', href: '#', icon: AlertCircle },
        ];
    }

    return [
        { title: 'Dashboard', href: dashboard.url(), icon: LayoutGrid },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard.url()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="navItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
