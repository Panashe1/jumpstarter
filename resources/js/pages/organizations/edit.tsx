import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type OrganizationRole, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Member {
    id: number;
    name: string;
    email: string;
    role: OrganizationRole;
}

interface Props {
    organization: { id: number; name: string; slug: string };
    members: Member[];
    can: { update: boolean; delete: boolean; manageMembers: boolean };
}

export default function EditOrganization({ organization, members, can }: Props) {
    const { auth } = usePage<SharedData>().props;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Organization settings', href: route('organizations.edit', organization.id) },
    ];

    const rename = useForm({ name: organization.name });
    const addMember = useForm({ email: '', role: 'member' });

    const submitRename: FormEventHandler = (e) => {
        e.preventDefault();
        rename.patch(route('organizations.update', organization.id));
    };

    const submitAddMember: FormEventHandler = (e) => {
        e.preventDefault();
        addMember.post(route('organizations.members.store', organization.id), {
            onSuccess: () => addMember.reset(),
        });
    };

    const changeRole = (member: Member, role: string) => {
        router.patch(route('organizations.members.update', [organization.id, member.id]), { role });
    };

    const removeMember = (member: Member) => {
        const message =
            member.id === auth.user.id
                ? 'Leave this organization?'
                : `Remove ${member.name} from ${organization.name}?`;

        if (confirm(message)) {
            router.delete(route('organizations.members.destroy', [organization.id, member.id]));
        }
    };

    const deleteOrganization = () => {
        if (confirm(`Delete ${organization.name}? All of its projects and tasks will be permanently removed.`)) {
            router.delete(route('organizations.destroy', organization.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organization settings" />

            <div className="mx-auto w-full max-w-3xl space-y-10 p-6">
                <section className="space-y-6">
                    <HeadingSmall title="Organization" description="Rename your organization" />

                    <form onSubmit={submitRename} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={rename.data.name}
                                onChange={(e) => rename.setData('name', e.target.value)}
                                disabled={!can.update}
                                required
                            />
                            <InputError message={rename.errors.name} />
                        </div>

                        {can.update && <Button disabled={rename.processing}>Save</Button>}
                    </form>
                </section>

                <section className="space-y-6">
                    <HeadingSmall title="Members" description="People with access to this organization" />

                    {can.manageMembers && (
                        <form onSubmit={submitAddMember} className="flex items-end gap-2">
                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="email">Add member by email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    placeholder="teammate@example.com"
                                    value={addMember.data.email}
                                    onChange={(e) => addMember.setData('email', e.target.value)}
                                    required
                                />
                            </div>
                            <Select value={addMember.data.role} onValueChange={(role) => addMember.setData('role', role)}>
                                <SelectTrigger className="w-32">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="member">Member</SelectItem>
                                    <SelectItem value="admin">Admin</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button disabled={addMember.processing}>Add</Button>
                        </form>
                    )}
                    <InputError message={addMember.errors.email} />

                    <ul className="divide-y rounded-lg border">
                        {members.map((member) => (
                            <li key={member.id} className="flex items-center justify-between gap-4 p-4">
                                <div className="min-w-0">
                                    <p className="truncate font-medium">
                                        {member.name}
                                        {member.id === auth.user.id && <span className="ml-2 text-xs text-muted-foreground">(you)</span>}
                                    </p>
                                    <p className="truncate text-sm text-muted-foreground">{member.email}</p>
                                </div>

                                <div className="flex shrink-0 items-center gap-2">
                                    {can.manageMembers && member.role !== 'owner' ? (
                                        <Select value={member.role} onValueChange={(role) => changeRole(member, role)}>
                                            <SelectTrigger className="w-28">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="member">Member</SelectItem>
                                                <SelectItem value="admin">Admin</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    ) : (
                                        <span className="text-sm capitalize text-muted-foreground">{member.role}</span>
                                    )}

                                    {member.role !== 'owner' && (can.manageMembers || member.id === auth.user.id) && (
                                        <Button variant="ghost" size="sm" onClick={() => removeMember(member)}>
                                            {member.id === auth.user.id ? 'Leave' : 'Remove'}
                                        </Button>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                </section>

                {can.delete && (
                    <section className="space-y-4 rounded-lg border border-destructive/50 p-4">
                        <HeadingSmall title="Danger zone" description="Deleting an organization removes all of its data" />
                        <Button variant="destructive" onClick={deleteOrganization}>
                            Delete organization
                        </Button>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
