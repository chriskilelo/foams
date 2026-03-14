<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    BarChart3,
    Bell,
    CheckCircle,
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
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import AssetController from '@/actions/App/Http/Controllers/Assets/AssetController';
import SlaConfigurationController from '@/actions/App/Http/Controllers/Admin/SlaConfigurationController';
import AuditLogController from '@/actions/App/Http/Controllers/Admin/AuditLogController';
import IssueController from '@/actions/App/Http/Controllers/Issues/IssueController';
import IssueActivityController from '@/actions/App/Http/Controllers/Issues/IssueActivityController';
import NocPanelController from '@/actions/App/Http/Controllers/Issues/NocPanelController';
import ResolutionController from '@/actions/App/Http/Controllers/Issues/ResolutionController';
import NotificationController from '@/actions/App/Http/Controllers/Notifications/NotificationController';
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
            { title: 'Assets', href: AssetController.index.url(), icon: Wifi },
            { title: 'Issues', href: IssueController.index.url(), icon: AlertCircle },
            { title: 'NOC Panel', href: NocPanelController.url(), icon: Shield },
            { title: 'Issue Activities', href: IssueActivityController.index.url(), icon: Activity },
            { title: 'Resolutions', href: ResolutionController.index.url(), icon: CheckCircle },
            { title: 'Notifications', href: NotificationController.index.url(), icon: Bell },
            { title: 'Regions', href: RegionController.index.url(), icon: MapPin },
            { title: 'Counties', href: CountyController.index.url(), icon: MapPin },
            { title: 'Users', href: UserController.index.url(), icon: Users },
            { title: 'SLA Config', href: SlaConfigurationController.index.url(), icon: Settings },
            { title: 'Audit Log', href: AuditLogController.index.url(), icon: ScrollText },
        ];
    }

    if (hasRole('director')) {
        return [
            { title: 'Dashboard', href: dashboard.url(), icon: LayoutGrid },
            { title: 'Issues', href: IssueController.index.url(), icon: AlertCircle },
            { title: 'NOC Panel', href: NocPanelController.url(), icon: Shield },
            { title: 'Resolutions', href: ResolutionController.index.url(), icon: CheckCircle },
            { title: 'Issue Activities', href: IssueActivityController.index.url(), icon: Activity },
            { title: 'Notifications', href: NotificationController.index.url(), icon: Bell },
            { title: 'Reports', href: '#', icon: BarChart3 },
        ];
    }

    if (hasRole('noc')) {
        return [
            { title: 'Dashboard', href: dashboard.url(), icon: LayoutGrid },
            { title: 'Issues', href: IssueController.index.url(), icon: AlertCircle },
            { title: 'NOC Panel', href: NocPanelController.url(), icon: Shield },
            { title: 'Issue Activities', href: IssueActivityController.index.url(), icon: Activity },
            { title: 'Resolutions', href: ResolutionController.index.url(), icon: CheckCircle },
            { title: 'Notifications', href: NotificationController.index.url(), icon: Bell },
            { title: 'Reports', href: '#', icon: BarChart3 },
        ];
    }

    if (hasRole('ricto')) {
        return [
            { title: 'Dashboard', href: dashboard.url(), icon: LayoutGrid },
            { title: 'Issues', href: IssueController.index.url(), icon: AlertCircle },
            { title: 'Issue Activities', href: IssueActivityController.index.url(), icon: Activity },
            { title: 'Resolutions', href: ResolutionController.index.url(), icon: CheckCircle },
            { title: 'Notifications', href: NotificationController.index.url(), icon: Bell },
            { title: 'Assets', href: AssetController.index.url(), icon: Wifi },
            { title: 'Officers', href: '#', icon: Users },
            { title: 'Reports', href: '#', icon: BarChart3 },
        ];
    }

    if (hasAnyRole('icto', 'aicto')) {
        return [
            { title: 'My Assets', href: AssetController.index.url(), icon: Wifi },
            { title: 'Log Status', href: '#', icon: ClipboardList },
            { title: 'Issues', href: IssueController.index.url(), icon: AlertCircle },
            { title: 'Notifications', href: NotificationController.index.url(), icon: Bell },
        ];
    }

    return [
        { title: 'Dashboard', href: dashboard.url(), icon: LayoutGrid },
        { title: 'Notifications', href: NotificationController.index.url(), icon: Bell },
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
