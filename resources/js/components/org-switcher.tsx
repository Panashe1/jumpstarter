import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type Organization, type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Plus, Settings } from 'lucide-react';

export function OrgSwitcher() {
    const { organizations, currentOrganization } = usePage<SharedData>().props;

    if (!currentOrganization) {
        return null;
    }

    const switchTo = (organization: Organization) => {
        if (organization.id !== currentOrganization.id) {
            router.put(route('organizations.switch', organization.id));
        }
    };

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton size="lg" className="data-[state=open]:bg-sidebar-accent">
                            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                <Building2 className="size-4" />
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-medium">{currentOrganization.name}</span>
                                <span className="truncate text-xs capitalize text-muted-foreground">{currentOrganization.role}</span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent className="w-56" align="start">
                        <DropdownMenuLabel className="text-xs text-muted-foreground">Organizations</DropdownMenuLabel>

                        {organizations.map((organization) => (
                            <DropdownMenuItem key={organization.id} onSelect={() => switchTo(organization)}>
                                <span className="truncate">{organization.name}</span>
                                {organization.id === currentOrganization.id && <Check className="ml-auto size-4" />}
                            </DropdownMenuItem>
                        ))}

                        <DropdownMenuSeparator />

                        <DropdownMenuItem asChild>
                            <Link href={route('organizations.edit', currentOrganization.id)}>
                                <Settings className="mr-2 size-4" />
                                Organization settings
                            </Link>
                        </DropdownMenuItem>

                        <DropdownMenuItem asChild>
                            <Link href={route('organizations.create')}>
                                <Plus className="mr-2 size-4" />
                                Create organization
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
