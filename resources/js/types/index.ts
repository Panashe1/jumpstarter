import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export type OrganizationRole = 'owner' | 'admin' | 'member';

export interface Organization {
    id: number;
    name: string;
    slug: string;
    role: OrganizationRole;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    organizations: Organization[];
    currentOrganization: Organization | null;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}
